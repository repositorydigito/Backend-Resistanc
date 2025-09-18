<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Email - Resistance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .logo {
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 200px;
            height: auto;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('image/logos/resistance-logo-two.png') }}" alt="Resistance Logo">
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <h1>Verifica tu dirección de email</h1>

        <p>
            Gracias por registrarte. Antes de poder acceder a tu cuenta, necesitas verificar tu dirección de email haciendo clic en el enlace que te hemos enviado.
        </p>

        <p>
            Si no recibiste el email, puedes solicitar uno nuevo.
        </p>

        <form method="POST" action="{{ route('verification.send') }}" style="margin-bottom: 20px;">
            @csrf
            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; text-align: left; margin-bottom: 5px; color: #333; font-weight: bold;">
                    Email:
                </label>
                <input type="email" id="email" name="email" placeholder="ejemplo@correo.com"
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; font-size: 16px;"
                       required>
                @error('email')
                    <div style="color: #dc3545; font-size: 14px; text-align: left; margin-top: 5px;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <button type="submit" class="btn" style="width: 100%; font-size: 16px; padding: 15px;">
                📧 Reenviar email de verificación
            </button>
        </form>

        <a href="{{ route('home') }}" class="btn btn-secondary">
            Volver al inicio
        </a>

        <p style="margin-top: 30px; font-size: 14px; color: #999;">
            ¿Tienes problemas? Contacta con soporte técnico.
        </p>
    </div>
</body>
</html>
