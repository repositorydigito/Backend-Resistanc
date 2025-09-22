<x-app>

{{-- Hero --}}
<section class="hero" id="inicio">
    <div class="containerBanner">
        <h1>TRAIN <span class="light">YOUR</span> RSISTANC.</h1>
        <h1><span class="light">LIVE</span> UNSTOPPABLE.</h1>
        <p>Clases que te transforman. Energía que te eleva. Una comunidad que te empuja a más.</p>
        <a href="#membresias" class="btn btn-primary">EMPIEZA HOY</a>
        <a href="#disciplinas" class="btn btn-outline">RESERVA TU CLASE DE PRUEBA</a>
    </div>
</section>

{{-- Disciplinas --}}
@if($disciplines->count() > 0)
<section class="section" id="disciplinas">
    <div class="containerDisciplines">
        <h2 class="section-title"><span class="light">ELIGE CÓMO QUIERES</span> MOVERTE</h2>
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

{{-- Paquetes y Beneficios --}}
<section class="section section-packages" id="paquetes">
    <div class="container">
        <div class="gridCards">
            <div class="package-card card-brown">
                <h3><span class="light">PAQUETES</span></h3>
                <h3><span class="light">QUE SE ADAPTAN</span></h3>
                <h3>A TU RITMO.</h3>
                <br>
                <p class="dark">Desde 1 hasta 40 clases.</p>
                <div class="package-details">
                    <p><span class="light">Mixea disciplinas, suma puntos,</span></p>
                    <p><span class="light">sube de nivel.</span></p>
                </div>
                <a href="#membresias" class="btn btn-outline">VER PAQUETES →</a>
            </div>
            <div class="package-card card-purple">
                <h3><span class="light">MÁS</span> RESISTANCE, <span class="light">MÁS</span> REWARDS.</h3>
                <br>
                <p class="dark">Entrenar tiene beneficios reales:</p>
                <div class="package-details">
                    <p><span class="light">Early access, descuentos y shakes gratis</span></p>
                    <p><span class="light">alcanzando la categoría </span>GOLD y BLACK.</p>
                </div>
                <a href="#beneficios" class="btn btn-outline">VER BENEFICIOS →</a>
            </div>
        </div>
    </div>
</section>

{{-- Servicios --}}
@if($disciplines->count() > 0)
<section class="section" id="servicios">
    <div class="containerDisciplines">
        <h2 class="titleServicios">SERVICIOS</h2>
        <h3 class="subtitleServicios"><span class="light">Explora lo que hace única tu experiencia en</span> R STUDIO<span class="light">, dentro y fuera del training floor.</span></h3>
        <div class="gridServicios">
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

{{-- Descarga --}}
<section class="section" id="descarga">
    <div class="containerDescarga">
        <img src="/image/pages/vistaCel.svg" alt="descarga">
        <div class="textDescarga">
            <h1>TU RSISTANC <span class="light">VA CONTIGO.</span></h1>
            <h3><span class="light">Reserva, compra, suma puntos y ve tu progreso desde nuestra app.</span></h3>
            <h3>Simple, rápida, tuya.</h3>
            <br>
            <div>
                <img src="/image/logos/iconos/ios.svg" alt="ios">
                <img src="/image/logos/iconos/android.svg" alt="android">
            </div>
        </div>
    </div>
</section>

{{-- Direccion --}}
<section class="section" id="direccion">
    <div class="containerDireccion">        
        <div class="textDireccion">
            <h1><span class="light">ENCUENTRA</span></h1>
            <div class="logoStudio">
                <img src="/image/logos/iconos/logor.svg" alt="logo">
                <h1>STUDIO</h1>
            </div>
            <h3><span class="light">Ubicado en Surco, diseñado para que te muevas libre y con flow.</span></h3>
            <br>
            <div class="logoStudio">
                <img src="/image/logos/iconos/iconomapa.svg" alt="mapa">
                <span class="light">Avenida Surco 123, Santiago de Surco, Lima, Perú</span>
            </div>
            <div class="logoStudio">
                <img src="/image/logos/iconos/iconocel.svg" alt="celular">
                <span class="light">+51 966532455</span>
            </div>
            <div class="logoStudio">
                <img src="/image/logos/iconos/iconomail.svg" alt="correo">
                <span class="light">hola@rsistanc.com</span>
            </div>
        </div>
        <a href="https://www.google.com/maps?q=Avenida+Surco+123,+Santiago+de+Surco,+Lima,+Perú" target="_blank" rel="noopener noreferrer">
            <img src="/image/pages/mapa.svg" alt="Mapa de Studio">
        </a>
    </div>
</section>

{{-- FAQ --}}
<section class="section" id="faq">
    <div class="containerFaq">
        <h2 class="section-title">FAQs</h2>
        <p class="faq-subtitle">¿Tienes dudas? Resolvemos todo.</p>

        <div class="faq-container">
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
                    Nuestro estudio principal está ubicado en Avenida Surco 123, Santiago de Surco, Lima, Perú. Pronto abriremos más sedes en otras zonas.
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
        </div>
    </div>
</section>

<!-- {{-- Membresías --}}
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
</section> -->

</x-app>
