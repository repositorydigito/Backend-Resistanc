<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verifica tu cuenta</title>
    <style>
        * {
            /* outline: 1px solid red; */
        }

        main {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .maincontainer {
            background-color: #e7e7e7;
            padding: 3rem 2rem;

        }

        .maincontainer__content {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            border-radius: 30px;
            padding: 1.5rem;
        }

        .content__body {}

        .header {
            display: grid;
            place-items: center;
            text-align: center;
            gap: 1rem;
        }

        .header__logo {
            /* background: red; */
            width: 100%;
            display: grid;
            place-items: center;
            place-content: center;
        }

        .logo {
            /* width: 50px; */
            height: 50px;
            object-fit: contain;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <main class="main">
        <div class="maincontainer">
            <div class="maincontainer__content">
                <div class="header">
                    {{-- <h1>¡Bienvenido/a a la comunidad</h1> --}}
                    <div class="header__logo">
                        <img class=" logo"
                            src="https://raw.githubusercontent.com/cr0ybot/ingress-logos/master/resistance_hexagon/ingress-resistance.png"
                            alt="">
                    </div>

                </div>

                <div class="content">
                    <h2>¡Hola, {{ $user->name }}!</h2>

                    <div class="content__body">
                        {!! $data->body !!}
                    </div>


                    <a href="{{ $verificationUrl }}" class="button">Verificar mi cuenta</a>

                    <p>Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:</p>
                    <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">
                        {{ $verificationUrl }}
                    </p>

                    <p><strong>Nota:</strong> Este enlace expirará en 60 minutos por seguridad.</p>

                    <p>Si no creaste una cuenta en Resistanc Studio, puedes ignorar este correo.</p>
                </div>

                <div class="footer">
                    <p>© {{ date('Y') }} Resistanc Studio. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                </div>

            </div>

        </div>

    </main>

</body>

</html>
