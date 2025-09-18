<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSISTANC - Train Your Resistance</title>

    <!-- Vite para CSS/JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-one), -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #333;
        }

        .container {
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        /* HEADER */
        .header {
            background-image: url('{{ asset('image/pages/banner1.png') }}');
            background-size: cover;
            background-position: 80% center;
            color: white;
            overflow: hidden;
            position: relative;
            height: 95vh;
            display: flex;
            align-items: center;
            margin-top: -70px;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(88deg, #B66F37 20%, rgba(157, 90, 169, 0.90) 40%, rgba(181, 130, 190, 0.70) 60.44%, rgba(255, 255, 255, 0.00) 80%);
            z-index: 1;
        }

        .header > * {
            position: relative;
            z-index: 2;
        }

        .header__title {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 200;
            text-transform: uppercase;
            line-height: 1.1;
            margin: 0;
        }

        .header__title strong {
            font-weight: 700;
        }

        .header__subtitle {
            font-size: 1.2rem;
            font-weight: 300;
            margin: 1rem 0;
            max-width: 500px;
        }

        /* BOTONES */
        .btn-primary,
        .btn-secondary {
            padding: 16px 32px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .btn-primary:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border-color: rgba(255, 255, 255, 0.8);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            transform: translateY(-2px);
        }

        /* MAIN */
        .main {
            background: linear-gradient(94deg, #CDD6D7 0%, #D9D9D2 30.29%, #E7DFE9 49.52%, #E5D7EA 71.15%, #E7D4D8 100%);
            padding: 3rem 0;
            display: grid;
            gap: 3rem;
        }

        /* DISCIPLINAS */
        .disciplinas__title {
            font-size: 2.5rem;
            font-weight: 400;
            text-transform: uppercase;
            font-family: var(--font-one);
            margin-bottom: 1.5rem;
            color: #5D6D7A;
        }

        .disciplina__card {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            display: grid;
            align-content: flex-end;
            color: white;
            padding: 1rem;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .disciplina__card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .disciplina__card > * {
            position: relative;
            z-index: 2;
        }

        .disciplina__card--title {
            font-size: 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            margin: 0;
        }

        .disciplina__card--paragrahp {
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            height: 75px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        /* BANNER */
        .banner__content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            height: 350px;
        }

        .banner__one {
            background: url('{{ asset('image/pages/banner_one.png') }}') center/cover;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 1rem;
            border-radius: 15px;
            color: white;
        }

        .banner__title {
            font-size: 2rem;
            font-weight: 300;
            line-height: 1.2;
            text-transform: uppercase;
            font-family: var(--font-one);
        }

        .banner__title strong {
            font-weight: 700;
        }

        .banner__subtitle {
            font-size: 1rem;
            font-weight: 200;
            line-height: 1.2;
            font-family: var(--font-one);
        }

        /* APPS */
        .apps__content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
        }

        .apps__texts--title {
            font-size: 3rem;
            font-weight: 300;
            line-height: 1.2;
            color: #5D6D7A;
            text-transform: uppercase;
            font-family: var(--font-one);
        }

        .apps__texts--title strong {
            font-weight: 700;
        }

        .apps__texts p {
            font-size: 1.2rem;
            font-weight: 400;
            line-height: 1.5;
            color: #5D6D7A;
        }

        .apps__text--app {
            font-size: 0.8rem !important;
            font-weight: 400;
            color: #5D6D7A;
        }

        .apps__img img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* FAQ */
        .preguntas__title {
            font-size: 2.5rem;
            font-weight: 400;
            text-transform: uppercase;
            font-family: var(--font-one);
            margin-bottom: 1rem;
            color: #5D6D7A;
            text-align: center;
        }

        .preguntas__content {
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.24);
            border-radius: 12px;
            border: 1px solid rgba(209, 213, 219, 0.3);
            padding: 2rem;
        }

        .pregunta__item {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .pregunta__button {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            color: #5D6D7A;
            text-align: left;
            transition: all 0.3s ease;
            font-family: var(--font-one);
        }

        .pregunta__button:hover,
        .pregunta__button--active {
            color: #B66F37;
            font-weight: 600;
        }

        .pregunta__icon {
            transition: transform 0.3s ease;
            color: #5D6D7A;
        }

        .pregunta__icon--active {
            transform: rotate(180deg);
            color: #B66F37;
        }

        .pregunta__content {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            color: #6B7280;
            line-height: 1.6;
            font-size: 1rem;
            margin-top: 1rem;
        }

        /* SWIPER CUSTOM */
        .swiper-button-next,
        .swiper-button-prev {
            color: #B66F37;
            background: rgba(255, 255, 255, 0.9);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: #B66F37;
            color: white;
        }

        .swiper-pagination-bullet {
            background: #B66F37;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
            background: #B66F37;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header__title {
                font-size: 3rem;
            }

            .banner__content {
                grid-template-columns: 1fr;
                height: auto;
            }

            .apps__content {
                grid-template-columns: 1fr;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                max-width: 280px;
                margin: 0.5rem auto;
            }
        }
    </style>
</head>
<body>
    @include('layouts.partials.navigate')
    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <div class="header__content">
                <div class="header__text">
                    <h1 class="header__title"><strong>Train</strong> your <strong>RSISTANC.</strong><br>Live <strong>unstoppable.</strong></h1>
                    <p class="header__subtitle">Clases que te transforman. Energía que te eleva. Una comunidad que te empuja a más.</p>
                    <div class="cta-buttons" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 2rem;">
                        <a href="#" class="btn-primary">EMPIEZA HOY</a>
                        <a href="#" class="btn-secondary">RESERVA TU CLASE DE PRUEBA</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- DISCIPLINAS -->
        <section class="disciplinas">
            <div class="container">
                <div class="disciplinas__content">
                    <h2 class="disciplinas__title">Elige cómo quieres <strong>moverte.</strong></h2>

                    <!-- Swiper -->
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                            @foreach ($disciplines as $item)
                                <div class="swiper-slide">
                                    <div class="disciplina__card"
                                        style="background-image: url('{{ $item->image_url ?? asset('image/pages/cycling.png') }}');">
                                        <div class="flex gap-2 items-center">
                                            <img class="w-5 h-5" src="{{ asset('image/pages/logo.png') }}" alt="Logo">
                                            <h3 class="disciplina__card--title">{{ $item->name }}</h3>
                                        </div>
                                        <p class="disciplina__card--paragrahp">{{ $item->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- BANNER -->
        <section class="banner">
            <div class="container">
                <div class="banner__content">
                    <div class="banner__one">
                        <h2 class="banner__title">Paquetes <br> que se adaptan <strong> a tu ritmo.</strong></h2>
                        <p class="banner__subtitle"><strong>Desde 1 hasta 40 clases.</strong></p>
                        <p class="banner__subtitle">Mixea disciplinas, suma puntos, sube de nivel.</p>
                        <a href="#" class="text-white font-bold text-lg">VER PAQUETES</a>
                    </div>
                    <div class="banner__one">
                        <h2 class="banner__title">Paquetes <br> que se adaptan <strong> a tu ritmo.</strong></h2>
                        <p class="banner__subtitle"><strong>Desde 1 hasta 40 clases.</strong></p>
                        <p class="banner__subtitle">Mixea disciplinas, suma puntos, sube de nivel.</p>
                        <a href="#" class="text-white font-bold text-lg">VER PAQUETES</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- APPS -->
        <section class="apps">
            <div class="container">
                <div class="apps__content">
                    <div class="apps__img">
                        <img src="{{ asset('image/pages/mockups.png') }}" alt="App RSISTANC">
                    </div>
                    <div class="apps__texts">
                        <h3 class="apps__texts--title"><strong>Tu RSISTANC</strong><br> va contigo.</h3>
                        <p>Reserva, compra, suma puntos y ve tu progreso<br> desde nuestra app.</p>
                        <h4 class="text-lg font-bold text-gray-600">Simple, rápida, tuya.</h4>
                        <div class="apps__buttons flex gap-4 mt-4">
                            <a href="#" class="flex gap-2 items-center p-1.5 border border-gray-300 rounded-lg">
                                <img class="w-5 h-5" src="{{ asset('image/pages/apple.png') }}" alt="App Store">
                                <p class="apps__text--app">Descargala en <br> <strong>App Store</strong></p>
                            </a>
                            <a href="#" class="flex gap-2 items-center p-1.5 border border-gray-300 rounded-lg">
                                <img class="w-5 h-5" src="{{ asset('image/pages/Playstore.png') }}" alt="Google Play">
                                <p class="apps__text--app">Descargala en <br> <strong>Google Play</strong></p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="preguntas">
            <div class="container">
                <div class="preguntas__content">
                    <div class="mb-5 text-center">
                        <h2 class="preguntas__title"><strong>FAQs</strong></h2>
                        <p class="text-gray-600 text-lg">¿Tienes dudas? Resolvemos todo.</p>
                    </div>

                    <div class="preguntas__accordion" id="faqAccordion">
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="pregunta__item" data-faq-item="{{ $i }}">
                                <button class="pregunta__button" data-faq-button="{{ $i }}">
                                    <span>Pregunta de ejemplo {{ $i }}?</span>
                                    <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="pregunta__content" data-faq-content="{{ $i }}">
                                    <p>Respuesta de ejemplo para la pregunta {{ $i }}. Este es un texto de relleno para mostrar cómo se expande el contenido cuando haces clic en la pregunta.</p>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </section>

    </main>
@include('layouts.partials.footerapp')
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Swiper
            var swiper = new Swiper(".mySwiper", {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: false,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                breakpoints: {
                    640: { slidesPerView: 2, spaceBetween: 20 },
                    768: { slidesPerView: 3, spaceBetween: 20 },
                    1024: { slidesPerView: 4, spaceBetween: 20 },
                },
            });

            // Inicializar FAQ
            const accordion = document.getElementById('faqAccordion');
            const buttons = accordion.querySelectorAll('[data-faq-button]');
            let activeItem = null;

            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-faq-button');
                    const content = document.querySelector(`[data-faq-content="${itemId}"]`);
                    const icon = this.querySelector('.pregunta__icon');

                    if (activeItem === itemId) {
                        content.style.maxHeight = '0px';
                        content.style.opacity = '0';
                        content.style.transform = 'translateY(-10px)';
                        this.classList.remove('pregunta__button--active');
                        icon.classList.remove('pregunta__icon--active');
                        activeItem = null;
                    } else {
                        if (activeItem) {
                            const prevContent = document.querySelector(`[data-faq-content="${activeItem}"]`);
                            const prevButton = document.querySelector(`[data-faq-button="${activeItem}"]`);
                            const prevIcon = prevButton.querySelector('.pregunta__icon');
                            prevContent.style.maxHeight = '0px';
                            prevContent.style.opacity = '0';
                            prevContent.style.transform = 'translateY(-10px)';
                            prevButton.classList.remove('pregunta__button--active');
                            prevIcon.classList.remove('pregunta__icon--active');
                        }
                        content.style.maxHeight = content.scrollHeight + 'px';
                        content.style.opacity = '1';
                        content.style.transform = 'translateY(0)';
                        this.classList.add('pregunta__button--active');
                        icon.classList.add('pregunta__icon--active');
                        activeItem = itemId;
                    }
                });
            });
        });
    </script>

</body>
</html>