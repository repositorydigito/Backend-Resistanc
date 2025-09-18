<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Código de Recuperación de Contraseña</title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        html {
            background: #EFF0F2;
        }

        .main {
            background: #4a6cb0;
            height: 100vh;
            width: 100vw;
        }

        .code__content {

            display: grid;
            justify-content: center;
            justify-items: center;

        }

        .code {
            background: linear-gradient(94deg, #E7D4D8 0%, #E5D7EA 28.85%, #E7DFE9 50.48%, #D9D9D2 69.71%, #CDD6D7 100%);
            padding: .5rem 1rem;
            align-items: center;
            border-radius: 16px;
            font-size: 1.7rem;
            letter-spacing: .8rem;
            font-weight: 500;
            text-align: center;
            display: flex;
            justify-content: center;
            justify-items: center;
            max-width: 150px;
            width: 150px;
            margin: auto;
        }

        .main {
            display: grid;
            padding: 2rem;
        }

        .fondo {

            background: #EFF0F2;
            display: grid;
            justify-content: center;
            align-content: center;
            padding: 5rem 2rem;
        }

        .card {
            background: #fff;
            max-width: 550px;
            width: 550px;
            margin: auto;
            padding: 1.5rem;
        }

        .card__content--negrita {
            font-weight: 600;
            color: #B0694C;
            background: #fff;
        }

        .card__title {
            font-size: 1.3rem;
        }

        .card__title--negrita {
            font-weight: 800;
        }

        .card__saludo {
            font-size: 1rem;
        }

        .card__body>* {
            text-align: center;
        }
    </style>
</head>

<body>
    <main class="main">

        <div class="fondo">
            <div class="card">

                <div class="card__content">

                    <h2 class="card__title">
                        ¡DON’T <span class="card__title--negrita">WORRY!</span>
                    </h2>

                    <h1 class="card__saludo">Hola {{ $user->name }}, </h1>

                    <div class="card__body">
                        {!! $data->body !!}
                    </div>
                    <div class="code__content">
                        <span class="code">
                            {{ $code }}
                        </span>
                    </div>


                </div>


            </div>
        </div>



    </main>
</body>

</html>
