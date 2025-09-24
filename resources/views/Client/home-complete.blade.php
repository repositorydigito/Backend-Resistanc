@php
use App\Services\HomePageContentService;
use App\Models\Faq;

$heroContent = HomePageContentService::getHeroContent();
$disciplinesContent = HomePageContentService::getDisciplinesContent();
$packagesContent = HomePageContentService::getPackagesContent();
$servicesContent = HomePageContentService::getServicesContent();
$downloadContent = HomePageContentService::getDownloadContent();
$locationContent = HomePageContentService::getLocationContent();
$faqContent = HomePageContentService::getFaqContent();
$faqs = Faq::active()->ordered()->get();
@endphp

<x-app>

{{-- Hero --}}
<section class="hero" id="inicio">
    <div class="containerBanner">
        <h1>{{ $heroContent['title_line_1'] ?? 'TRAIN YOUR RSISTANC.' }}</h1>
        <h1><span class="light">{{ $heroContent['title_line_2'] ?? 'LIVE UNSTOPPABLE.' }}</span></h1>
        <p>{{ $heroContent['description'] ?? 'Clases que te transforman. Energía que te eleva. Una comunidad que te empuja a más.' }}</p>
        <a href="{{ $heroContent['primary_button_link'] ?? '#membresias' }}" class="btn btn-primary">{{ $heroContent['primary_button_text'] ?? 'EMPIEZA HOY' }}</a>
        <a href="{{ $heroContent['secondary_button_link'] ?? '#disciplinas' }}" class="btn btn-outline">{{ $heroContent['secondary_button_text'] ?? 'RESERVA TU CLASE DE PRUEBA' }}</a>
    </div>
</section>

{{-- Disciplinas --}}
@if($disciplines->count() > 0)
<section class="section" id="disciplinas">
    <div class="containerDisciplines">
        <h2 class="section-title"><span class="light">{{ $disciplinesContent['title'] ?? 'ELIGE CÓMO QUIERES MOVERTE' }}</span></h2>

        <div class="carousel-container">
            <div class="carousel-items" id="carousel">
                @foreach($disciplines as $index => $discipline)
                    <div class="carousel-item">
                        <div class="card">
                            <div class="logoStudio1">
                                <img src="/image/logos/logoBlancoR.svg" alt="logo">
                                <h3>{{ $discipline->name }}</h3>
                            </div>
                            @if($discipline->description)
                                <p>{{ $discipline->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="carousel-controls">
                <button class="carousel-nav carousel-nav-left" onclick="prevSlide()">←</button>
                <button class="carousel-nav carousel-nav-right" onclick="nextSlide()">→</button>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Paquetes y Beneficios --}}
<section class="section section-packages" id="paquetes">
    <div class="container">
        <div class="gridCards">
            <div class="package-card card-brown">
                <h3><span class="light">{{ $packagesContent['card_1_title_line_1'] ?? 'PAQUETES' }}</span></h3>
                <h3><span class="light">{{ $packagesContent['card_1_title_line_2'] ?? 'QUE SE ADAPTAN' }}</span></h3>
                <h3>{{ $packagesContent['card_1_title_line_3'] ?? 'A TU RITMO.' }}</h3>
                <br>
                <p class="dark">{{ $packagesContent['card_1_subtitle'] ?? 'Desde 1 hasta 40 clases.' }}</p>
                <div class="package-details">
                    <p><span class="light">{{ $packagesContent['card_1_description_line_1'] ?? 'Mixea disciplinas, suma puntos,' }}</span></p>
                    <p><span class="light">{{ $packagesContent['card_1_description_line_2'] ?? 'sube de nivel.' }}</span></p>
                </div>
                <a href="{{ $packagesContent['card_1_button_link'] ?? '#membresias' }}" class="btn btn-outline">{{ $packagesContent['card_1_button_text'] ?? 'VER PAQUETES →' }}</a>
            </div>
            <div class="package-card card-purple">
                <h3><span class="light">{{ $packagesContent['card_2_title'] ?? 'MÁS RESISTANCE, MÁS REWARDS.' }}</span></h3>
                <br>
                <p class="dark">{{ $packagesContent['card_2_subtitle'] ?? 'Entrenar tiene beneficios reales:' }}</p>
                <div class="package-details">
                    <p><span class="light">{{ $packagesContent['card_2_description_line_1'] ?? 'Early access, descuentos y shakes gratis' }}</span></p>
                    <p><span class="light">{{ $packagesContent['card_2_description_line_2'] ?? 'alcanzando la categoría GOLD y BLACK.' }}</span></p>
                </div>
                <a href="{{ $packagesContent['card_2_button_link'] ?? '#beneficios' }}" class="btn btn-outline">{{ $packagesContent['card_2_button_text'] ?? 'VER BENEFICIOS →' }}</a>
            </div>
        </div>
    </div>
</section>

{{-- Servicios --}}
@if($disciplines->count() > 0)
<section class="section" id="servicios">
    <div class="containerDisciplines">
        <h2 class="titleServicios">{{ $servicesContent['title'] ?? 'SERVICIOS' }}</h2>
        <h3 class="subtitleServicios"><span class="light">{{ $servicesContent['subtitle'] ?? 'Explora lo que hace única tu experiencia en R STUDIO, dentro y fuera del training floor.' }}</span></h3>
        <div class="gridServicios">
            @foreach($disciplines as $discipline)
            <div class="card">
                <div class="logoStudio1">
                    <img src="/image/logos/logoBlancoR.svg" alt="logo">
                    <h3>{{ $discipline->name }}</h3>
                </div>
                @if($discipline->description)
                <p>{{ $discipline->description }}</p>
                @endif
                <div class="logoStudio">
                    <span class="light">{{ $servicesContent['card_footer_text'] ?? '⚡Shake it, wear it, own it.' }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Descarga --}}
<section class="section" id="descarga">
    <div class="containerDescarga">
        <img src="{{ HomePageContentService::getImageUrl($downloadContent['image'] ?? '/image/pages/vistaCel.svg') }}" alt="descarga">
        <div class="textDescarga">
            <h1>{{ $downloadContent['title'] ?? 'TU RSISTANC VA CONTIGO.' }} <span class="light"></span></h1>
            <h3><span class="light">{{ $downloadContent['subtitle_line_1'] ?? 'Reserva, compra, suma puntos y ve tu progreso desde nuestra app.' }}</span></h3>
            <h3>{{ $downloadContent['subtitle_line_2'] ?? 'Simple, rápida, tuya.' }}</h3>
            <br>
            <div>
                <img src="{{ HomePageContentService::getImageUrl($downloadContent['ios_icon'] ?? '/image/logos/iconos/ios.svg') }}" alt="ios">
                <img src="{{ HomePageContentService::getImageUrl($downloadContent['android_icon'] ?? '/image/logos/iconos/android.svg') }}" alt="android">
            </div>
        </div>
    </div>
</section>

{{-- Direccion --}}
<section class="section" id="direccion">
    <div class="containerDireccion">        
        <div class="textDireccion">
            <h1><span class="light">{{ $locationContent['title_line_1'] ?? 'ENCUENTRA' }}</span></h1>
            <div class="logoStudio1">
                <img src="{{ HomePageContentService::getImageUrl($locationContent['logo_image'] ?? '/image/logos/iconos/logor.svg') }}" alt="logo">
                <h1>{{ $locationContent['title_line_2'] ?? 'STUDIO' }}</h1>
            </div>
            <h3><span class="light">{{ $locationContent['description'] ?? 'Ubicado en Surco, diseñado para que te muevas libre y con flow.' }}</span></h3>
            <br>
            <div class="logoStudio">
                <img src="{{ HomePageContentService::getImageUrl($locationContent['address_icon'] ?? '/image/logos/iconos/iconomapa.svg') }}" alt="mapa">
                <span class="light">{{ $locationContent['address'] ?? 'Avenida Surco 123, Santiago de Surco, Lima, Perú' }}</span>
            </div>
            <div class="logoStudio">
                <img src="{{ HomePageContentService::getImageUrl($locationContent['phone_icon'] ?? '/image/logos/iconos/iconocel.svg') }}" alt="celular">
                <span class="light">{{ $locationContent['phone'] ?? '+51 966532455' }}</span>
            </div>
            <div class="logoStudio">
                <img src="{{ HomePageContentService::getImageUrl($locationContent['email_icon'] ?? '/image/logos/iconos/iconomail.svg') }}" alt="correo">
                <span class="light">{{ $locationContent['email'] ?? 'hola@rsistanc.com' }}</span>
            </div>
        </div>
        <a href="{{ $locationContent['map_link'] ?? 'https://www.google.com/maps?q=Avenida+Surco+123,+Santiago+de+Surco,+Lima,+Perú' }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ HomePageContentService::getImageUrl($locationContent['map_image'] ?? '/image/pages/mapa.svg') }}" alt="Mapa de Studio" class="mapaStudio">
        </a>
    </div>
</section>

{{-- FAQ --}}
<section class="section" id="faq">
    <div class="containerFaq">
        <h2 class="section-title">{{ $faqContent['title'] ?? 'FAQs' }}</h2>
        <p class="faq-subtitle">{{ $faqContent['subtitle'] ?? '¿Tienes dudas? Resolvemos todo.' }}</p>

        <div class="faq-container">
            @forelse($faqs as $faq)
                <details class="faq-item">
                    <summary class="faq-question">
                        {{ $faq->question }}
                    </summary>
                    <div class="faq-answer">
                        {{ $faq->answer }}
                    </div>
                </details>
            @empty
                {{-- Fallback to hardcoded FAQs if none exist in database --}}
                <details class="faq-item">
                    <summary class="faq-question">
                        ¿Qué incluye mi membresía RSISTANC?
                    </summary>
                    <div class="faq-answer">
                        Tu membresía te da acceso a nuestras cuatro disciplinas: Cycling, Reformer, Pilates y Box. Además, acumulas puntos con cada clase que puedes canjear por recompensas exclusivas. Gestiona todo desde nuestra app.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        ¿Ofrecen clases de prueba para nuevos miembros?
                    </summary>
                    <div class="faq-answer">
                        Sí, ofrecemos clases de prueba gratuitas para nuevos miembros. Solo necesitas registrarte en nuestra app y reservar tu clase desde allí.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        ¿Dónde se encuentran los estudios de RSISTANC?
                    </summary>
                    <div class="faq-answer">
                        Nuestro estudio principal está ubicado en {{ $locationContent['address'] ?? 'Avenida Surco 123, Santiago de Surco, Lima, Perú' }}. Pronto abriremos más sedes en otras zonas.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        ¿Puedo combinar diferentes clases en mi paquete?
                    </summary>
                    <div class="faq-answer">
                        Sí, puedes mixear cualquier combinación de clases según tu preferencia. Elige entre Cycling, Reformer, Pilates y Box sin límites.
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        ¿Cómo puedo reservar una clase?
                    </summary>
                    <div class="faq-answer">
                        Reserva tu clase desde nuestra app móvil o web. Solo selecciona la fecha, hora y disciplina, y listo. ¡Tu lugar ya está asegurado!
                    </div>
                </details>
            @endforelse
        </div>
    </div>
</section>

<script>
let slideIndex = 0;
const carousel = document.getElementById('carousel');
const items = carousel.querySelectorAll('.carousel-item');
let slidesPerView = window.innerWidth < 768 ? 1 : 3;

function updateSlides() {
    const slideWidth = carousel.offsetWidth / slidesPerView;
    carousel.scrollLeft = slideIndex * slideWidth;
}

function nextSlide() {
    if (slideIndex < items.length - slidesPerView) {
        slideIndex++;
        updateSlides();
    }
}

function prevSlide() {
    if (slideIndex > 0) {
        slideIndex--;
        updateSlides();
    }
}

function handleResize() {
    const newSlidesPerView = window.innerWidth < 768 ? 1 : 3;
    if (newSlidesPerView !== slidesPerView) {
        slidesPerView = newSlidesPerView;
        updateSlides();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    updateSlides();

    window.addEventListener('resize', handleResize);
});
</script>

</x-app>