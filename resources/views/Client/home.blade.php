<x-app>

    @push('css')
        <style>
            * {
                /* outline: 1px solid red */
            }

            .header__content {
                padding-top: 70px;
                height: 95vh;
                display: flex;
                align-items: center;

            }

            .header {
                background-image: url('{{ asset('image/pages/header__two.jpeg') }}');
                background-size: cover;
                background-position: 80% center;
                /* Ajusta este valor según sea necesario */
                color: white;
                overflow: hidden;
                position: relative;
                font-family: var(--font-one);
                margin-top: -70px;
                z-index: -1
            }

            .header::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(88deg, #B66F37 20%, rgba(157, 90, 169, 0.90) 40%, rgba(181, 130, 190, 0.70) 60.44%, rgba(255, 255, 255, 0.00) 80%);
            }

            .header>* {
                position: relative;
                z-index: 100;
            }

            .header__title {
                font-size: 4.5rem;
                line-height: 1.2;
                font-weight: 200;
                text-transform: uppercase;
            }

            .header__title strong {
                font-weight: 700;
            }

            .header__subtitle {
                font-size: 1.5rem;
                font-weight: 300;
                margin-top: 1rem;
            }

            .header__text {
                max-width: 830px;
            }

            .header__subtitle {
                max-width: 500px;
            }

            .main {
                background: linear-gradient(94deg, #CDD6D7 0%, #D9D9D2 30.29%, #E7DFE9 49.52%, #E5D7EA 71.15%, #E7D4D8 100%);
                display: grid;
                gap: 2rem;
                padding: 2rem 0;
            }

            .disciplinas__container {
                background: #fff;
                border-radius: 10px;
            }

            .disciplinas__content {
                padding: 1.3rem;

            }

            .disciplinas__title {
                font-size: 2.5rem;
                font-weight: 400;
                text-transform: uppercase;
                font-family: var(--font-one);
                margin-bottom: 1.2rem;
                color: #5D6D7A;

            }

            .disciplina__card {
                height: 300px;
                border-radius: 10px;
                overflow: hidden;
                display: grid;
                align-content: flex-end;
                color: white;
                padding: 1rem;
            }

            .disciplina__card--cycling {
                background: url('');
                background-size: cover;
                background-position: center;


            }

            .disciplina__card--title {
                font-size: 1.5rem;
                font-weight: 500;
                text-transform: uppercase;
            }

            .disciplina__card--paragrahp {
                font-size: 1rem;
                font-weight: 400;
            }

            .banner__content {
                display: grid;
                grid-template-columns: 27.5% 70%;
                justify-content: space-between;
                /* align-items: center; */
                gap: 1rem;
                height: 350px;
            }

            .banner__one {
                background: url('{{ asset('image/pages/banner_one.png') }}');
                background-size: cover;
                background-position: center;
                padding: 2rem;
                display: grid;
                align-content: end;
                gap: 1rem;
                border-radius: 15px;
            }

            .banner__title {
                font-size: 2rem;
                font-weight: 300;
                line-height: 1.2;
                color: #fff;
                text-transform: uppercase;
                font-family: var(--font-one);
            }

            .banner__title strong {
                font-weight: 700;

            }

            .banner__subtitle {
                font-size: 1rem;
                font-weight: 200;
                line-height: 1.2;
                color: #fff;
                font-family: var(--font-one);
            }

            .apps__content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
                padding: 1.5rem 1.5rem 0 1.5rem;
                /* padding-top:1rem;
                        padding-left: 1rem;
                        padding-right: 1rem; */
                background: #fff;
                border-radius: 10px;
            }

            .apps__texts {
                display: grid;
                align-content: center;
                gap: 1rem;
            }

            .apps__texts--title {
                font-size: 3rem;
                font-weight: 300;
                line-height: 1.2;
                color: #5D6D7A;
                text-transform: uppercase;
                font-family: var(--font-one);
            }

            .apps__texts--title strong {
                font-weight: 700;
            }

            .apps__texts p {
                font-size: 1.2rem;
                font-weight: 400;
                line-height: 1.2;
                color: #5D6D7A;
            }

            .apps__text--app {
                font-size: 0.8rem !important;
                font-weight: 400;
                line-height: 1.2;
                color: #5D6D7A;
            }

            .apps__img img {
                object-fit: contain;
                width: 100%;
                height: 100%;
            }

            /* Estilos para la sección de preguntas frecuentes */
            .preguntas__content {
                backdrop-filter: blur(14px) saturate(180%);
                -webkit-backdrop-filter: blur(14px) saturate(180%);
                background-color: rgba(255, 255, 255, 0.24);
                border-radius: 12px;
                border: 1px solid rgba(209, 213, 219, 0.3);
                /* border-radius: 10px; */
                padding: 2rem;
            }

            .preguntas__title {
                font-size: 2.5rem;
                font-weight: 400;
                text-transform: uppercase;
                font-family: var(--font-one);
                margin-bottom: 1rem;
                color: #5D6D7A;
                text-align: center;
            }

            .preguntas__accordion {
                display: grid;
                /* grid-template-columns: 1fr 1fr; */
                gap: 1rem;
                /* max-width: 800px; */
                /* margin: 0 auto; */
            }

            .pregunta__item {
                /* border-bottom: 1px solid #E5E7EB; */
                background: #fff;
                /* margin-bottom: 0.5rem; */
                padding: 1rem 2rem;
                border-radius: 10px;
            }

            .pregunta__item:last-child {
                border-bottom: none;
            }

            .pregunta__button {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                /* padding: 1.5rem 0; */
                background: none;
                border: none;
                cursor: pointer;
                font-size: 1.1rem;
                font-weight: 500;
                color: #5D6D7A;
                text-align: left;
                transition: all 0.3s ease;
                font-family: var(--font-one);
            }

            .pregunta__button:hover {
                color: #B66F37;
            }

            .pregunta__button--active {
                color: #B66F37;
                font-weight: 600;
            }

            .pregunta__icon {
                transition: transform 0.3s ease;
                color: #5D6D7A;
                flex-shrink: 0;
            }

            .pregunta__icon--active {
                transform: rotate(180deg);
                color: #B66F37;
            }

            .pregunta__content {
                /* padding: 0 0 1.5rem 0; */
                color: #6B7280;
                line-height: 1.6;
                font-size: 1rem;
                max-height: 0;
                overflow: hidden;
                opacity: 0;
                transform: translateY(-10px);
                transition: all 0.3s ease;
            }

            .pregunta__content p {
                margin: 0;
            }
        </style>
    @endpush


    <div class="header">
        <div class="header__container container">

            <div class="header__content">
                <div class="header__text">
                    <h1 class="header__title"><strong class="">Train</strong> your <strong>RSISTANC.</strong>
                        Live <strong>unstoppable.</strong></h1>

                    <p class="header__subtitle">Clases que te transforman. Energía que te eleva. Una comunidad que te
                        empuja a más.</p>

                    <div class="mt-5 buttons flex gap-5">
                        <a href="#" class="btn btn__one">Empieza hoy</a>
                        <a href="#" class="btn btn__two ">Reserva tu clase de prueba</a>
                    </div>
                </div>

            </div>



        </div>
    </div>


    <main class="main">



        <div class="disciplinas">

            <div class="disciplinas__container container">

                <div class="disciplinas__content">
                    <h2 class="disciplinas__title">
                        Elige cómo quieres <strong>moverte.</strong>
                    </h2>
                    <div class="disciplinas__cards grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">



                        @foreach ($disciplines as $item)
                            <div class="disciplina__card"
                                style="background-image: url('{{ $item->image_url ?? asset('image/pages/cycling.png') }}');">
                                <div class="flex gap-2 items-center">
                                    <img class="w-5 h-5" src="{{ asset('image/pages/logo.png') }}" alt="Indoor Cycling">
                                    <h3 class="disciplina__card--title">{{ $item->name }}</h3>
                                </div>

                                <p class="disciplina__card--paragrahp">{{ $item->description }}</p>
                            </div>
                        @endforeach

                    </div>
                </div>

            </div>

        </div>

        <div class="banner">
            <div class="banner__container container">
                <div class="banner__content">

                    <div class="banner__one">
                        <h2 class="banner__title">Paquetes <br> que se adaptan <strong> a tu ritmo.</strong></h2>
                        <p class="banner__subtitle"><strong>Desde 1 hasta 40 clases.</strong></p>
                        <p class="banner__subtitle">Mixea disciplinas, suma puntos, sube de nivel.</p>
                        <a href="#" class="text-white font-bold text-lg">VER PAQUETES</a>
                    </div>
                    <div class="banner__one">
                        <h2 class="banner__title">Paquetes <br> que se adaptan <strong> a tu ritmo.</strong></h2>
                        <p class="banner__subtitle"><strong>Desde 1 hasta 40 clases.</strong></p>
                        <p class="banner__subtitle">Mixea disciplinas, suma puntos, sube de nivel.</p>
                        <a href="#" class="text-white font-bold text-lg">VER PAQUETES</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="apps">
            <div class="apps__container container">

                <div class="apps__content">

                    <div class="apps__img">
                        <img class="" src="{{ asset('image/pages/mockups.png') }}" alt="App RSISTANC">

                    </div>

                    <div class="apps__texts">
                        <h3 class="apps__texts--title"><strong>Tu RSISTANC</strong><br> va contigo.</h3>
                        <p>Reserva, compra, suma puntos y ve tu progreso<br> desde nuestra app.</p>
                        <h4 class="text-lg font-bold text-gray-600">Simple, rápida, tuya.</h4>
                        <div class="apps__buttons flex gap-4">

                            <a href="" class="flex gap-2 items-center p-1.5 border border-gray-300 rounded-lg">
                                <img class="object-contain w-5 h-5" src="{{ asset('image/pages/apple.png') }}"
                                    alt="">
                                <p class="apps__text--app">Descargala en <br> <strong class="">App Store</strong>
                                </p>
                            </a>
                            <a href="" class="flex gap-2 items-center p-1.5 border border-gray-300 rounded-lg">
                                <img class="object-contain w-5 h-5" src="{{ asset('image/pages/Playstore.png') }}"
                                    alt="">
                                <p class="apps__text--app">Descargala en <br> <strong>Google Play</strong></p>
                            </a>
                        </div>
                    </div>

                </div>


            </div>
        </div>


        <div class="preguntas">
            <div class="preguntas__container container">
                <div class="preguntas__content">
                    <div class="mb-5 text-center">
                        <h2 class="preguntas__title"><strong>FAQs</strong></h2>
                        <p class="text-gray-600 text-lg">¿Tienes dudas? Resolvemos todo.</p>
                    </div>

                    <div class="preguntas__accordion" id="faqAccordion">

                        <div class="pregunta__item" data-faq-item="1">
                            <button class="pregunta__button" data-faq-button="1">
                                <span>¿Qué incluye mi membresía RSISTANC?</span>
                                <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="pregunta__content" data-faq-content="1">
                                <p>Tu membresía te da acceso a nuestras cuatro disciplinas: Cycling, Reformer, Pilates y
                                    Box. Además, acumulas puntos con cada clase que puedes canjear por recompensas
                                    exclusivas. Gestiona todo desde nuestra app.</p>
                            </div>
                        </div>

                        <div class="pregunta__item" data-faq-item="2">
                            <button class="pregunta__button" data-faq-button="2">
                                <span>¿Dónde se encuentran los estudios de RSISTANC?</span>
                                <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="pregunta__content" data-faq-content="2">
                                <p>Nuestros paquetes incluyen acceso a todas las disciplinas, equipamiento necesario,
                                    instructores certificados y seguimiento de tu progreso. Puedes elegir desde 1 hasta
                                    40 clases según tus necesidades.</p>
                            </div>
                        </div>

                        <div class="pregunta__item" data-faq-item="3">
                            <button class="pregunta__button" data-faq-button="3">
                                <span>¿Puedo combinar diferentes clases en mi paquete?</span>
                                <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="pregunta__content" data-faq-content="3">
                                <p>Sí, puedes cancelar o reprogramar tu clase hasta 2 horas antes del inicio sin
                                    penalización. Después de ese tiempo, se considerará como clase tomada.</p>
                            </div>
                        </div>

                        <div class="pregunta__item" data-faq-item="4">
                            <button class="pregunta__button" data-faq-button="4">
                                <span>¿Ofrecen clases de prueba para nuevos miembros?</span>
                                <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="pregunta__content" data-faq-content="4">
                                <p>No necesitas experiencia previa. Nuestros instructores adaptan las clases a todos los
                                    niveles, desde principiantes hasta avanzados. Te guiarán paso a paso para que
                                    disfrutes de una experiencia segura y efectiva.</p>
                            </div>
                        </div>

                        <div class="pregunta__item" data-faq-item="5">
                            <button class="pregunta__button" data-faq-button="5">
                                <span>¿Cómo puedo reservar una clase?</span>
                                <svg class="pregunta__icon" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="pregunta__content" data-faq-content="5">
                                <p>Ganas puntos por cada clase que tomas. A medida que acumulas puntos, subes de nivel y
                                    desbloqueas beneficios exclusivos como descuentos, clases especiales y acceso
                                    prioritario a nuevas disciplinas.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const accordion = document.getElementById('faqAccordion');
                const buttons = accordion.querySelectorAll('[data-faq-button]');
                let activeItem = null;

                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        const itemId = this.getAttribute('data-faq-button');
                        const content = document.querySelector(`[data-faq-content="${itemId}"]`);
                        const icon = this.querySelector('.pregunta__icon');

                        // Si el mismo item está activo, lo cerramos
                        if (activeItem === itemId) {
                            // Cerrar el item actual
                            content.style.maxHeight = '0px';
                            content.style.opacity = '0';
                            content.style.transform = 'translateY(-10px)';
                            this.classList.remove('pregunta__button--active');
                            icon.classList.remove('pregunta__icon--active');
                            activeItem = null;
                        } else {
                            // Cerrar el item anterior si existe
                            if (activeItem) {
                                const prevContent = document.querySelector(
                                    `[data-faq-content="${activeItem}"]`);
                                const prevButton = document.querySelector(
                                    `[data-faq-button="${activeItem}"]`);
                                const prevIcon = prevButton.querySelector('.pregunta__icon');

                                prevContent.style.maxHeight = '0px';
                                prevContent.style.opacity = '0';
                                prevContent.style.transform = 'translateY(-10px)';
                                prevButton.classList.remove('pregunta__button--active');
                                prevIcon.classList.remove('pregunta__icon--active');
                            }

                            // Abrir el nuevo item
                            content.style.maxHeight = content.scrollHeight + 'px';
                            content.style.opacity = '1';
                            content.style.transform = 'translateY(0)';
                            this.classList.add('pregunta__button--active');
                            icon.classList.add('pregunta__icon--active');
                            activeItem = itemId;
                        }
                    });
                });
            });
        </script>
    @endpush

</x-app>
