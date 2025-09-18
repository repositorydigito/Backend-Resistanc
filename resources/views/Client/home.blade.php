<x-app>

{{-- Hero --}}
<section class="hero" id="inicio">
    <div class="container">
        <h1>Transforma tu Vida</h1>
        <p>Entrena con los mejores equipos e instructores certificados</p>
        <a href="#membresias" class="btn btn-primary">Ver Membresías</a>
        <a href="#disciplinas" class="btn btn-outline">Conocer Disciplinas</a>
    </div>
</section>

{{-- Membresías --}}
@if($membresias->count() > 0)
<section class="section section-alt" id="membresias">
    <div class="container">
        <h2 class="section-title">Nuestras Membresías</h2>
        <div class="grid">
            @foreach($membresias as $membresia)
            <div class="card membership-card">
                <h3>{{ $membresia->name }}</h3>
                <div class="price">${{ number_format($membresia->price, 0) }}</div>
                @if($membresia->description)
                <p>{{ $membresia->description }}</p>
                @endif
                <ul class="features">
                    <li>✓ Acceso completo al gimnasio</li>
                    <li>✓ Asesoría personalizada</li>
                    <li>✓ Clases grupales incluidas</li>
                </ul>
                <a href="#contacto" class="btn btn-primary">Elegir Plan</a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Disciplinas --}}
@if($disciplines->count() > 0)
<section class="section" id="disciplinas">
    <div class="container">
        <h2 class="section-title">Disciplinas Disponibles</h2>
        <div class="grid">
            @foreach($disciplines as $discipline)
            <div class="card">
                <h3>{{ $discipline->name }}</h3>
                @if($discipline->description)
                <p>{{ $discipline->description }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section class="hero" id="contacto">
    <div class="container">
        <h2>¿Listo para empezar?</h2>
        <p>Tu transformación comienza hoy</p>
        <a href="tel:+123456789" class="btn btn-primary">Llamar Ahora</a>
        <a href="mailto:info@resistance.com" class="btn btn-outline">Enviar Email</a>
    </div>
</section>

</x-app>
