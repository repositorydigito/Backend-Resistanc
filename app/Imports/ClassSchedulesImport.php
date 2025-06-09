<?php

/**
 * ClassSchedulesImport - Importación de horarios de clases
 *
 * IMPORTANTE: La columna B (instructor) ahora debe contener el NÚMERO DE DOCUMENTO
 * del instructor en lugar del nombre, ya que el document_number es único y más confiable.
 *
 * Formato del archivo Excel/CSV:
 * A: clase (nombre exacto)
 * B: instructor (número de documento - ej: 12345678)
 * C: sala (nombre exacto)
 * D: fecha (DD/MM/YYYY)
 * E: hora_inicio (HH:MM)
 * F: hora_fin (HH:MM)
 * G: capacidad (número)
 * H: reservas_abre (opcional - DD/MM/YYYY HH:MM)
 * I: reservas_cierran (opcional - DD/MM/YYYY HH:MM)
 * J: limite_cancelacion (opcional - DD/MM/YYYY HH:MM)
 * K: es_feriado (opcional - 0 o 1)
 */

namespace App\Imports;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\Instructor;
use App\Models\Studio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithStartRow; // ✅ Agregar esto

use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;
// use Maatwebsite\Excel\Concerns\SkipsOnError;
// use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class ClassSchedulesImport implements ToModel, WithHeadingRow
{
    protected $errors = [];
    protected $warnings = [];
    protected $currentRow = 1;

    public function model(array $row)
    {
        $this->currentRow++;

        try {
            // Mapear datos con manejo de campos opcionales
            $columnMapping = [
                'clase' => trim($row['clase'] ?? ''),
                'instructor' => trim($row['instructor'] ?? ''),
                'sala' => trim($row['sala'] ?? ''),
                'fecha' => $row['fecha'] ?? '',
                'hora_inicio' => $row['hora_inicio'] ?? '',
                'hora_fin' => $row['hora_fin'] ?? '',
                'capacidad' => trim($row['capacidad'] ?? ''),
                'reservas_abre' => $row['reservas_abre'] ?? null,
                'reservas_cierran' => $row['reservas_cierran'] ?? null,
                'limite_cancelacion' => $row['limite_cancelacion'] ?? null,
                'es_feriado' => trim($row['es_feriado'] ?? '') ?: 0,
            ];

            Log::info("Fila {$this->currentRow} - Datos recibidos:", $columnMapping);

            // Verificar que no sea fila vacía
            if (empty($columnMapping['clase'])) {
                return null;
            }

            $this->validateRequiredFields($columnMapping);

            $class = $this->findClass($columnMapping);
            $instructor = $this->findInstructor($columnMapping);
            $studio = $this->findStudio($columnMapping);

            $this->validateRelationships($columnMapping, $class, $instructor, $studio);

            // ✅ SOLUCIÓN: Procesar campos de fecha/hora por separado y con validación
            $scheduledDate = $this->processDate($columnMapping['fecha']);
            $startTime = $this->processTime($columnMapping['hora_inicio']);
            $endTime = $this->processTime($columnMapping['hora_fin']);

            // ✅ Procesar campos opcionales con validación mejorada
            $bookingOpensAt = $this->processOptionalDateTime($columnMapping['reservas_abre']);
            $bookingClosesAt = $this->processOptionalDateTime($columnMapping['reservas_cierran']);
            $cancellationDeadline = $this->processOptionalDateTime($columnMapping['limite_cancelacion']);

            $this->validateScheduleTimes($scheduledDate, $startTime, $endTime, $bookingOpensAt, $bookingClosesAt, $cancellationDeadline);
            $this->checkScheduleConflicts($instructor->id, $studio->id, $scheduledDate, $startTime, $endTime);

            Log::info("=== DATOS FINALES VALIDADOS ===");
            Log::info("scheduledDate: {$scheduledDate}");
            Log::info("startTime: {$startTime}");
            Log::info("endTime: {$endTime}");
            Log::info("bookingOpensAt: " . ($bookingOpensAt ?? 'null'));
            Log::info("bookingClosesAt: " . ($bookingClosesAt ?? 'null'));
            Log::info("cancellationDeadline: " . ($cancellationDeadline ?? 'null'));

            return new ClassSchedule([
                'class_id' => $class->id,
                'instructor_id' => $instructor->id,
                'studio_id' => $studio->id,
                'scheduled_date' => $scheduledDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_capacity' => (int) $columnMapping['capacidad'],
                'available_spots' => (int) $columnMapping['capacidad'],
                'booked_spots' => 0,
                'waitlist_spots' => 0,
                'booking_opens_at' => $bookingOpensAt,
                'booking_closes_at' => $bookingClosesAt,
                'cancellation_deadline' => $cancellationDeadline,
                'is_holiday_schedule' => (bool) ($columnMapping['es_feriado'] ?? 0),
                'status' => 'scheduled',
            ]);
        } catch (\Exception $e) {
            $this->errors[] = "FILA {$this->currentRow}: " . $e->getMessage();
            return null;
        }
    }

    /**
     * ✅ NUEVO: Procesar solo fechas (sin hora)
     */
    private function processDate($value)
    {
        if (empty($value)) {
            throw new \Exception('Fecha vacía en columna D');
        }

        try {
            Log::info("=== PROCESANDO FECHA ===");
            Log::info("Valor original: " . json_encode($value) . " (tipo: " . gettype($value) . ")");

            // Detectar y prevenir fechas duplicadas
            $valueStr = (string) $value;
            if (preg_match('/\d{4}-\d{2}-\d{2}.*\d{4}-\d{2}-\d{2}/', $valueStr)) {
                throw new \Exception("Fecha contiene valores duplicados: '{$valueStr}'");
            }

            // Si es numérico (Excel)
            if (is_numeric($value)) {
                $excelDate = floatval($value);
                $unixTimestamp = ($excelDate - 25569) * 86400;
                $date = Carbon::createFromTimestamp($unixTimestamp);
                $result = $date->format('Y-m-d');
                Log::info("✅ Fecha Excel convertida: {$result}");
                return $result;
            }

            $dateString = trim($value);

            // Si incluye hora, extraer solo la fecha
            if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})/', $dateString, $matches)) {
                $dateString = $matches[1];
                Log::info("Extrayendo solo fecha: {$dateString}");
            }

            // Si ya está en formato correcto
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                Log::info("✅ Fecha ya en formato correcto: {$dateString}");
                return $dateString;
            }

            // Intentar formatos comunes
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateString);
                    if ($date && $date->format($format) === $dateString) {
                        $result = $date->format('Y-m-d');
                        Log::info("✅ Fecha convertida con formato {$format}: {$result}");
                        return $result;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            throw new \Exception("Formato de fecha no reconocido: '{$dateString}'");
        } catch (\Exception $e) {
            Log::error("Error procesando fecha: " . $e->getMessage());
            throw new \Exception("Error en columna D (fecha): " . $e->getMessage());
        }
    }

    /**
     * ✅ NUEVO: Procesar solo horas (sin fecha)
     */
    private function processTime($value)
    {
        if (empty($value)) {
            throw new \Exception('Hora vacía');
        }

        try {
            Log::info("=== PROCESANDO HORA ===");
            Log::info("Valor original: " . json_encode($value) . " (tipo: " . gettype($value) . ")");

            // Si es fracción decimal (Excel)
            if (is_numeric($value) && $value < 1) {
                $totalSeconds = $value * 24 * 60 * 60;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $result = sprintf('%02d:%02d:00', $hours, $minutes);
                Log::info("✅ Fracción Excel convertida: {$result}");
                return $result;
            }

            // Si es número mayor (fecha+hora Excel), extraer solo la hora
            if (is_numeric($value) && $value > 1) {
                $excelDateTime = floatval($value);
                $unixTimestamp = ($excelDateTime - 25569) * 86400;
                $dateTime = Carbon::createFromTimestamp($unixTimestamp);
                $result = $dateTime->format('H:i:s');
                Log::info("✅ Hora extraída de Excel: {$result}");
                return $result;
            }

            $timeString = trim($value);

            // Si ya está en formato correcto
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeString)) {
                Log::info("✅ Hora ya en formato correcto: {$timeString}");
                return $timeString;
            }

            // Formato HH:MM
            if (preg_match('/^\d{1,2}:\d{2}$/', $timeString)) {
                $time = Carbon::createFromFormat('H:i', $timeString);
                $result = $time->format('H:i:s');
                Log::info("✅ Hora HH:MM convertida: {$result}");
                return $result;
            }

            throw new \Exception("Formato de hora no reconocido: '{$timeString}'");
        } catch (\Exception $e) {
            Log::error("Error procesando hora: " . $e->getMessage());
            throw new \Exception("Hora inválida '{$value}'. Usa formato HH:MM");
        }
    }

    /**
     * ✅ NUEVO: Procesar fecha-hora opcionales con validación estricta
     */
    private function processOptionalDateTime($value)
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        try {
            Log::info("=== PROCESANDO DATETIME OPCIONAL ===");
            Log::info("Valor original: " . json_encode($value) . " (tipo: " . gettype($value) . ")");

            $valueStr = (string) $value;

            // ✅ VALIDACIÓN CRÍTICA: Detectar fechas duplicadas
            if (preg_match('/\d{4}-\d{2}-\d{2}.*\d{4}-\d{2}-\d{2}/', $valueStr)) {
                throw new \Exception("DateTime contiene fechas duplicadas: '{$valueStr}'");
            }

            // Si es numérico (Excel)
            if (is_numeric($value)) {
                $excelDateTime = floatval($value);
                $unixTimestamp = ($excelDateTime - 25569) * 86400;
                $dateTime = Carbon::createFromTimestamp($unixTimestamp);
                $result = $dateTime->format('Y-m-d H:i:s');
                Log::info("✅ DateTime Excel convertido: {$result}");
                return $result;
            }

            $dateTimeString = trim($value);

            // Si ya está en formato correcto
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateTimeString)) {
                Log::info("✅ DateTime ya en formato correcto: {$dateTimeString}");
                return $dateTimeString;
            }

            // Intentar formatos específicos
            $formats = [
                'd/m/Y H:i',
                'd/m/Y H:i:s',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
            ];

            foreach ($formats as $format) {
                try {
                    $dateTime = Carbon::createFromFormat($format, $dateTimeString);
                    if ($dateTime && $dateTime->format($format) === $dateTimeString) {
                        $result = $dateTime->format('Y-m-d H:i:s');
                        Log::info("✅ DateTime convertido con formato {$format}: {$result}");
                        return $result;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Si es solo fecha, agregar hora por defecto
            if (strlen($dateTimeString) <= 10 && !str_contains($dateTimeString, ':')) {
                $dateTimeString .= ' 08:00:00';
            }

            $dateTime = Carbon::parse($dateTimeString);
            $result = $dateTime->format('Y-m-d H:i:s');
            Log::info("✅ DateTime parseado automáticamente: {$result}");
            return $result;
        } catch (\Exception $e) {
            Log::error("Error procesando datetime opcional: " . $e->getMessage());
            throw new \Exception("DateTime inválido '{$value}': " . $e->getMessage());
        }
    }

    // ✅ ELIMINAR MÉTODOS ANTIGUOS DUPLICADOS
    // Remover: transformDateTime, transformTime, transformDate, parseTime, parseDateTime, parseDate

    protected function validateRequiredFields(array $row)
    {
        $requiredFields = [
            'clase' => 'A (Nombre de la clase)',
            'instructor' => 'B (Número de documento del instructor)',
            'sala' => 'C (Nombre de la sala)',
            'fecha' => 'D (Fecha)',
            'hora_inicio' => 'E (Hora de inicio)',
            'hora_fin' => 'F (Hora de fin)',
            'capacidad' => 'G (Capacidad)'
        ];

        foreach ($requiredFields as $field => $column) {
            if (empty($row[$field])) {
                throw new \Exception("Campo obligatorio faltante en columna {$column}: '{$field}'");
            }
        }
    }

    protected function findClass(array $row)
    {
        $className = trim($row['clase'] ?? '');
        $class = ClassModel::where('name', $className)->first();

        if (!$class) {
            throw new \Exception("Clase '{$className}' no encontrada en columna A. Verifica que el nombre sea exacto.");
        }

        return $class;
    }

    protected function findInstructor(array $row)
    {
        $instructorDocument = trim($row['instructor'] ?? '');
        $instructor = Instructor::where('document_number', $instructorDocument)->first();

        if (!$instructor) {
            $similar = Instructor::where('document_number', 'LIKE', "%{$instructorDocument}%")
                ->pluck('document_number')
                ->take(3);

            $suggestion = $similar->isNotEmpty()
                ? " ¿Quisiste decir: " . $similar->implode(', ') . "?"
                : "";

            throw new \Exception("Instructor con documento '{$instructorDocument}' no encontrado en columna B.{$suggestion}");
        }

        return $instructor;
    }

    protected function findStudio(array $row)
    {
        $studioName = trim($row['sala'] ?? '');
        $studio = Studio::where('name', $studioName)->first();

        if (!$studio) {
            $similar = Studio::where('name', 'LIKE', "%{$studioName}%")->pluck('name')->take(3);
            $suggestion = $similar->isNotEmpty() ? " ¿Quisiste decir: " . $similar->implode(', ') . "?" : "";

            throw new \Exception("Sala '{$studioName}' no encontrada en columna C.{$suggestion}");
        }

        return $studio;
    }

    protected function validateScheduleTimes($scheduledDate, $startTime, $endTime, $bookingOpensAt, $bookingClosesAt, $cancellationDeadline)
    {
        $scheduledDateTime = Carbon::parse($scheduledDate);
        $startDateTime = Carbon::parse($scheduledDate . ' ' . $startTime);
        $endDateTime = Carbon::parse($scheduledDate . ' ' . $endTime);

        if ($scheduledDateTime->isPast()) {
            throw new \Exception("No se pueden programar clases en fechas pasadas. Fecha: {$scheduledDate}");
        }

        if ($endDateTime <= $startDateTime) {
            throw new \Exception("La hora de fin ({$endTime}) debe ser posterior a la hora de inicio ({$startTime})");
        }

        $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
        if ($durationMinutes < 30) {
            throw new \Exception("La clase debe durar al menos 30 minutos. Duración actual: {$durationMinutes} minutos");
        }

        if ($durationMinutes > 180) {
            $this->warnings[] = "FILA {$this->currentRow}: Clase muy larga ({$durationMinutes} minutos)";
        }

        // Validar fechas opcionales
        if ($bookingOpensAt && $bookingClosesAt) {
            $opensAt = Carbon::parse($bookingOpensAt);
            $closesAt = Carbon::parse($bookingClosesAt);

            if ($closesAt <= $opensAt) {
                throw new \Exception("La fecha de cierre de reservas debe ser posterior a la fecha de apertura");
            }

            if ($closesAt >= $startDateTime) {
                $this->warnings[] = "FILA {$this->currentRow}: Las reservas cierran después del inicio de la clase";
            }
        }

        if ($cancellationDeadline) {
            $deadline = Carbon::parse($cancellationDeadline);
            if ($deadline >= $startDateTime) {
                $this->warnings[] = "FILA {$this->currentRow}: El límite de cancelación es después del inicio de la clase";
            }
        }
    }

    protected function checkScheduleConflicts($instructorId, $studioId, $scheduledDate, $startTime, $endTime)
    {
        // Verificar conflicto de instructor
        $instructorConflict = ClassSchedule::where('instructor_id', $instructorId)
            ->where('scheduled_date', $scheduledDate)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })->first();

        if ($instructorConflict) {
            throw new \Exception("CONFLICTO: El instructor ya tiene una clase programada el {$scheduledDate} de {$instructorConflict->start_time} a {$instructorConflict->end_time}");
        }

        // Verificar conflicto de sala
        $studioConflict = ClassSchedule::where('studio_id', $studioId)
            ->where('scheduled_date', $scheduledDate)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })->first();

        if ($studioConflict) {
            throw new \Exception("CONFLICTO: La sala ya está ocupada el {$scheduledDate} de {$studioConflict->start_time} a {$studioConflict->end_time}");
        }
    }

    protected function validateRelationships($row, $class, $instructor, $studio)
    {
        if (!$class) {
            throw new \Exception("Clase '{$row['clase']}' no encontrada en el sistema");
        }

        if (!$instructor) {
            throw new \Exception("Instructor '{$row['instructor']}' no encontrado en el sistema");
        }

        if (!$studio) {
            throw new \Exception("Sala '{$row['sala']}' no encontrada en el sistema");
        }

        if ($instructor->status !== 'active') {
            throw new \Exception("El instructor '{$instructor->name}' (Doc: {$instructor->document_number}) no está activo");
        }

        if (!$studio->is_active) {
            throw new \Exception("La sala '{$studio->name}' no está activa");
        }

        $canTeach = $instructor->disciplines()->where('discipline_id', $class->discipline_id)->exists();
        if (!$canTeach) {
            $this->warnings[] = "ADVERTENCIA: El instructor '{$instructor->name}' (Doc: {$instructor->document_number}) no está certificado para enseñar '{$class->name}'";
        }
    }

    public function onError(\Throwable $e)
    {
        $this->errors[] = "FILA {$this->currentRow}: Error inesperado - " . $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = implode(', ', $failure->errors());
            $this->errors[] = "FILA {$row}: {$errors}";
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }
}
