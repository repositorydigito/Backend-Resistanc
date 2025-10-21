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

                height: 300px;
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

            .discipline__description {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                font-size: 1rem;
                font-weight: 200;
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
            }

            .direccion__detl span {
                font-size: 1.2rem;
                font-weight: 100;
            }

            .card__services {
                height: 250px;
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
                background: rgba(0, 0, 0, 0.301);
                z-index: 10;
            }

            .card__services > * {
                  position: relative;
                z-index: 11;
            }

            .servicescard__title {
                display: flex;
                /* gap: .7rem; */
                font-size: 1.9rem;
                font-weight: 600;


            }

            .faqcontainer{
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
        </style>
    @endpush

    {{-- Hero --}}
    <section class="hero h-[calc(100vh-8rem)] items-center justify-center content-center pt-6">
        <div class="container ">

            <div data-aos="fade-up" class="grid gap-8 w-full  lg:w-8/12 text-white">
                <h1 class="text-6xl  font-extrabold tracking-[8px]">TRAIN <span class="font-light">YOUR </span>RSISTANC.
                    <span class="font-light">LIVE</span> UNSTOPPABLE.
                </h1>
                <p class="text-2xl max-w-xl">Clases que te transforman. Energía que te eleva. Una comunidad que te empuja
                    a más.</p>


                <div class="flex gap-4 ">

                    <a href="#membresias" class="btn btn__one">EMPIEZA HOY</a>
                    <a href="#disciplinas" class="btn btn__two">RESERVA TU CLASE DE PRUEBA</a>
                </div>


            </div>



        </div>
    </section>


    <main class="main flex flex-col gap-8 py-8">



        {{-- Disciplinas --}}
        @if ($disciplines->count() > 0)
            <section class="section " id="disciplinas">
                <div class="container">
                    <div data-aos="fade-up-left" class="bg-white p-9 rounded-3xl">

                        <h2 class="text-2xl font-extrabold"><span class="font-normal">ELIGE CÓMO QUIERES</span> MOVERTE
                        </h2>

                        <div class="swiper disciplinesSwiper">
                            <div class="swiper-wrapper">
                                @foreach ($disciplines as $index => $discipline)
                                    <div class="swiper-slide card__discipline"
                                        style="background: url({{ $discipline->image_url ?? asset('default/discipline.png') }});           background-position: center;
                background-repeat: no-repeat;
                background-size: cover;">
                                        <div class="grid gap-3">
                                            <div class="flex items-center gap-2">
                                            <img src="{{ $discipline->icon_url ?? asset('image/logos/logoBlancoR.svg') }}" alt="logo">
                                                <h3 class="uppercase font-bold text-lg">{{ $discipline->name }}</h3>
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
                        class="card__benef card__benef--one p-8 rounded-3xl grid gap-3 justify-start justify-items-start content-end w-full lg:max-w-96">
                        <h3 class="text-4xl font-light leading-tight">PAQUETES <br> QUE SE ADAPTAN <span
                                class="grid font-extrabold">A TU RITMO.</span></h3>

                        <p class="dark text-lg font-medium">Desde 1 hasta 40 clases.</p>
                        <div class="font-light text-lg">
                            <p>Mixea disciplinas, suma puntos,
                                sube de nivel.</p>
                        </div>
                        <a href="#membresias" class="font-extrabold text-xl">VER PAQUETES →</a>
                    </div>
                    <div data-aos="fade-down-left"
                        class="card__benef card__benef--two flex-1 bg-slate-500 rounded-3xl p-8 grid gap-3 justify-start justify-items-start content-end ">
                        <h3 class="text-4xl font-extrabold leading-tight"><span class="font-light">MÁS</span>
                            RESISTANCE, <span class="font-light">MÁS</span> REWARDS.</h3>

                        <p class="dark text-lg font-medium">Entrenar tiene beneficios reales:</p>
                        <div class="package-details">
                            <p><span class="font-light">Early access, descuentos y shakes gratis</span></p>
                            <p class="font-extrabold"><span class="font-light">alcanzando la categoría </span>GOLD y
                                BLACK.</p>
                        </div>
                        <a href="#beneficios" class="font-extrabold text-xl ">VER BENEFICIOS →</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Servicios --}}
        @if ($services->count() > 0)
            <section class="section" id="servicios">
                <div class="container">
                    <div data-aos="fade-up" class="services grid gap-2 bg-white p-9 rounded-3xl ">
                        <h2 class="text-2xl font-extrabold">SERVICIOS </h2>
                        <h3 class="font-bold text-lg"><span class="font-light">Explora lo que hace única tu experiencia
                                en</span> R
                            STUDIO<span class="font-light">, dentro y fuera del training floor.</span></h3>
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            @foreach ($services as $service)
                                <div class="card__services" style="background: url({{ $service->image_url ?? asset('default/discipline.png') }}); background-position: center;
                background-repeat: no-repeat;
                background-size: cover;">
                                    <div class="servicescard__title flex gap-3 items-center">
                                        <img src="/image/logos/logoBlancoR.svg" alt="logo">
                                        <h3>{{ $service->title }}</h3>
                                    </div>
                                    @if ($discipline->description)
                                        <p>{{ $service->description }}</p>
                                    @endif

                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </section>
        @endif

        {{-- Descarga --}}
        <section class="section" id="descarga">
            <div class="container">

                <div
                    class="grid grid-cols-1 xl:grid-cols-2 justify-center content-center justify-items-center items-center gap-6 bg-white p-9 rounded-3xl ">


                    <img data-aos="fade-up" class="store__img" src="/image/pages/vistaCel.svg" alt="descarga">
                    <div data-aos="fade-up" class="grid gap-2">
                        <h2 class="text-4xl font-extrabold">TU RSISTANC <span class="font-light">VA CONTIGO.</span></h2>
                        <h3><span class="text-xl font-light">Reserva, compra, suma puntos y ve tu progreso desde nuestra
                                app.</span>
                        </h3>
                        <h3 class="font-bold text-lg">Simple, rápida, tuya.</h3>

                        <div class="flex gap-3 mt-5">
                            <img src="/image/logos/iconos/ios.svg" alt="ios">
                            <img src="/image/logos/iconos/android.svg" alt="android">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Direccion --}}
        <section class="section" id="direccion">
            <div class="container">
                <div class="flex gap-6 flex-wrap items-center bg-white p-9 rounded-3xl">
                    <div data-aos="fade-right" class="flex-1 grid gap-4">
                        <div  class="text-5xl grid gap-2">
                            <h1><span class="font-extralight">ENCUENTRA</span></h1>
                            <div class="flex gap-3 font-extrabold">
                                <img class="w-10" src="/image/logos/iconos/logor.svg" alt="logo">
                                <h2>STUDIO</h2>
                            </div>
                        </div>

                        <p class="text-xl">Ubicado en Surco, diseñado para que te muevas libre y con flow.</p>

                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconomapa.svg" alt="mapa">
                            <span class="light">Avenida Surco 123, Santiago de Surco, Lima, Perú</span>
                        </div>
                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconocel.svg" alt="celular">
                            <span class="light">+51 966532455</span>
                        </div>
                        <div class="direccion__detl">
                            <img src="/image/logos/iconos/iconomail.svg" alt="correo">
                            <span class="light">hola@rsistanc.com</span>
                        </div>
                    </div>
                    <a data-aos="fade-left" href="https://www.google.com/maps?q=Avenida+Surco+123,+Santiago+de+Surco,+Lima,+Perú"
                        target="_blank" rel="noopener noreferrer">
                        <img src="/image/pages/mapa.svg" alt="Mapa de Studio" class="mapaStudio">
                    </a>
                </div>

            </div>
        </section>

        {{-- FAQ --}}
        <section class="section" id="faq">
            <div class="container">
                <div class="faqcontainer p-9 rounded-3xl">

                    <div class="text-center grid gap-3 mb-5">
                        <h2 class="font-semibold text-3xl">FAQs</h2>
                        <p class="font-light text-lg">¿Tienes dudas? Resolvemos todo.</p>
                    </div>
                    <div class="faq-container grid gap-3">

                        @foreach ($faqs as $faq)
                            <details data-aos="flip-up" class="p-5 bg-white rounded-2xl">
                                <summary class="font-semibold text-lg">
                                    {{ $faq->question }}
                                </summary>
                                <div class="faq-answer font-light">
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
                            spaceBetween: 20,
                        },

                        700: {
                            slidesPerView: 2,
                            spaceBetween: 20,
                        },

                        840: {
                            slidesPerView: 3,
                            spaceBetween: 20,
                        },
                        1200: {
                            slidesPerView: 4,
                            spaceBetween: 20,
                        },
                    },
                });
            });
        </script>
    @endpush




</x-app>
