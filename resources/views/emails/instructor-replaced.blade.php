<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cambio de Instructor</title>
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

        .card__img--logo {
            height: 80px;
            max-width: 250px;
            margin: auto;
            object-fit: contain;
        }

        .card__body p {
            font-size: .9rem;
            text-align: center;
            margin-bottom: .6rem;
            font-weight: 100;
            color: #5D6D7A;
            max-width: 450px;
            margin: auto;
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

        .footer__direccion {
            max-width: 150px;
            font-size: 1rem;
            text-align: center;
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
    $discipline = $classSchedule->class->discipline ?? null;
    $disciplineName = $discipline->name ?? 'Clase';
    $scheduledDate = \Carbon\Carbon::parse($classSchedule->scheduled_date);
    $formattedDate = $scheduledDate->locale('es')->isoFormat('dddd, D [de] MMMM');
    $formattedTime = \Carbon\Carbon::parse($classSchedule->start_time)->format('H:i');
    $substituteName = $substituteInstructor->name ?? 'Nuevo Instructor';
    $instructorImage = $substituteInstructor->presentation_image 
        ? asset('storage/' . $substituteInstructor->presentation_image)
        : ($substituteInstructor->profile_image ? asset('storage/' . $substituteInstructor->profile_image) : asset('image/emails/default-instructor.png'));
@endphp

<body>
    <main class="main">
        <div class="fondo">
            <div class="card">
                <div class="card__content">
                    <!-- Logo -->
                    <div style="text-align: center; margin-bottom: 2rem;">
                        @if ($company->logo_path)
                            <img class="card__img--logo" src="{{ asset('storage/' . $company->logo_path) }}"
                                alt="Logo-rsistanc">
                        @else
                            <img class="card__img--logo" src="{{ asset('image/emails/logos/logo-correo.png') }}"
                                alt="">
                        @endif
                    </div>

                    <!-- Hero Section con imagen del instructor -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 2rem;">
                        <tr>
                            <td align="center" style="padding: 20px 0;">
                                <img src="{{ $instructorImage }}"
                                    alt="{{ $substituteName }}"
                                    style="width: 100%; max-width: 400px; height: auto; border-radius: 20px; object-fit: cover; display: block; margin: 0 auto;">
                            </td>
                        </tr>
                    </table>

                    <!-- Título principal -->
                    <h2 style="font-size: 24px; color: #5D6D7A; text-align: center; margin: 1rem 0; font-weight: 800;">
                        DIFFERENT <span style="color: #B0694C;">ЯISTAR</span>, <span style="color: #B0694C;">SAME ENERGY</span>
                    </h2>

                    <!-- Saludo -->
                    <h1 class="card__saludo">¡Hola!</h1>

                    <!-- Contenido -->
                    <div class="card__body">
                        <p>Queremos avisarte que tu clase de <strong>{{ $disciplineName }}</strong> programada para el <strong>{{ $formattedDate }}</strong> a las <strong>{{ $formattedTime }}</strong> ahora será dictada por <strong>{{ $substituteName }}</strong>.</p>
                        <p style="margin-top: 1rem;">Sabemos que cada coach aporta su estilo, y estamos seguras de que esta clase será <strong style="color: #AF58C9;">POWER</strong> 💪</p>
                    </div>

                    <!-- Botón CTA -->
                    <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center"
                        style="margin: 30px auto;">
                        <tr>
                            <td align="center" bgcolor="#899830"
                                style="border-radius: 30px; padding: 12px 30px;">
                                <a href="{{ config('app.url') }}/classes" target="_blank"
                                    style="font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; text-transform: uppercase; display: inline-block;">
                                    VER MI CLASE
                                </a>
                            </td>
                        </tr>
                    </table>

                    <!-- Nota -->
                    <div style="text-align: center; margin: 2rem 0; font-size: 12px; color: #5D6D7A;">
                        <p>Los T&C van hacia la web en la sección que corresponde. La señalética de bici manda a reservar en la app o web.</p>
                    </div>

                    <div class="linea"></div>

                    <!-- Footer -->
                    <div class="footer__correo">
                        @if ($company->signature_image)
                            <img class="footer__firma" src="{{ asset('storage/' . $company->signature_image) }}"
                                alt="firma">
                        @else
                            <img class="footer__firma" src="{{ asset('image/emails/firma.png') }}" alt="firma">
                        @endif

                        <p class="footer__text">
                            Estás recibiendo este correo electrónico porque te registraste a través de nuestro sitio web.
                        </p>

                        <!-- Redes sociales -->
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

                        <p class="footer__direccion">{{ $company->address }}</p>
                        <div class="footer__logo">
                            <img src="{{ asset('image/emails/logo-rsistanc-correo.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>

