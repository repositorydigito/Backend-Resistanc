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
        </style>
    @endpush

    {{-- Hero --}}
    <section class="hero h-[calc(100vh-8rem)] items-center justify-center content-center pt-6">
        <div class="container ">

            <div data-aos="fade-up" class="grid gap-8 w-full   text-white">
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

                @foreach ($privacies as $privacy)
                    <details class="faq-item p-5 bg-white rounded-2xl">
                        <summary class="faq-question text-lg">
                            {{ $privacy->title }}
                        </summary>
                        <div class="faq-answer">
                            {!! $privacy->content !!}
                        </div>
                    </details>
                @endforeach



            </div>
        </div>
    </section>

    @push('js')
    @endpush
</x-app>
