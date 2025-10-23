<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Código de Recuperación de Contraseña</title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        html {
            background: #EFF0F2;
        }

        .main {
            background: #4a6cb0;
            height: 100vh;
            width: 100vw;
            color: #5D6D7A;
        }

        .code__content {

            display: grid;
            justify-content: center;
            justify-items: center;

        }

        .code {
            background: linear-gradient(94deg, #E7D4D8 0%, #E5D7EA 28.85%, #E7DFE9 50.48%, #D9D9D2 69.71%, #CDD6D7 100%);
            padding: .5rem 1rem;
            align-items: center;
            border-radius: 16px;
            font-size: 1.7rem;
            letter-spacing: .8rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            justify-content: center;
            justify-items: center;
            /* max-width: 150px; */
            /* width: 150px; */
            margin: auto;
        }

        .main {
            display: grid;
            padding: 2rem;
        }

        .fondo {

            background: #EFF0F2;
            display: grid;
            justify-content: center;
            align-content: center;
            padding: 5rem 2rem;
        }

        .card {
            background: #fff;
            border-radius: 30px;
            max-width: 550px;
            width: 550px;
            margin: auto;
            padding: 1.5rem;
        }

        .card__content--negrita {
            font-weight: 600;
            color: #B0694C;
            background: #fff;
        }

        .card__title {
            text-align: center;
            font-size: 2rem;
            margin: 1rem 0;
        }

        .card__title--negrita {
            color: #B0694C;
            font-weight: 800;
        }

        .card__saludo {
            font-size: 1.2rem;
            margin: 1rem 0;
            text-align: center;
        }

        .card__body>* {
            text-align: center;
        }

        .linea {
            height: 1px;
            width: 100%;
            background: linear-gradient(91deg, rgba(176, 105, 76, 0.70) -12.38%, rgba(162, 103, 180, 0.70) 27.49%, rgba(174, 159, 176, 0.70) 56.83%, rgba(106, 111, 74, 0.70) 80.27%, rgba(33, 106, 176, 0.70) 127.38%);
        }

        .card__imgs img {
            width: 100%;
        }

        .card__img--logo {
            height: 80px;
            max-width: 250px;
            margin: auto;
            /* margin-bottom: 1rem; */
            padding-bottom: 1rem;
            object-fit: contain;

        }

        .card__body p {
            font-size: 1rem;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 200;
            color: #5D6D7A;
        }

        .nota p {
            margin: 1rem 0;
            font-size: 1rem;
            font-weight: 200;
            color: #5D6D7A;
            text-align: center;
        }

        .footer__correo {
            display: grid;
            justify-content: center;
            justify-items: center;
        }

        .footer__firma {
            height: 100px;
            max-width: 250px;
            margin: auto;
            object-fit: contain;
        }

        .footer__text {
            font-weight: 100;
            text-align: center;
            max-width: 280px;
            margin: auto;
            color: #5D6D7A;
        }

        .redes {
            display: inline-block;
            gap: .5rem;
            justify-content: center;
            justify-items: center;
            align-content: center;
            align-items: center;
            margin: auto;
            padding: 1rem 0;
            /* background: red; */
        }

        .red__social img {
            height: 20px;
            width: 20px;
            object-position: center;
            display: inline-block;
            object-fit: contain;
        }

        .footer__direccion {
            max-width: 150px;
            font-size: 1rem;
            text-align: center;
            margin: auto;
        }


        .footer__logo {
            margin: auto;
        }

        .footer__logo img {
            padding-top: 1rem;
            width: 85px;
            object-fit: contain;
            margin: auto;
        }
    </style>
</head>

@php
    $company = \App\Models\Company::first();
@endphp

<body>
    <main class="main">

        <div class="fondo">
            <div class="card">

                <div class="card__content">

                    <div class="card__imgs">

                        {{-- Produccion --}}
                        {{-- <img class="card__img--logo" src="" alt="">
                        <img class="card__img--target" src="" alt=""> --}}

                        {{-- Desarrollo --}}



                        @if ($company->logo_path)
                            {{-- Desarrollo  --}}
                            {{-- <img class="card__img--logo"
                                src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Logos.svg/1200px-Logos.svg.png"
                                alt="logo"> --}}

                            {{-- Produccion --}}
                            <img class="card__img--logo" src="{{ asset('storage/' . $company->logo_path) }}"
                                alt="Logo-rsistanc">
                        @else
                            {{-- Desarrollo --}}
                            {{-- <img class="card__img--logo"
                                src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Logos.svg/1200px-Logos.svg.png"
                                alt="logo"> --}}

                            {{-- Produccion --}}
                            <img class="card__img--logo" src="{{ asset('image/emails/logos/logo-correo.png') }}"
                                alt="">
                        @endif


                        {{-- Desarrollo --}}
                        {{-- <img class="card__img--target"
                            src="https://media.united.com/assets/m/4beccaf8e41c8b87/original/UNTD24_ClubCard_LgType_MKTG_Card_RGB_2000x1260.png"
                            alt="card"> --}}

                        {{-- PRODUCCION --}}
                        <img class="card__img--target"
                            src="{{ asset('image/emails/recuperacionContrasenia/ayuda-rsistanc.png') }}" alt="card">

                    </div>


                    <h2 class="card__title">
                        ¡DON’T <span class="card__title--negrita">WORRY!</span>
                    </h2>

                    <h1 class="card__saludo">Hola {{ $user->name }}, </h1>

                    <div class="card__body">
                        {{-- {!! $data->body !!}
                         --}}
                        <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                        <p>Tu código de verificación es:</p>

                    </div>
                    <div class="code__content">
                        <span class="code">
                            {{ $code }}
                        </span>
                    </div>

                    <div class="nota">
                        <p>Nota: Si no creaste esta cuenta, por favor ignora este mensaje.</p>
                    </div>

                    <div class="linea"></div>
                    <div class="footer__correo">

                        @if ($company->signature_image)
                            {{-- Desarrollo --}}
                            {{-- <img class="footer__firma"
                                src="https://upload.wikimedia.org/wikipedia/commons/8/82/Firma-Miguel.png"
                                alt=""> --}}
                            {{-- Produccion --}}
                            <img class="footer__firma" src="{{ asset('storage/' . $company->signature_image) }}"
                                alt="firma">
                        @else
                            {{-- Desarrollo --}}
                            {{-- <img class="footer__firma"
                                src="https://upload.wikimedia.org/wikipedia/commons/8/82/Firma-Miguel.png"
                                alt=""> --}}

                            {{-- Produccion --}}
                            <img class="footer__firma" src="{{ asset('image/emails/firma.png') }}" alt="firma">
                        @endif



                        <p class="footer__text">
                            Estás recibiendo este correo electrónico porque te registraste a través de nuestro app.
                        </p>
                        <div class="redes">

                            {{-- Facebook --}}
                            @if ($company->facebook_url)
                                <a class="red__social" href="{{ $company->facebook_url }}">
                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                        alt="facebook-rsistanc"> --}}
                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/facebook.png') }}" alt="facebook-rsistanc">
                                </a>
                            @endif

                            {{-- Instagram --}}
                            @if ($company->instagram_url)
                                <a class="red__social" href="{{ $company->instagram_url }}">

                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                        alt="Instagram-rsistanc"> --}}
                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/instagram.png') }}" alt="facebook-rsistanc">

                                </a>
                            @endif

                            {{-- Twiter --}}
                            @if ($company->twitter_url)
                                <a class="red__social" href="{{ $company->twitter_url }}">
                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                        alt="Twiter-rsistanc"> --}}

                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/new-twitter.png') }}" alt="Twiter-rsistanc">
                                </a>
                            @endif
                            {{-- linkedin --}}
                            @if ($company->linkedin_url)
                                <a class="red__social" href="{{ $company->linkedin_url }}">
                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                        alt="linkedin-rsistanc"> --}}

                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/linkedin.png') }}" alt="Twiter-rsistanc">

                                </a>
                            @endif
                            {{-- Youtube --}}
                            @if ($company->youtube_url)
                                <a class="red__social" href="{{ $company->youtube_url }}">

                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                    alt="Youtube-rsistanc"> --}}

                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/youtube.png') }}" alt="Youtube-rsistanc">

                                </a>
                            @endif

                            {{-- Tiktok --}}
                            @if ($company->tiktok_url)
                                <a class="red__social" href="{{ $company->tiktok_url }}">
                                    {{-- Desarrollo --}}
                                    {{-- <img src="https://cdn-icons-png.flaticon.com/512/59/59439.png"
                                        alt="Tiktok-rsistanc"> --}}

                                    {{-- Produccion --}}
                                    <img src="{{ asset('image/redes/tiktok.png') }}" alt="tiktok-rsistanc">
                                </a>
                            @endif

                        </div>

                        <p class="footer__direccion"> {{ $company->address }} </p>
                        <div class="footer__logo">

                            {{-- Desarrollo --}}
                            {{-- <img class=""
                                src="https://e7.pngegg.com/pngimages/415/762/png-clipart-circle-crescent-logo-circle-white-logo.png"
                                alt="Logo-footer"> --}}

                            {{-- Produccion --}}
                            <img src="{{ asset('image/emails/logo-rsistanc-correo.png') }}" alt="">
                        </div>

                    </div>

                </div>


            </div>
        </div>



    </main>
</body>

</html>
