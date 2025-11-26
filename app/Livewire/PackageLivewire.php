<?php

namespace App\Livewire;

use App\Models\Package;
use App\Models\Discipline;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PackageLivewire extends Component
{
    use WithPagination;

    public $selectedTab = 'paquetes'; // 'paquetes' o 'membresias'
    public $selectedDisciplineGroup = null;
    public $disciplineGroups = [];
    public $packages = [];
    public $loading = false;

    protected $queryString = [
        'selectedTab' => ['except' => 'paquetes'],
        'selectedDisciplineGroup' => ['except' => ''],
    ];

    public function mount()
    {

        $this->loadDisciplineGroups();


        // Seleccionar automáticamente el primer grupo de disciplinas si existe
        if (!empty($this->disciplineGroups) && is_null($this->selectedDisciplineGroup)) {
            $this->selectedDisciplineGroup = $this->disciplineGroups[0]['group_key'];

        }

        $this->loadPackages();

    }

    public function updatedSelectedTab()
    {
        // No resetear el filtro de disciplinas, solo recargar los paquetes
        $this->resetPage();
        $this->loadPackages();
    }

    public function updatedSelectedDisciplineGroup()
    {
        $this->resetPage();
        $this->loadPackages();
    }

    public function selectDisciplineGroup($groupKey)
    {

        $this->selectedDisciplineGroup = $groupKey;
        $this->resetPage();
        $this->loadPackages();
    }

    public function clearDisciplineFilter()
    {

        $this->selectedDisciplineGroup = null;
        $this->resetPage();
        $this->loadPackages();
    }


    public function loadDisciplineGroups()
    {


        try {
            // Obtener paquetes activos con sus disciplinas
            $packagesQuery = \App\Models\Package::query()
                ->with(['disciplines'])
                ->where('buy_type', 'affordable')
                ->where('status', 'active')
                ->where(function ($query) {
                    // Paquetes fijos o temporales vigentes
                    $query->where('type', 'fixed')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'temporary')
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        });
                });

            // Aplicar filtros opcionales
            if ($this->selectedTab === 'membresias') {
                $packagesQuery->where('is_membresia', true);
            } else {
                $packagesQuery->where('is_membresia', false);
            }

            $packages = $packagesQuery->get();

            // Crear grupos únicos de disciplinas
            $disciplineGroups = [];
            $groupCounter = 0;

            foreach ($packages as $package) {
                // Crear una clave única para el grupo de disciplinas de este paquete
                $disciplineIds = $package->disciplines->pluck('id')->sort()->values()->toArray();
                $groupKey = implode('-', $disciplineIds);

                // Si el grupo no existe, crearlo
                if (!isset($disciplineGroups[$groupKey])) {
                    $groupCounter++;
                    $disciplineGroups[$groupKey] = [
                        'id' => $groupCounter,
                        'group_key' => $groupKey,
                        'disciplines' => [],
                        'disciplines_count' => count($disciplineIds),
                        'packages_count' => 0,
                        'group_name' => '', // Se generará después
                    ];

                    // Agregar información de cada disciplina en el grupo
                    foreach ($package->disciplines as $discipline) {
                        $disciplineGroups[$groupKey]['disciplines'][] = [
                            'id' => $discipline->id,
                            'name' => $discipline->name,
                            'display_name' => $discipline->display_name,
                            'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : asset('default/icon.png'),
                            'color_hex' => $discipline->color_hex,
                            'order' => $discipline->order,
                        ];
                    }

                    // Ordenar disciplinas por orden
                    usort($disciplineGroups[$groupKey]['disciplines'], function ($a, $b) {
                        return $a['order'] <=> $b['order'];
                    });
                }

                // Incrementar contador de paquetes para este grupo
                $disciplineGroups[$groupKey]['packages_count']++;
            }

            // Generar nombres descriptivos para cada grupo
            foreach ($disciplineGroups as &$group) {
                $disciplineNames = array_column($group['disciplines'], 'display_name');

                if (count($disciplineNames) === 1) {
                    $group['group_name'] = $disciplineNames[0];
                } else {
                    $group['group_name'] = implode(' + ', $disciplineNames);
                }
            }

            // Ordenar grupos por cantidad de disciplinas y luego por nombre
            uasort($disciplineGroups, function ($a, $b) {
                if ($a['disciplines_count'] === $b['disciplines_count']) {
                    return $a['group_name'] <=> $b['group_name'];
                }
                return $a['disciplines_count'] <=> $b['disciplines_count'];
            });

            // Convertir a array indexado
            $this->disciplineGroups = array_values($disciplineGroups);


        } catch (\Exception $e) {

            $this->disciplineGroups = [];
        }
    }

    public function loadPackages()
    {
        $this->loading = true;

        try {
            $query = Package::with(['disciplines'])
                ->where('buy_type', 'affordable')
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('type', 'fixed')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'temporary')
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        });
                });

            // Filtrar por tipo (paquete o membresía)
            if ($this->selectedTab === 'membresias') {
                $query->where('is_membresia', true);
            } else {
                $query->where('is_membresia', false);
            }

            // Filtrar por grupo de disciplinas
            if ($this->selectedDisciplineGroup) {


                $group = collect($this->disciplineGroups)->firstWhere('group_key', $this->selectedDisciplineGroup);
                if ($group) {
                    $disciplineIds = collect($group['disciplines'])->pluck('id')->toArray();

                    $query->whereHas('disciplines', function ($q) use ($disciplineIds) {
                        $q->whereIn('disciplines.id', $disciplineIds);
                    });
                } else {
                    Log::warning('Group not found for filtering:', ['groupKey' => $this->selectedDisciplineGroup]);
                }
            }

            $this->packages = $query->orderBy('classes_quantity', 'asc')->get();
        } catch (\Exception $e) {
            $this->packages = collect();
        } finally {
            $this->loading = false;
        }
    }


    public function render()
    {
        return view('livewire.package-livewire');
    }
}
