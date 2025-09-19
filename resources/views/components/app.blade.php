<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resistance - Inicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="header">
        <div class="containerNav">
            <nav class="nav">
                <a href="{{ route('home') }}" class="logo">
                    <img src="{{ asset('image/logos/logoblanco.svg') }}" alt="Resistance Logo" width="200">
                </a>
                <ul class="nav-menu">
                    <a href="#contacto" class="btn btn-primary">EMPIEZA HOY</a>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} Resistance Gym. Todos los derechos reservados.</p>
        </div>
    </footer>


</body>
</html>
