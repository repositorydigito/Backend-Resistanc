@php
    $studioId = $getState() ?? data_get($getRecord(), 'studio_id');
@endphp

<div x-data="{ studioId: @js($studioId) }" 
     x-init="
        $watch('studioId', value => {
            if (window.Livewire) {
                // Buscar el componente de vista previa y actualizarlo
                const previewComponent = document.querySelector('[wire\\:id]');
                if (previewComponent) {
                    const componentId = previewComponent.getAttribute('wire:id');
                    const livewireComponent = window.Livewire.find(componentId);
                    if (livewireComponent) {
                        livewireComponent.set('studioId', value);
                    }
                }
            }
        })
     ">
    
    {{-- Escuchar cambios en el campo studio_id --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Escuchar cambios en el select de studio_id
            const studioSelect = document.querySelector('select[name="studio_id"]');
            if (studioSelect) {
                studioSelect.addEventListener('change', function(e) {
                    const newStudioId = e.target.value;
                    
                    // Actualizar el componente Alpine
                    const alpineComponent = document.querySelector('[x-data*="studioId"]');
                    if (alpineComponent && alpineComponent._x_dataStack) {
                        alpineComponent._x_dataStack[0].studioId = newStudioId;
                    }
                    
                    // Actualizar el componente Livewire
                    setTimeout(() => {
                        const previewComponent = document.querySelector('[wire\\:id]');
                        if (previewComponent && window.Livewire) {
                            const componentId = previewComponent.getAttribute('wire:id');
                            const livewireComponent = window.Livewire.find(componentId);
                            if (livewireComponent) {
                                livewireComponent.set('studioId', newStudioId);
                            }
                        }
                    }, 100);
                });
            }
        });
    </script>

    {{-- Componente Livewire de vista previa --}}
    @livewire('studio-seat-preview', ['studioId' => $studioId], key('studio-preview-' . ($studioId ?? 'none')))
</div>
