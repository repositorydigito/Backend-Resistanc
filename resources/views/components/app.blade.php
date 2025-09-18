<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resistance - Inicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>

    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="{{ route('home') }}" class="logo">Resistance</a>
                <ul class="nav-menu">
                    <li><a href="{{ route('home') }}" class="nav-link">Inicio</a></li>
                    <li><a href="#membresias" class="nav-link">Membresías</a></li>
                    <li><a href="#disciplinas" class="nav-link">Disciplinas</a></li>
                    <li><a href="#contacto" class="nav-link">Contacto</a></li>
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
