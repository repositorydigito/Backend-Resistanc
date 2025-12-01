<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>¡Tu paquete ya está activo!</title>
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
            /* color: #B0694C; */
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
            font-weight: 100;
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
    </style>
</head>

@php
    $company = \App\Models\Company::first();
    $package = $userPackage->package ?? null;
    $disciplines = $package ? ($package->disciplines ?? collect()) : collect();
    $packageName = $package->name ?? 'Paquete';
    $classesQuantity = $package->classes_quantity ?? 0;
    $firstDiscipline = $disciplines->isNotEmpty() ? $disciplines->first()->name ?? '' : '';
@endphp

<body>
    <main class="main">

        <div class="fondo">
            <div class="card">

                <div class="card__content">

                    <div class="card__imgs">

                        @if ($company->logo_path)
                            <img class="card__img--logo" src="{{ asset('storage/' . $company->logo_path) }}"
                                alt="Logo-rsistanc">
                        @else
                            <img class="card__img--logo" src="{{ asset('image/emails/logos/logo-correo.png') }}"
                                alt="">
                        @endif

                        <img class="card__img--target" src="{{ asset('image/emails/package/package-imagen.png') }}"
                            alt="card" onerror="this.src='{{ asset('image/emails/activacion/card-activacion.png') }}'">

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


                                            <td style="vertical-align:middle;">
                                                <span class="card__title--negrita logo__studio">
                                                  LET’S<strong style=" color: #B0694C;">STUDIO </strong>
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

                        <p>¡Esperamos estés teniendo un súper día! Te dejamos acá el detalle de lo que incluye el paquete <strong style="font-weight: 800;">{{ $packageName }}</strong> que acabas de adquirir ❤️</p>
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
                                <a href="{{ config('app.url') }}/packages" target="_blank"
                                    style="
                    font-size: 14px;
                    font-weight: 600;
                    color: #ffffff;
                    text-decoration: none;
                    text-transform: uppercase;
                    display: inline-block;
                ">
                                    RESERVAR MI PRIMERA CLASE
                                </a>
                            </td>
                        </tr>
                    </table>



                    <!-- INFORMACIÓN DEL PAQUETE -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                        style="background: #EFF0F2; border-radius: 15px; padding: 30px 20px; margin: 20px auto; max-width: 500px;">
                        <tr>
                            <td align="center" style="padding-bottom: 20px;">
                                <!-- Título con degradado -->
                                <h3 style="font-size: 24px; margin: 0 0 5px 0; font-weight: 800; font-family: 'Outfit', sans-serif; background: linear-gradient(94deg, #CF5E30 -5.25%, #AF58C9 27.54%, #8A982F 79.49%, #0979E5 110.6%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                    {{ strtoupper($classesQuantity) }} CLASES
                                </h3>
                                <h4 style="color: #5D6D7A; font-size: 16px; margin: 0; font-weight: 400; font-family: 'Outfit', sans-serif;">
                                    TU PAQUETE INCLUYE
                                </h4>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <!-- Tarjeta 1: Clases con icono de fuego -->
                                        <td width="48%" style="background: #ffffff; border-radius: 15px; padding: 20px; text-align: center; vertical-align: top;">
                                            <!-- Icono circular con degradado -->
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="center" style="padding-bottom: 15px;">
                                                        <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(94deg, #CF5E30 -5.25%, #AF58C9 27.54%, #8A982F 79.49%, #0979E5 110.6%); display: inline-block; padding: 12px; box-sizing: border-box;">
                                                            <img src="{{ asset('image/emails/package/fire-white.png') }}" 
                                                                alt="Fuego" 
                                                                width="36" 
                                                                height="36" 
                                                                style="display: block; width: 100%; height: auto; object-fit: contain;">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center">
                                                        <p style="font-size: 18px; color: #5D6D7A; margin: 5px 0; font-weight: 600; font-family: 'Outfit', sans-serif;">
                                                            {{ $classesQuantity }} clases
                                                        </p>
                                                        @if($firstDiscipline)
                                                        <p style="font-size: 14px; color: #5D6D7A; margin: 5px 0; font-weight: 500; font-family: 'Outfit', sans-serif;">
                                                            {{ $firstDiscipline }}
                                                        </p>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="4%"></td>
                                        <!-- Tarjeta 2: Validez con icono de calendario -->
                                        <td width="48%" style="background: #ffffff; border-radius: 15px; padding: 20px; text-align: center; vertical-align: top;">
                                            <!-- Icono circular con degradado -->
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="center" style="padding-bottom: 15px;">
                                                        <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(94deg, #CF5E30 -5.25%, #AF58C9 27.54%, #8A982F 79.49%, #0979E5 110.6%); display: inline-block; padding: 12px; box-sizing: border-box;">
                                                            <img src="{{ asset('image/emails/package/calender-white.png') }}" 
                                                                alt="Calendario" 
                                                                width="36" 
                                                                height="36" 
                                                                style="display: block; width: 100%; height: auto; object-fit: contain;">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center">
                                                        <p style="font-size: 14px; color: #5D6D7A; margin: 5px 0; font-weight: 400; font-family: 'Outfit', sans-serif;">
                                                            Válido por
                                                        </p>
                                                        @php
                                                            $monthsValid = $userPackage && $userPackage->expiry_date ? $userPackage->expiry_date->diffInMonths(now()) : 0;
                                                            $daysValid = $userPackage && $userPackage->expiry_date ? $userPackage->expiry_date->diffInDays(now()) : 0;
                                                        @endphp
                                                        <p style="font-size: 18px; color: #5D6D7A; margin: 5px 0; font-weight: 600; font-family: 'Outfit', sans-serif;">
                                                            @if($monthsValid > 0)
                                                                {{ $monthsValid }} {{ $monthsValid == 1 ? 'mes' : 'meses' }}
                                                            @elseif($daysValid > 0)
                                                                {{ $daysValid }} {{ $daysValid == 1 ? 'día' : 'días' }}
                                                            @else
                                                                Indefinido
                                                            @endif
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
                        <p>Nota: Si no realizaste esta compra, por favor ignora este mensaje.</p>
                    </div>

                    <div class="linea"></div>
                    <div class="footer__correo">

                        @if ($company->signature_image)
                            <img class="footer__firma" src="{{ asset('storage/' . $company->signature_image) }}"
                                alt="firma">
                        @else
                            <img class="footer__firma" src="{{ asset('image/emails/firma.png') }}" alt="firma">
                        @endif



                        <p class="footer__text">
                            Estás recibiendo este correo electrónico porque adquiriste un paquete a través de nuestro app.
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

