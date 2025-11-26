
<x-app>

    <style>
        * {
            /* outline: 1px solid red; */
        }


        .verificacion__titulo {
            color: #5D6D7A;
            font-weight: 500;
            font-size: 2.5rem;
        }

        .verificacion__parrafo {
            color: #303840;
            font-size: 1.3rem;
        }

        .verificacion__btn {
            color: white;
            border-radius: 32px;
            background: var(--Gradient-Bicolor, linear-gradient(135deg, #B0694C 31.48%, #A267B4 113.36%));

            /* purple shadow */
            box-shadow: 0 1px 2px 0 rgba(67, 54, 84, 0.30), 0 1px 3px 1px rgba(67, 54, 84, 0.15);
        }
    </style>

    <div class="verificacion">

        <div class="verificacion__container container">
            <div
                class="verificacion__contenido min-h-96 bg-white mt-36 mb-24 rounded-3xl p-8 lg:p-14 flex flex-col gap-4 justify-center items-center content-center place-content-center
">

                <img class="h-52 w-52 object-contain" src="{{ asset('image/emails/activacion/success.png') }}"
                    alt="">

                <h2 class="verificacion__titulo text-center"> ¡Tu correo está verificado!</h2>

                <p class="verificacion__parrafo text-center">
                    {{ $message }}
                </p>

                <img class="h-6 object-contain" src="{{ asset('image/emails/activacion/logo-arcoiris.png') }}"
                    alt="">

                <div class="w-full flex justify-center">
                    <a class="btn verificacion__btn w-auto lg:w-80" href="{{ route('home') }}" class="btn">
                        Ir al inicio
                    </a>
                </div>

            </div>
        </div>

    </div>

</x-app>
