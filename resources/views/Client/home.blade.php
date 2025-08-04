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
                background-image: url('{{ asset('image/pages/header.png') }}');
                background-size: cover;
                background-position: center;
                color: white;
                overflow: hidden;
                position: relative;
                font-family: var(--font-one);
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
                z-index: 1;
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
                grid-template-columns: 27% 70%;
                align-items: center;
                gap: 2rem;
            }
            .banner__one {
                background: url('{{ asset('image/pages/banner_one.png') }}');
                background-size: cover;
                background-position: center;
                padding: 2rem;
                display: grid;
                gap: 1rem;
                border-radius: 15px;
            }
            .banner__title{
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
            .banner__subtitle{
                font-size: 1rem;
                font-weight: 200;
                line-height: 1.2;
                color: #fff;
                font-family: var(--font-one);
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
                        <a href="#" class="">VER PAQUETES</a>
                    </div>
                    <div class="banner__two">
                        <img src="{{ asset('image/pages/banner.png') }}" alt="Banner Image"
                            class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>

        <div class="membresias">
            <div class="membresias__container container">

                <div class="py-5">
                    <h2>Membresías</h2>
                    <p>Elige la membresía que mejor se adapte a ti.</p>

                    <div class="">

                    </div>
                </div>

            </div>
        </div>
    </main>

</x-app>
