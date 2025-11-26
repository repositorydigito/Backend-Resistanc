<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rsistanc - Entrenamiento Premium y Clases Fitness</title>
    <meta name="description"
        content="Rsistanc ofrece entrenamiento premium, clases de fitness y servicios de bienestar. Únete a nuestra comunidad y transforma tu vida.">
    <meta name="keywords"
        content="fitness, entrenamiento, gym, resistencia, clases grupales, bienestar, salud, ejercicio">
    <meta name="author" content="Rsistanc">

    <!-- Favicon y iconos para múltiples dispositivos -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('image/pages/icon_nuevo.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('image/pages/icon_nuevo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('image/pages/icon_nuevo.png') }}">
    <link rel="manifest" href="{{ asset('image/favicon/site.webmanifest') }}">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <!-- Open Graph para redes sociales -->
    <meta property="og:title" content="Rsistanc - Entrenamiento Premium y Clases Fitness">
    <meta property="og:description"
        content="Únete a Rsistanc y transforma tu vida con nuestro entrenamiento premium y clases de fitness.">
    <meta property="og:image" content="{{ asset('image/logos/og-image.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Rsistanc">
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Rsistanc - Entrenamiento Premium y Clases Fitness">
    <meta name="twitter:description"
        content="Únete a Rsistanc y transforma tu vida con nuestro entrenamiento premium y clases de fitness.">
    <meta name="twitter:image" content="{{ asset('image/logos/twitter-image.jpg') }}">

    <!-- Verificación de Google -->
    <meta name="google-site-verification" content="39QUCmfjodGhkNM6wIR8EJkohPvkXKwGHRqlKxCduRo" />

    <!-- Estilos personalizados (después de Tailwind) -->
    {{-- <link rel="stylesheet" href="{{ asset('css/styles.css') }}"> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">


    {{-- aos scroll --}}

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    {{-- Fin aos scroll --}}

    {{-- @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
        </style>
    @endif --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('css')

    <style>
        * {
            /* outline: 1px solid red; */
        }

        .footer__title {
            display: grid;
            justify-content: start;
            align-content: flex-start;
            text-align: start;
            /* gap: .8rem; */
        }

        .footer__title h4 {
            font-weight: 500;
            font-size: 1.2rem
        }

        .footer__title ul {
            display: grid;
            gap: .4rem;
        }

        .footer__title ul li a {
            /* color: white; */
            text-decoration: none;
            font-size: .9rem;
            transition: all 0.3s ease;
        }

        .social-icons {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
        }

        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            /* padding: .9rem; */
            background: rgb(0, 0, 0);
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-icons a i {
            font-size: .9rem;
        }

        .social-icons a:hover {
            background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
            transform: translateY(-2px);
        }

        @media (max-width: 575.98px) {

            .footer__title {
                display: grid;
                justify-content: center;
                text-align: center;
                /* gap: .8rem; */
            }

        }
    </style>

</head>

<body class="background__primary overflow-x-hidden">

    @php
        $company = \App\Models\Company::first();
    @endphp

    <header class="header py-2 absolute top-1 left-0 w-full border-white border-b">
        <div class="container contenido">
            <nav data-aos="fade-right"
                class=" py-3 lg:p-0 grid justify-center md:flex md:justify-between items-center gap-4">
                <a href="{{ route('home') }}" class="logo">
                    <img src="{{ asset('image/pages/logo-web.png') }}" alt="Resistance Logo" class="h-8">
                </a>

                <a href="{{ route('package') }}" class="btn btn__one">EMPIEZA HOY</a>

            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="footer">
        <div class="container">
            <div class="grid ">


                <div data-aos="fade-left" class="grid gap-4  grid-cols-1 md:grid-cols-2 lg:grid-cols-4 py-12">
                    <div class="footer-logo grid justify-center content-start">
                        <img class="max-w-64" src="{{ asset('image/logos/logorsistanc.svg') }}" alt="RSISTANC Logo">
                        <div class="social-icons flex flex-wrap">
                            @if ($company->instagram_url)
                                <a class="bg-black" href="{{ $company->instagram_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            @endif

                            @if ($company->facebook_url)
                                <a class="bg-black" href="{{ $company->facebook_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-facebook"></i>
                                </a>
                            @endif

                            @if ($company->tiktok_url)
                                <a class="bg-black" href="{{ $company->tiktok_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                            @endif

                            @if ($company->youtube_url)
                                <a class="bg-black" href="{{ $company->youtube_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            @endif

                            @if ($company->linkedin_url)
                                <a class="bg-black" href="{{ $company->linkedin_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            @endif

                            @if ($company->twitter_url)
                                <a class="bg-black" href="{{ $company->twitter_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            @endif

                            @if ($company->whatsapp_url)
                                <a class="bg-black" href="{{ $company->whatsapp_url }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            @endif


                        </div>
                    </div>

                    <div class="footer__title">
                        <h4>R Studio</h4>
                        <ul>
                            <li><a href="#">Reservar</a></li>
                            <li><a href="#">Paquetes</a></li>
                            <li><a href="#">Servicios</a></li>
                            {{-- <li><a href="#">Rewards</a></li> --}}
                            <li><a href="#">Clase de Prueba</a></li>
                        </ul>
                    </div>

                    <div class="footer__title">
                        <h4>Links</h4>
                        <ul>
                            {{-- <li><a href="#">R Workshops</a></li>
                        <li><a href="#">R Business & Events</a></li>
                        <li><a href="#">R Recovery</a></li>
                        <li><a href="#">R Shop</a></li> --}}
                            <li><a href="#">FAQs</a></li>
                        </ul>
                    </div>

                    <div class="footer__title">
                        <h4>App</h4>
                        <ul>
                            <li><a href="#">iOS</a></li>
                            <li><a href="#">Android</a></li>
                        </ul>
                    </div>
                </div>

                @php
                    $currentYear = date('Y');
                @endphp

                <div class="text-center flex flex-wrap justify-center lg:justify-between py-2 items-center">
                    <p class="">&copy; {{ $currentYear }} RSISTANC. Todos los derechos reservados.</p>
                    <p class=""><a class="rsistanc__enlace" href="{{ route('privacity') }}">Políticas de
                            Privacidad</a> | <a class="rsistanc__enlace" href="{{ route('term') }}">Términos y
                            Condiciones</a>
                    </p>
                </div>

            </div>
        </div>
    </footer>

    @stack('js')

    {{-- aos scroll --}}

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    {{-- Fin aos scroll --}}

    <script>
        AOS.init({
            once: true, // Esta es la opción clave - la animación solo ocurrirá una vez
            duration: 800, // Duración de la animación en ms
            offset: 100, // Cuándo se debe activar la animación (px desde la parte superior)
            easing: 'ease-in-out', // Tipo de easing
        });
    </script>
</body>

</html>
