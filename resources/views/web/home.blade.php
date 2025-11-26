<x-app>

    @push('css')
        <!-- Swiper CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

        <style>
            * {
                /* outline: 1px solid red; */
            }

            .hero {

                background:
                    linear-gradient(88deg, #B66F37 26.73%, rgba(157, 90, 169, 0.90) 48.98%, rgba(181, 130, 190, 0.70) 68.44%, rgba(255, 255, 255, 0.00) 83.14%),
                    url({{ asset('image/pages/banner1.png') }});
                background-size: cover;
                background-position: center;
            }

            /* Estilos personalizados para Swiper */
            .swiper {
                width: 100%;
                padding: 20px 0;
            }

            .swiper-slide {
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .swiper-button-next,
            .swiper-button-prev {
                color: #B66F37;
            }

            .swiper-pagination-bullet-active {
                background: #B66F37;
            }

            .main {
                /* background: #d0d7d6b7; */
            }

            .card__discipline {

                height: 280px;
                color: #fff;
                border-radius: 30px;
                padding: 1.5rem;
                display: grid;
                align-content: flex-end;
                position: relative;
                overflow: hidden;
            }

            .card__discipline::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.301);
                z-index: 10;
            }

            .card__discipline>* {
                z-index: 11;
            }

            .discipline__title {
                line-height: unset;

            }

            .discipline__icon {
                width: 20;
                height: 20px;
                object-fit: contain;
            }

            .discipline__description {
                font-family: var(--font-two);
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                font-size: 1rem;
                font-weight: 300;
                letter-spacing: .05rem;
            }

            .card__benef {
                height: 450px;
                color: #fff
            }

            .card__benef--one {

                background: url({{ asset('image/pages/banner_one.png') }});
                background-repeat: no-repeat;
                background-position: center;
                background-size: cover;
            }

            .card__benef--two {
                background-repeat: no-repeat;
                background-size: cover;


                background: linear-gradient(180deg, rgba(157, 90, 169, 0.50) 7.07%, rgba(163, 107, 182, 0.50) 51.03%, rgba(49, 44, 54, 0.5) 93.98%), url({{ asset('image/pages/cyclingGroup.png') }});

                background-position: center;
            }

            .store__img {
                background-position: end;
                background-size: contain;
                background-repeat: no-repeat;
            }

            .direccion__detl {
                display: flex;
                align-items: center;
                gap: 1rem;

            }

            .direccion__detl img {
                height: 30px;
                width: 30px;
                object-fit: contain;
            }



            .card__services {
                height: 350px;
                max-width: 250px;
                /* background: red; */
                padding: 1.2rem;
                border-radius: 20px;
                align-content: end;
                color: #fff;
                position: relative;
                overflow: hidden;
            }

            .card__services::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.356);
                z-index: 10;
            }

            .card__services>* {
                position: relative;
                z-index: 11;
            }

            .servicescard__title {
                display: flex;
                /* gap: .7rem; */
                font-size: 1.9rem;
                font-weight: 600;


            }

            .faqcontainer {
                background: #ffffff44;
            }

            /* Estilos personalizados para FAQ */
            .faq-container details {
                position: relative;
            }

            .faq-container details summary {
                list-style: none;
                cursor: pointer;
                position: relative;
                padding-right: 40px;
            }

            .faq-container details summary::-webkit-details-marker {
                display: none;
            }

            .faq-container details summary::before {
                content: '+';
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                font-size: 24px;
                font-weight: bold;
                color: #B66F37;
                transition: all 0.3s ease;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(182, 111, 55, 0.1);
            }

            .faq-container details[open] summary::before {
                content: '−';
                background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
                color: white;
            }

            .card__rsistanc {
                padding: 2rem;
                border-radius: 25px;
            }

            .service__card {
                min-height: 420px;
                width: 100%;
            }

            .home__title {
                font-size: 1.8rem;
                font-weight: 900;
            }

            .home__parrafo {
                font-size: 1.1rem;
            }

            @media (max-width: 575.98px) {
                .card__rsistanc {
                    padding: 1.2rem;
                    border-radius: 15px;
                }

                .hero {

                    background:
                        linear-gradient(88deg, #B66F37 10.73%, rgba(157, 90, 169, 0.90) 48.98%, rgba(181, 130, 190, 0.70) 80.44%, rgba(255, 255, 255, 0.00) 150%),
                        url({{ asset('image/pages/banner1.png') }});

                    background-size: cover;
                    background-position: start;

                }

                .home__title {
                    font-size: 1.2rem;
                    font-weight: 900;
                }

                .home__parrafo {
                    font-size: .9rem;
                }

                .card__benef {
                    min-height: 100px;
                    color: #fff
                }

                .direccion__detl img {
                    height: 25px;
                    width: 25px;
                }
            }




            /* Large devices (desktops, 992px and up) */
            @media (max-width: 992px) {
                /* Estilos para escritorios */
            }
        </style>
    @endpush

    {{-- Hero --}}
    <section class="hero h-[calc(100vh-8rem)] items-center justify-center content-center pt-28 md:pt-6 lg:pt-6">
        <div class="container ">

            <div data-aos="fade-up" class="grid gap-4 lg:gap-8 w-full  lg:w-9/12 text-white">
                <h1
                    class="text-center md:text-start justify-center sm:justify-start font-two text-[1.6rem] md:text-4xl lg:text-6xl  font-bold tracking-[6px] lg:tracking-[8px] flex flex-wrap ">
                    TRAIN
                    <span class="font-light">YOUR
                    </span>RSISTANC.
                    <span class="font-light">LIVE</span> UNSTOPPABLE.
                </h1>

                <p class="font-two text-center md:text-start text-xl md:text-2xl lg:text-2xl lg:tracking-[.2rem] max-w-[650px] leading-[1.7]"
                    style="font-weight: 200;">Clases que te transforman. Energía
                    que te eleva. Una comunidad que te empuja
                    a más.</p>

                <div class="grid md:flex flex-wrap gap-4 ">

                    <a href="{{ route('package') }}" class="btn btn__one">EMPIEZA HOY</a>
                    <a href="#descarga" class="btn btn__two">RESERVA TU CLASE DE PRUEBA</a>
                </div>
            </div>
        </div>
    </section>


    <main class="main flex flex-col gap-8 py-8">



        {{-- Disciplinas --}}
        @if ($disciplines->count() > 0)
            <section class="section " id="disciplinas">
                <div class="container">
                    <div data-aos="fade-up-left" class="bg-white card__rsistanc">

                        <h2 class="home__title title__color text-two"><span class="font-normal">ELIGE CÓMO
                                QUIERES</span> MOVERTE
                        </h2>

                        <div class="swiper disciplinesSwiper">
                            <div class="swiper-wrapper">
                                @foreach ($disciplines as $index => $discipline)
                                    <div class="swiper-slide card__discipline"
                                        style="background: url({{ $discipline->image_url ? 'storage/' . $discipline->image_url : asset('default/discipline.png') }});           background-position: center;
                background-repeat: no-repeat;
                background-size: cover;">
                                        <div class="grid gap-1">
                                            <div class="flex items-center gap-2">
                                                <img class="discipline__icon"
                                                    src="{{ $discipline->icon_url ? 'storage/' . $discipline->icon_url : asset('image/logos/logoBlancoR.svg') }}"
                                                    alt="logo">
                                                <h3 class="uppercase  text-xl text-two discipline__title">
                                                    {{ $discipline->name }}</h3>
                                            </div>
                                            @if ($discipline->description)
                                                <p class="discipline__description">{{ $discipline->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Navegación -->
                            {{-- <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div> --}}

                            <!-- Paginación -->
                            {{-- <div class="swiper-pagination"></div> --}}
                        </div>
                    </div>

                </div>
            </section>
        @endif

        {{-- Paquetes y Beneficios --}}
        <section class="section section-packages" id="paquetes">
            <div class="container">
                <div class="flex flex-wrap gap-6">
                    <div data-aos="fade-down-right"
                        class="card__benef card__benef--one card__rsistanc grid  gap-1 md:gap-3 justify-start justify-items-start content-end w-full lg:max-w-96">
                        <h3 class="home__title leading-tight font-extralight" style="font-weight: 100;">PAQUETES <br>
                            QUE SE ADAPTAN <span class="grid  home__title">A TU RITMO.</span></h3>

                        <p class="dark text-base lg:text-lg font-medium home__parrafo">Desde 1 hasta 40 clases.</p>
                        <div class="font-light text-lg">
                            <p class="home__parrafo">Mixea disciplinas, suma puntos,
                                sube de nivel.</p>
                        </div>
                        <a href="{{ route('package') }}" class="font-extrabold text-xl">VER PAQUETES →</a>
                    </div>
                    <div data-aos="fade-down-left"
                        class="card__benef card__benef--two flex-1 bg-slate-500 card__rsistanc grid gap-3 justify-start justify-items-start content-end ">
                        <h3 class="home__title font-extrabold leading-tight"><span class="font-light">MÁS</span>
                            RESISTANCE, <span class="font-light">MÁS</span> REWARDS.</h3>

                        <p class="dark text-lg font-medium">Entrenar tiene beneficios reales:</p>
                        <div class="package-details">
                            <p><span class="font-light">Early access, descuentos y shakes gratis</span></p>
                            <p class="font-extrabold"><span class="font-light">alcanzando la categoría </span>GOLD y
                                BLACK.</p>
                        </div>
                        <a href="{{ route('package') }}" class="font-extrabold text-xl ">VER BENEFICIOS →</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Servicios --}}
        @if ($services->count() > 0)
            <section class="section" id="servicios">
                <div class="container">
                    <div data-aos="fade-up" class="services grid gap-4 bg-white card__rsistanc">
                        <div class="grid gap-2">
                            <h2 class="home__title title__color uppercase text-two">Servicios
                            </h2>

                            <h3 class="font-bold home__parrafo text-two">
                                <span class="font-light">Explora lo que hace única tu experiencia en</span> R
                                STUDIO<span class="font-light">, dentro y fuera del training floor.</span>
                            </h3>
                        </div>

                        {{-- Contenedor estilo historias de WhatsApp --}}
                        <div class="relative">
                            <div class="overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4"
                                    style="scroll-snap-type: x mandatory; scroll-behavior: smooth;">
                                    @foreach ($services as $service)
                                        @php
                                            // Limpiar el path de la imagen
                                            $imagePath = $service->image ? trim($service->image, '/') : null;
                                            $backgroundImage = $imagePath
                                                ? asset('storage/' . $imagePath)
                                                : asset('default/discipline.png');
                                        @endphp

                                        <div class="relative flex-shrink-0 service__card rounded-3xl overflow-hidden shadow-lg"
                                            style="
                                                background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5)), url('{{ $backgroundImage }}');
                                                background-position: center;
                                                background-repeat: no-repeat;
                                                background-size: cover;
                                                scroll-snap-align: start;
                                            ">
                                            {{-- Contenido superpuesto --}}
                                            <div class="absolute inset-0 flex flex-col justify-end p-6 text-white z-10">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <img src="/image/logos/logoBlancoR.svg" alt="logo"
                                                        class="w-8 h-8 object-contain">
                                                    <h3 class="text-xl font-bold">{{ $service->title }}</h3>
                                                </div>
                                                @if ($service->description)
                                                    <p class="text-sm font-light leading-relaxed line-clamp-3">
                                                        {{ $service->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif


        {{-- @if ($services->count() > 0)
            <section class="section" id="servicios">
                <div class="container">
                    <div data-aos="fade-up" class="services grid gap-4 bg-white card__rsistanc">
                        <div class="grid gap-2">
                            <h2 class="text-2xl font-extrabold">SERVICIOS</h2>
                            <h3 class="font-bold text-lg">
                                <span class="font-light">Explora lo que hace única tu experiencia en</span> R
                                STUDIO<span class="font-light">, dentro y fuera del training floor.</span>
                            </h3>
                        </div>


                        @php

                            $columns = 4;
                            $servicesChunks = [];


                            foreach ($services as $index => $service) {
                                $colIndex = $index % $columns;
                                if (!isset($servicesChunks[$colIndex])) {
                                    $servicesChunks[$colIndex] = [];
                                }
                                $servicesChunks[$colIndex][] = $service;
                            }
                        @endphp

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @for ($col = 0; $col < $columns; $col++)
                                <div class="grid gap-4">
                                    @if (isset($servicesChunks[$col]))
                                        @foreach ($servicesChunks[$col] as $serviceIndex => $service)
                                            @php

                                                $imagePath = $service->image ? trim($service->image, '/') : null;
                                                $backgroundImage = $imagePath
                                                    ? asset('storage/' . $imagePath)
                                                    : asset('default/discipline.png');


                                                $heights = [300, 250, 280, 320, 270];
                                                $height = $heights[$serviceIndex % count($heights)] . 'px';
                                            @endphp

                                            <div class="relative rounded-lg overflow-hidden shadow-lg transition-transform "
                                                style="
                                                    min-height: {{ $height }};
                                                    background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5)), url('{{ $backgroundImage }}');
                                                    background-position: center;
                                                    background-repeat: no-repeat;
                                                    background-size: cover;
                                                ">

                                                <div
                                                    class="absolute inset-0 flex flex-col justify-end p-4 text-white z-10">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <img src="/image/logos/logoBlancoR.svg" alt="logo"
                                                            class="w-6 h-6 object-contain">
                                                        <h3 class="text-lg font-bold">{{ $service->title }}</h3>
                                                    </div>
                                                    @if ($service->description)
                                                        <p class="text-xs font-light leading-relaxed line-clamp-2">
                                                            {{ $service->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </section>
        @endif --}}

        {{-- Descarga --}}
        <section class="section" id="descarga">
            <div class="container">

                <div
                    class="grid grid-cols-1 xl:grid-cols-2 justify-center content-center justify-items-center items-center gap-6 bg-white card__rsistanc">


                    <img data-aos="fade-up" class="store__img" src="/image/pages/vistaCel.svg" alt="descarga">
                    <div data-aos="fade-up" class="grid gap-2">
                        <h2 class="home__title text-two">TU RSISTANC <span class="font-light">VA CONTIGO.</span>
                        </h2>

                        <h4 class="font-extralight home__parrafo text-two ">Reserva, compra, suma puntos y ve tu
                            progreso desde
                            nuestra
                            app.</h4>
                        <h3 class="font-bold text-lg">Simple, rápida, tuya.</h3>

                        <div class="flex flex-wrap gap-3 mt-1">
                            <a href="">
                                <img src="/image/logos/iconos/ios.svg" alt="ios">
                            </a>

                            <a href="">
                                <img src="/image/logos/iconos/android.svg" alt="android">
                            </a>


                        </div>
                    </div>
                </div>
            </div>
        </section>

        @php
            $company = \App\Models\Company::first();
        @endphp

        {{-- Direccion --}}
        <section class="section" id="direccion">
            <div class="container">
                <div class="flex gap-6 flex-wrap items-center bg-white card__rsistanc">
                    <div data-aos="fade-right" class="flex-1 grid gap-2 md:gap-4">
                        <div class="text-2xl lg:text-5xl grid gap-2">
                            <h1><span class="home__title">ENCUENTRANOS: </span></h1>
                            {{-- <div class="flex gap-3 font-extrabold">
                                <img class="w-10" src="/image/logos/iconos/logor.svg" alt="logo">
                                <h2>STUDIO</h2>
                            </div> --}}
                        </div>

                        <p class="home__parrafo text-two mb-2 md:mb-0">Ubicado en Surco, diseñado para que te muevas libre y con
                            flow.</p>

                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconomapa.svg" alt="mapa">
                            <span class="home__parrafo"> {{ $company->address }} </span>
                        </div>
                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconocel.svg" alt="celular">
                            <span class="home__parrafo"> {{ $company->phone_help }}</span>
                        </div>
                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconomail.svg" alt="correo">
                            <span class="home__parrafo">{{ $company->email }}</span>
                        </div>
                    </div>
                    <a data-aos="fade-left"
                        href="https://www.google.com/maps?q=Avenida+Surco+123,+Santiago+de+Surco,+Lima,+Perú"
                        target="_blank" rel="noopener noreferrer">
                        <img src="/image/pages/mapa.svg" alt="Mapa de Studio" class="mapaStudio">
                    </a>
                </div>

            </div>
        </section>

        {{-- FAQ --}}
        <section class="section" id="faq">
            <div class="container">
                <div class="faqcontainer card__rsistanc">

                    <div class="text-center grid gap-3 mb-5">
                        <h2 class="font-semibold text-two home__title">FAQs</h2>
                        <p class="font-light home__parrafo text-two">¿Tienes dudas? Resolvemos todo.</p>
                    </div>
                    <div class="faq-container grid gap-3">

                        @foreach ($faqs as $faq)
                            <details data-aos="flip-up" class="p-5 bg-white rounded-2xl">
                                <summary class=" title__color home__parrafo text-two font-semibold text-md">
                                    {{ $faq->question }}
                                </summary>
                                <div class="faq-answer text-two title__color font-light text-sm mt-3">
                                    {{ $faq->answer }}
                                </div>
                            </details>
                        @endforeach
                    </div>

                </div>
            </div>
        </section>

    </main>


    @push('js')
        <!-- Swiper JS -->
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inicializar Swiper para las disciplinas
                const swiper = new Swiper('.disciplinesSwiper', {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: true,
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    breakpoints: {

                        562: {
                            slidesPerView: 1,
                            spaceBetween: 13,
                        },

                        700: {
                            slidesPerView: 2,
                            spaceBetween: 13,
                        },

                        840: {
                            slidesPerView: 3,
                            spaceBetween: 13,
                        },
                        1200: {
                            slidesPerView: 4,
                            spaceBetween: 13,
                        },
                    },
                });
            });
        </script>
    @endpush




</x-app>
