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
            /* outline: 1px solid red; */
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
            font-size: 1.7rem;
            margin: 1rem 0;
        }

        .card__title--negrita {
            color: #B0694C;
            font-weight: 800;
            margin: auto;
            text-align: center;
            font-size: 1.7rem;
        }

        .card__saludo {
            font-size: 1.1rem;
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
            display: grid;
            width: 100%;
        }

        .card__img--logo {
            height: 80px;
            max-width: 250px;


            margin: auto;
            object-fit: contain;

        }

        .card__body {
            display: grid;
            /* flex-direction: column; */
            gap: .6rem;
        }

        .card__body p {
            font-size: .9rem;
            text-align: center;
            margin-bottom: 13rem;
            font-weight: 100;
            color: #5D6D7A;
            max-width: 450px;
            margin: auto;
            margin-bottom: .6rem;
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

        .logo__studio img {
            height: 2rem;
            /* width: 2rem; */
            object-fit: contain;
        }

        .card__title--img {
            margin: auto;
            display: flex;
            justify-content: center;
        }

        .card__title--img img {
            height: 2.5rem;
            object-fit: contain;
            margin-right: .3rem;
        }


        .verificacion__btn {
            position: relative;

            margin-top: 1rem;
            margin-bottom: 1rem;
            /* background: blue; */
        }

        .verificacion__btn a {

            all: unset;
        }

        a {
            all: unset;
        }

        .verificacion__btn--enlace {
            display: inline-block !important;
            padding: 12px 30px !important;
            background: #899830 !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 30px !important;
            font-weight: 600;
            text-align: center !important;
            font-size: .8rem !important;
            border: none !important;
            cursor: pointer !important;
            margin: auto !important;
            text-transform: uppercase !important;
        }

        /* Para asegurar que no herede estilos */
        .verificacion__btn a {
            text-decoration: none !important;
            color: inherit !important;
        }

        .titulo__acoiris {
            text-align: center;
            font-size: 1.2rem;
            text-transform: uppercase;
            font-weight: 900;

            background: var(--gradient2,
                    linear-gradient(94deg,
                        #CF5E30 -5.25%,
                        #AF58C9 27.54%,
                        #8A982F 79.49%,
                        #0979E5 110.6%));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .beneficios {
            padding: 2rem;

        }

        .beneficios__list {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
            margin-bottom: 2rem;
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
                        <img class="card__img--target" src="{{ asset('image/emails/activacion/card-activacion.png') }}"
                            alt="card">

                    </div>


                    <!-- Contenedor -->
                    <div class="" style="padding-top: 2rem;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                            style="text-align:center;">
                            <tr>
                                <td align="center">

                                    <!-- Imagen + texto juntos -->
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="vertical-align:middle; padding-right:5px;">
                                                <img src="{{ asset('image/emails/activacion/rsistanc-logo-marron.png') }}"
                                                    alt="rsistanc-logo" style="height:22px; display:block;">
                                            </td>

                                            <td style="vertical-align:middle;">
                                                <span class="card__title--negrita logo__studio">
                                                    STUDIO
                                                </span>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>
                    </div>





                    <h1 class="card__saludo">Hola {{ $user->name }}, </h1>

                    <div class="card__body">

                        <p>¡Esperamos estés teniendo un súper día! Ya formas parte de una red de entrenamiento que
                            combina energía, motivación y resultados.</p>
                        <p>Solo falta un paso para que empieces a reservar tus clases, disfrutar de nuestros
                            entrenamientos y aprovechar los beneficios exclusivos para miembros.</p>
                        <p>Haz clic en el botón de abajo para activar tu cuenta:</p>
                    </div>

                    <!-- BOTÓN CENTRADO 100% COMPATIBLE -->
                    <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center"
                        style="margin: 20px auto;">
                        <tr>
                            <td align="center" bgcolor="#899830"
                                style="
            border-radius: 30px;
            padding: 12px 30px;
        ">
                                <a href="{{ $verificationUrl }}" target="_blank"
                                    style="
                    font-size: 14px;
                    font-weight: 600;
                    color: #ffffff;
                    text-decoration: none;
                    text-transform: uppercase;
                    display: inline-block;
                ">
                                    ACTIVAR MI CUENTA
                                </a>
                            </td>
                        </tr>
                    </table>



                    <div class="card__body">
                        <p class="">Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu
                            navegador:</p>
                    </div>

                    <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">
                        {{ $verificationUrl }}
                    </p>


                    <!-- Sección de Beneficios -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                        style="background: url('{{ asset('image/emails/activacion/fondo-activacion.png') }}') no-repeat center; background-size: cover; border-radius: 15px; padding: 20px; margin: 20px auto; max-width: 500px;">
                        <tr>
                            <td align="center" style="padding: 20px;">
                                <!-- Título "BENEFICIOS" -->
                                <h3
                                    style="
                font-family: Arial, sans-serif;
                font-size: 36px;
                font-weight: bold;
                color: #000;
                background: linear-gradient(90deg, #EC008C, #4FC3F7, #2ECC71);
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                display: inline-block;
                margin: 0;
                padding: 0;
                letter-spacing: -1px;
            ">
                                    BENEFICIOS
                                </h3>
                                <h4 style="color: #5D6D7A; font-size: 16px; margin: 5px 0; font-weight: normal;">
                                    al activar tu cuenta hoy
                                </h4>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0 20px;">
                                <!-- Lista de beneficios -->
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    style="margin: 15px auto; text-align: left;">
                                    <!-- Beneficio 1 -->
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;">
                                            <table role="presentation" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 20px; vertical-align: top; padding-right: 10px;">
                                                        <img src="{{ asset('image/emails/activacion/check.png') }}"
                                                            alt="Icono" width="20" height="20"
                                                            style="display: block;">
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <p
                                                            style="font-size: 14px; color: #5D6D7A; margin: 0; font-weight: 500;">
                                                            <strong>Acceso a todas nuestros planes</strong> por
                                                            disciplinas: R Cycling, R Reformer, R Pilates y R Box.
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Beneficio 2 -->
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;">
                                            <table role="presentation" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 20px; vertical-align: top; padding-right: 10px;">
                                                        <img src="{{ asset('image/emails/activacion/check.png') }}"
                                                            alt="Icono" width="20" height="20"
                                                            style="display: block;">
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <p
                                                            style="font-size: 14px; color: #5D6D7A; margin: 0; font-weight: 500;">
                                                            <strong>Descuentos exclusivos</strong> en accesorios y
                                                            shakes de recuperación.
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Beneficio 3 -->
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;">
                                            <table role="presentation" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 20px; vertical-align: top; padding-right: 10px;">
                                                        <img src="{{ asset('image/emails/activacion/check.png') }}"
                                                            alt="Icono" width="20" height="20"
                                                            style="display: block;">
                                                    </td>
                                                    <td style="vertical-align: top;">
                                                        <p
                                                            style="font-size: 14px; color: #5D6D7A; margin: 0; font-weight: 500;">
                                                            <strong>Seguimiento de tu progreso</strong> desde la app.
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>



                    <div class="nota">
                        <p>Nota: Si no creaste esta cuenta, por favor ignora este mensaje.</p>
                    </div>

                    <div class="linea"></div>
                    <div class="footer__correo">

                        @if ($company->signature_image)
                            {{-- Produccion --}}
                            <img class="footer__firma" src="{{ asset('storage/' . $company->signature_image) }}"
                                alt="firma">
                        @else
                            {{-- Produccion --}}
                            <img class="footer__firma" src="{{ asset('image/emails/firma.png') }}" alt="firma">
                        @endif



                        <p class="footer__text">
                            Estás recibiendo este correo electrónico porque te registraste a través de nuestro app.
                        </p>
                        <!-- Redes sociales (compatible con Gmail/Outlook) -->
                        <table role="presentation" cellpadding="0" cellspacing="0" align="center"
                            style="margin: 20px auto;">
                            <tr>

                                @if ($company->facebook_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->facebook_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/facebook.png') }}" alt="facebook"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                                @if ($company->instagram_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->instagram_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/instagram.png') }}" alt="instagram"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                                @if ($company->twitter_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->twitter_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/new-twitter.png') }}" alt="twitter"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                                @if ($company->linkedin_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->linkedin_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/linkedin.png') }}" alt="linkedin"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                                @if ($company->youtube_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->youtube_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/youtube.png') }}" alt="youtube"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                                @if ($company->tiktok_url)
                                    <td style="padding: 0 6px;">
                                        <a href="{{ $company->tiktok_url }}" target="_blank"
                                            style="text-decoration:none; display:inline-block;">
                                            <img src="{{ asset('image/redes/tiktok.png') }}" alt="tiktok"
                                                width="20" height="20" style="display:block;">
                                        </a>
                                    </td>
                                @endif

                            </tr>
                        </table>


                        <p class="footer__direccion"> {{ $company->address }} </p>
                        <div class="footer__logo">



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
