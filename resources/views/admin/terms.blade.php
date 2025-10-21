<x-app>

    @push('css')
        <style>
            .hero {
                background:
                    linear-gradient(91deg, rgba(176, 105, 76, 0.70) -12.38%, rgba(162, 103, 180, 0.70) 27.49%, rgba(174, 159, 176, 0.70) 56.83%, rgba(106, 111, 74, 0.70) 80.27%, rgba(33, 106, 176, 0.70) 127.38%),
                    url({{ asset('image/pages/banner1.png') }});
                background-size: cover;
                background-position: center;
            }

            /* Estilos personalizados para FAQ */
            .privacy__container details {
                position: relative;
            }

            .privacy__container details summary {
                list-style: none;
                cursor: pointer;
                position: relative;
                padding-right: 40px;
                font-weight: 600;
            }

            .privacy__container details summary::-webkit-details-marker {
                display: none;
            }

            .privacy__container details summary::before {
                content: '+';
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                font-size: 24px;
                font-weight: bold;
                color: #B66F37;
                transition: all 0.3s ease;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(182, 111, 55, 0.1);
            }

            .privacy__container details[open] summary::before {
                content: '−';
                background: radial-gradient(128.53% 138.92% at 7.28% -1.41%, #CD6134 0%, #925035 29.32%, #9142AA 66.83%, #A267B4 100%);
                color: white;
            }
        </style>
    @endpush

    {{-- Hero --}}
    <section class="hero h-[calc(100vh-8rem)] items-center justify-center content-center pt-6">
        <div class="container ">

            <div  data-aos="fade-up" class="grid gap-8 w-full   text-white">
                <h1 class="text-6xl  font-extrabold tracking-[8px] uppercase">
                    Términos y condiciones de uso de servicios en RSISTANC STUDIO
                </h1>


            </div>



        </div>
    </section>


    {{-- FAQ --}}
    <section class="privacy py-24" id="faq">
        <div class="container">

            <div class="privacy__container grid gap-3 ckeditor">

                @foreach ($terms as $term)
                    <details class="faq-item p-5 bg-white rounded-2xl">
                        <summary class="faq-question text-lg">
                            {{ $term->title }}
                        </summary>
                        <div class="faq-answer">
                            {!! $term->content !!}
                        </div>
                    </details>
                @endforeach



            </div>
        </div>
    </section>

    @push('js')
    @endpush
</x-app>
