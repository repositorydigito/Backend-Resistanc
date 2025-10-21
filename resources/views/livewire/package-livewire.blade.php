@push('css')
    <style>
        * {
            /* outline: 1px solid red; */
        }

        .package__tab {
            background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .package__active {
            background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
            color: #fff !important;
            -webkit-text-fill-color: unset;
        }

        .discipline-group__active {
            background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
        }

        .package__text-gradient {
            background: linear-gradient(135deg, var(--discipline-color) 0%, #ffffff 150%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
    </style>
@endpush

<div class="">

    <!-- Tabs de filtro principal -->
    <div class="flex justify-center mb-8">
        <div class="bg-gray-100 rounded-full  flex">
            <button wire:click="$set('selectedTab', 'paquetes')"
                class="package__tab px-6 py-3 rounded-full font-medium transition-all duration-300 {{ $selectedTab === 'paquetes' ? 'package__active  shadow-lg' : 'text-gray-600 hover:text-gray-800' }}">
                PAQUETES
            </button>
            <button wire:click="$set('selectedTab', 'membresias')"
                class="package__tab px-6 py-3 rounded-full font-medium transition-all duration-300 {{ $selectedTab === 'membresias' ? 'package__active shadow-lg' : 'text-gray-600 hover:text-gray-800' }}">
                MEMBRESÍAS
            </button>
        </div>
    </div>

    <div class="categorias__list">


        <!-- Filtros por grupo de disciplinas -->
        <div class="flex items-center justify-center mb-8 ">
            <div class="flex items-center gap-4 px-4">
                @if (count($disciplineGroups) > 0)
                    @foreach ($disciplineGroups as $group)
                        <button wire:click="selectDisciplineGroup('{{ $group['group_key'] }}')"
                            class="flex-shrink-0  rounded-full flex items-center justify-start transition-all duration-300 {{ $selectedDisciplineGroup === $group['group_key'] ? 'scale-110 shadow-lg' : 'hover:scale-105' }}"
                            style="" title="{{ $group['group_name'] }}" type="button">
                            {{-- @if ($group['disciplines_count'] === 1)
                                <!-- Una sola disciplina -->
                                <img src="{{ $group['disciplines'][0]['icon_url'] ? asset('storage/' . $group['disciplines'][0]['icon_url']) : asset('default/icon.png') }}"
                                    alt="{{ $group['disciplines'][0]['display_name'] }}" class="w-6 h-6 object-contain">
                            @else --}}
                            <!-- Múltiples disciplinas - mostrar círculos pequeños -->
                            <div
                                class="flex flex-wrap justify-center items-center gap-1 p-3 rounded-full {{ $selectedDisciplineGroup === $group['group_key'] ? 'discipline-group__active' : 'bg-white' }}">
                                @foreach ($group['disciplines'] as $discipline)
                                    <div class="w-14 h-14 rounded-full } grid content-center justify-center"
                                        style="background: linear-gradient(135deg, {{ $discipline['color_hex'] }} 0%, #ffffff 200%);"
                                        title="{{ $discipline['display_name'] }}">
                                        <img class="w-7 h-7 object-contain" src="{{ $discipline['icon_url'] }}"
                                            alt="">
                                    </div>
                                @endforeach
                            </div>
                            {{-- @endif --}}
                        </button>
                    @endforeach
                @else
                    <div class="text-gray-500 text-sm">
                        Cargando grupos de disciplinas... ({{ count($disciplineGroups) }} grupos encontrados)
                    </div>
                @endif
            </div>
        </div>

        <!-- Loading state -->
        @if ($loading)
            <div class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            </div>
        @else
            <!-- Grid de paquetes -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 cards">
                @forelse($packages as $package)
                    <div class="package-card flex flex-col content-start justify-start items-start bg-white rounded-3xl p-6  border-2 border-gray-100 cursor-pointer"
                        style="border:2px solid {{ $package->color_hex ?? '#9142AA' }}">
                        <!-- Header del paquete -->
                        <div class="text-center mb-4">
                            <h3 class="text-3xl font-bold package__text-gradient"
                                style="--discipline-color: {{ $package->color_hex ?? '#9142AA' }}">
                                {{ $package->classes_quantity }}
                                {{ $package->classes_quantity == 1 ? 'CLASE' : 'CLASES' }}
                            </h3>

                            {{-- @if ($package->is_membresia)
                                <span
                                    class="inline-block bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    MEMBRESÍA
                                </span>
                            @endif --}}
                        </div>



                        <!-- Disciplinas -->
                        {{-- <div class="mb-6">
                        <div class="flex flex-wrap gap-2 justify-center">
                            @foreach ($package->disciplines as $discipline)
                                <span class="px-3 py-1 rounded-full text-xs font-medium text-white"
                                    style="background-color: {{ $discipline->color_hex ?? '#9D5AA9' }}">
                                    {{ $discipline->display_name }}
                                </span>
                            @endforeach
                        </div>
                    </div> --}}

                        <!-- Detalles del paquete -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-lg text-gray-600">
                                <img class="w-5 object-contain" src="{{ asset('image/pages/fire.png') }}"
                                    alt="">
                                <span>{{ $package->classes_quantity }}
                                    {{ $package->classes_quantity == 1 ? 'clase' : 'clases' }}</span>
                            </div>

                            <div class="flex items-center gap-3 text-lg text-gray-600">
                                <img class="w-5 object-contain" src="{{ asset('image/pages/calendar.png') }}"
                                    alt="">
                                <span>Válido por {{ $package->validity_days ?? 30 }} días</span>
                            </div>

                            {{-- @if ($package->description)
                            <div class="flex items-start gap-3 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ Str::limit($package->description, 100) }}</span>
                            </div>
                        @endif --}}
                        </div>

                        <!-- Precio -->
                        <div class="text-center">
                            <div class="flex flex-col justify-start items-start ">
                                <span class="text-3xl font-semibold text-gray-800">S/
                                    {{ number_format($package->price_soles / $package->classes_quantity, 0) }}</span>

                                <span class="line-through text-lg ">S/
                                    {{ number_format($package->original_price_soles / $package->classes_quantity, 0) }}
                                </span>
                                <span> (S/{{ number_format($package->price_soles, 0) }}

                                    @if ($package->is_membresia)
                                        la membresia
                                    @else
                                        el paquete
                                    @endif
                                    )
                                </span>
                                @if ($package->compare_price_soles && $package->compare_price_soles > $package->price_soles)
                                    <span class="text-lg text-gray-500 line-through">S/
                                        {{ number_format($package->compare_price_soles, 0) }}</span>
                                @endif
                            </div>
                            {{-- <p class="text-sm text-gray-600">el paquete</p> --}}
                        </div>

                        <!-- Botón de acción -->
                        {{-- <div class="text-center">
                        @if ($package->is_membresia)
                            <button
                                class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold py-3 px-6 rounded-full hover:from-purple-700 hover:to-purple-800 transition-all duration-300 shadow-lg hover:shadow-xl"
                                onclick="alert('Funcionalidad de compra de membresía en desarrollo')">
                                ADQUIRIR MEMBRESÍA
                            </button>
                        @else
                            <button
                                class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-semibold py-3 px-6 rounded-full hover:from-orange-600 hover:to-orange-700 transition-all duration-300 shadow-lg hover:shadow-xl"
                                onclick="alert('Funcionalidad de compra de paquete en desarrollo')">
                                COMPRAR PAQUETE
                            </button>
                        @endif
                    </div> --}}
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay paquetes disponibles</h3>
                        <p class="text-gray-500">
                            @if ($selectedDisciplineGroup)
                                No se encontraron paquetes para el grupo de disciplinas seleccionado.
                            @else
                                No hay paquetes disponibles en este momento.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

</div>
