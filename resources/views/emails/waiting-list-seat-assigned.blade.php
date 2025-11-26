<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>¡Asiento asignado desde lista de espera!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .class-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .class-info h3 {
            margin-top: 0;
            color: #333;
        }
        .class-info p {
            margin: 5px 0;
        }
        .footer {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Buenas noticias, {{ $user->name }}!</h1>
    </div>

    <div class="content">
        <div class="success-badge">
            ✓ Asiento asignado desde lista de espera
        </div>

        <p>¡Te tenemos excelentes noticias! Se ha liberado un asiento en la clase para la que estabas en lista de espera y has sido asignado automáticamente.</p>

        <div class="class-info">
            <h3>Detalles de la clase:</h3>
            <p><strong>Clase:</strong> {{ $classSchedule->class->name ?? 'N/A' }}</p>
            <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($classSchedule->scheduled_date)->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ \Carbon\Carbon::parse($classSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($classSchedule->end_time)->format('H:i') }}</p>
            @if($classSchedule->instructor)
            <p><strong>Instructor:</strong> {{ $classSchedule->instructor->name }}</p>
            @endif
            @if($classSchedule->studio)
            <p><strong>Estudio:</strong> {{ $classSchedule->studio->name }}</p>
            @endif
            @if($seatNumber)
            <p><strong>Asiento:</strong> {{ $seatNumber }}</p>
            @endif
        </div>

        <p><strong>Importante:</strong> Tu asiento ha sido reservado automáticamente. Por favor, asegúrate de llegar a tiempo para la clase.</p>

        <p>Si no puedes asistir, por favor cancela tu reserva con al menos 1 hora de anticipación para que otra persona pueda tomar tu lugar.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ $company->name ?? 'Resistanc Studio' }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>


