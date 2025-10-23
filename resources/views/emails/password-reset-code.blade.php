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

        body {
            background: #EFF0F2;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border: 2px dotted #B8C5D1;
            border-radius: 20px;
            overflow: hidden;
        }

        .header {
            text-align: center;
            padding: 30px 20px 20px;
            background: white;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #B8A8C8;
            letter-spacing: 2px;
        }

        .hero-section {
            background: linear-gradient(135deg, #FF6B6B 0%, #8B5CF6 100%);
            padding: 40px 30px;
            position: relative;
            border-radius: 0 0 20px 20px;
        }

        .heart-icon {
            position: absolute;
            top: 20px;
            left: 30px;
            width: 30px;
            height: 30px;
        }

        .hero-title {
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
            margin: 20px 0 0 50px;
        }

        .hero-logo {
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 1.2rem;
            color: white;
            font-weight: bold;
        }

        .main-content {
            padding: 40px 30px;
            text-align: center;
        }

        .main-title {
            font-size: 2rem;
            font-weight: bold;
            color: #B0694C;
            margin-bottom: 20px;
        }

        .greeting {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 20px;
        }

        .description {
            font-size: 1rem;
            color: #666;
            margin-bottom: 30px;
        }

        .code-label {
            font-size: 1rem;
            color: #666;
            margin-bottom: 15px;
        }

        .code-box {
            background: #F5F5F5;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            letter-spacing: 3px;
            display: inline-block;
            margin: 0 auto 30px;
            min-width: 200px;
        }

        .disclaimer {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 30px;
        }

        .separator {
            height: 3px;
            background: linear-gradient(135deg, #FF6B6B 0%, #8B5CF6 100%);
            margin: 30px 0;
        }

        .footer {
            padding: 30px;
            text-align: center;
            background: white;
        }

        .family-text {
            font-family: 'Brush Script MT', cursive;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #FF6B6B 0%, #8B5CF6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }

        .footer-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 20px;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .social-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #FF6B6B 0%, #8B5CF6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .address {
            font-size: 0.9rem;
            color: #666;
            margin: 20px 0;
        }

        .unsubscribe {
            color: #8B5CF6;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .bottom-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FF6B6B 0%, #8B5CF6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto 0;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }

            .hero-title {
                font-size: 1.4rem;
                margin-left: 40px;
            }

            .main-title {
                font-size: 1.6rem;
            }

            .code-box {
                font-size: 1.2rem;
                padding: 12px 20px;
                min-width: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">ЯSISTANC</div>
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            <img src="https://via.placeholder.com/30x30/FFFFFF/FFFFFF?text=♥" alt="Heart Icon" class="heart-icon">
            <div class="hero-title">
                ESTAMOS AQUÍ<br>
                PARA AYUDARTE<br>
                A VOLVER
            </div>
            <div class="hero-logo">ЯSISTANC</div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="main-title">¡DON'T WORRY!</h1>

            <p class="greeting">Hola {{ $user->name }},</p>

            <p class="description">Recibimos una solicitud para restablecer tu contraseña.</p>

            <p class="code-label">Tu código de verificación es:</p>

            <div class="code-box">{{ $code }}</div>

            <p class="disclaimer">Nota: Si no creaste esta cuenta, por favor ignora este mensaje.</p>
        </div>

        <!-- Separator -->
        <div class="separator"></div>

        <!-- Footer -->
        <div class="footer">
            <div class="family-text">Rsistane Family</div>

            <p class="footer-text">Estás recibiendo este correo electrónico porque te registraste a través de nuestro sitio web.</p>

            <div class="social-icons">
                <div class="social-icon">♪</div>
                <div class="social-icon">📺</div>
                <div class="social-icon">🎵</div>
                <div class="social-icon">📷</div>
                <div class="social-icon">f</div>
                <div class="social-icon">in</div>
            </div>

            <p class="address">Avenida Surco 123, Santiago de Surco, Lima 15052</p>

            <a href="#" class="unsubscribe">Unsubscribe</a>

            <div class="bottom-logo">Я</div>
        </div>
    </div>
</body>

</html>
