<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resistance - Inicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <meta name="google-site-verification" content="39QUCmfjodGhkNM6wIR8EJkohPvkXKwGHRqlKxCduRo" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <div class="footer-grid">
                <div class="footer-logo">
                    <img src="{{ asset('image/logos/logorsistanc.svg') }}" alt="RSISTANC Logo">
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-spotify"></i></a>
                    </div>
                </div>

                <div class="footer-column">
                    <h4>R Studio</h4>
                    <ul>
                        <li><a href="#">Reservar</a></li>
                        <li><a href="#">Paquetes</a></li>
                        <li><a href="#">Servicios</a></li>
                        <li><a href="#">Rewards</a></li>
                        <li><a href="#">Clase de Prueba</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Links</h4>
                    <ul>
                        <li><a href="#">R Workshops</a></li>
                        <li><a href="#">R Business & Events</a></li>
                        <li><a href="#">R Recovery</a></li>
                        <li><a href="#">R Shop</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>App</h4>
                    <ul>
                        <li><a href="#">iOS</a></li>
                        <li><a href="#">Android</a></li>
                    </ul>
                </div>
            </div>
            <div class="footerBottom">
                <p>&copy; 2025 RSISTANC. Todos los derechos reservados.</p>
                <p><a href="privacity">Políticas de Privacidad</a> | <a href="terms">Términos y Condiciones</a></p>
            </div>
        </div>
    </footer>
</body>
</html>
