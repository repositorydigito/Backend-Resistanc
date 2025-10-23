<x-app>

    @push('css')
        <style>
            .hero {

                background:
                    linear-gradient(88deg, #B66F37 26.73%, rgba(157, 90, 169, 0.90) 48.98%, rgba(181, 130, 190, 0.70) 68.44%, rgba(255, 255, 255, 0.00) 83.14%),
                    url({{ asset('image/pages/banner1.png') }});
                background-size: cover;
                background-position: center;
            }

            .categorias__list {
                background: #ffffff6e;
                padding: 2rem;
                border-radius: 3rem;
            }


            .categoria__item {
                position: relative;
                scale: 0.98;
                transition: all ease .3s;
                overflow: hidden;
                background: #fff;
            }

            .categoria__item::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;

            }

            .categoria__item>* {
                z-index: 11;
            }

            .categoria__item:hover {}

            .categoria__item:hover {
                scale: 1;
                /* background: rgba(var(--color-rgb), 0.001); */
            }

            .categoria__item:hover::before {
                background: rgba(var(--color-rgb), 0.03);
                /* Color en hover */
            }

            /* Estilos para el componente Livewire */
            .paquetes__content {
                max-width: 1200px;
                margin: 0 auto;
            }

            .package-card {
                transition: all 0.3s ease;
                border: 2px solid transparent;
            }

            .package-card:hover {
                transform: translateY(-5px);
                border-color: #9D5AA9;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }

            .discipline-filter {
                scrollbar-width: thin;
                scrollbar-color: #9D5AA9 #f3f4f6;
            }

            .discipline-filter::-webkit-scrollbar {
                height: 6px;
            }

            .discipline-filter::-webkit-scrollbar-track {
                background: #f3f4f6;
                border-radius: 3px;
            }

            .discipline-filter::-webkit-scrollbar-thumb {
                background: #9D5AA9;
                border-radius: 3px;
            }

            .discipline-filter::-webkit-scrollbar-thumb:hover {
                background: #7c3aed;
            }

            @media (max-width: 768px) {
                .categorias__list {

                    padding: 1rem;
                    border-radius: 2rem;
                }
            }
        </style>
    @endpush

    {{-- Hero --}}
    <section class="hero h-[30rem] items-center justify-center content-center pt-6">
        <div class="container ">

            <div data-aos="fade-up" class="grid gap-8 w-full  text-white">
                <h1 class="text-4xl lg:text-6xl  font-extrabold tracking-[8px]"><span class="font-light">CONOCE NUESTROS </span> <br>
                    PAQUETES & BENEFICIOS
                </h1>



            </div>



        </div>
    </section>





    <div class="paquetes py-16">
        <div class="paquetes__container container">
            <div class="text-center grid gap-3 mb-5">
                <h2 class="text-4xl lg:text-6xl font-extralight text-center mb-16 ">ENTRENA CON LOS MEJORES <br> <span
                        class="font-bold"> PAQUETES DE CLASES</span></h2>

            </div>
            <!-- Componente Livewire -->
            @livewire('package-livewire')
        </div>
    </div>

    <div class="categorias py-12">

        <div class="categorias__container container">

            <div class="categorias__content">

                <h1 class="text-4xl lg:text-6xl font-extralight text-center mb-16">Y DISFRUTA LOS MEJORES <br> <span
                        class="font-bold flex flex-wrap gap-2 items-center justify-center">BENEFICIOS DE <img
                            class="h-8 lg:h-12 object-contain w-auto inline-block"
                            src="{{ asset('image/logos/rsistanclogo.png') }}" alt=""></span></h1>


                <div class="categorias__list grid content-start  grid-cols-1 gap-6 lg:grid-cols-3">
                    @foreach ($membreships as $membreship)
                        <div class="categoria__item bg-white p-6 rounded-3xl grid gap-1 content-start cursor-pointer"
                            style="border: 2px solid {{ $membreship->color_hex ?? '#9D5AA9' }}; --color-rgb: {{ $membreship->color_rgb ?? '157, 90, 150' }};">
                            <span class="uppercase font-medium">Categoría</span>
                            <h3 class="uppercase text-3xl">
                                @php
                                    $words = explode(' ', $membreship->name);
                                    $firstWord = array_shift($words);
                                    $remainingWords = implode(' ', $words);
                                @endphp
                                <span class="font-light"
                                    style="
        background: linear-gradient(-135deg, #ffffff 0%, {{ $membreship->color_hex ?? '#9D5AA9' }} 50%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-fill-color: transparent;
    ">{{ $firstWord }}</span>
                                @if ($remainingWords)
                                    <span class="font-bold"
                                        style="
            background: linear-gradient(-135deg, #ffffff 0%, {{ $membreship->color_hex ?? '#9D5AA9' }} 50%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-fill-color: transparent;
        ">{{ $remainingWords }}</span>
                                @endif
                            </h3>
                            <p class="font-light text-gray-600">
                                {{ $membreship->description }}
                            </p>

                            @if ($membreship->classes_before || $membreship->is_benefit_shake || $membreship->is_benefit_discipline)
                                <div class="border-t-2 mt-5 grid  gap-2 pt-3 font-light text-gray-600"
                                    style="border-color: {{ $membreship->color_hex ?? '#9D5AA9' }};">
                                    @if ($membreship->classes_before)
                                        <span class="flex items-center gap-2">

                                            <img class="h-4" src="{{ asset('image/logos/calendario.png') }}"
                                                alt=""> Acceso a clases
                                            {{ $membreship->classes_before }} días antes
                                        </span>
                                    @endif
                                    @if ($membreship->is_benefit_shake)
                                        <span class="flex items-center gap-2">
                                            <img class="h-4" src="{{ asset('image/logos/shake.png') }}"
                                                alt="">
                                            {{ $membreship->shake_quantity }} shakes gratis
                                        </span>
                                    @endif

                                    @if ($membreship->is_benefit_discipline)
                                        <span class="flex items-center gap-2">
                                            <img style="filter: brightness(0) saturate(100%) invert(19%) sepia(24%) saturate(395%) hue-rotate(168deg) brightness(92%) contrast(89%);"
                                                class="h-4"
                                                src="{{ 'storage/' . $membreship->discipline->icon_url ?? asset('image/pages/logo.png') }}"
                                                alt=""> 1 clase de {{ $membreship->discipline->name }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

            </div>

        </div>

    </div>


</x-app>
