@php
use App\Services\LegalContentService;

$termsPolicy = LegalContentService::getTermsAndConditions();
$termsFaqs = LegalContentService::getTermsFaqs();
@endphp

<x-app>
    {{-- Header Section --}}
    <section class="hero" id="inicio">
        <div class="containerBanner">
            <h1>{{ $termsPolicy->title ?? 'Términos y Condiciones' }}</h1>
            @if($termsPolicy && $termsPolicy->subtitle)
                <p class="subtitle">{{ $termsPolicy->subtitle }}</p>
            @endif
        </div>
    </section>

    {{-- FAQs Section --}}
    @if($termsFaqs->count() > 0)
        <section class="section" id="faq">
            <div class="containerFaq">
                <div class="faq-container">
                    @foreach($termsFaqs as $faq)
                        <details class="faq-item">
                            <summary class="faq-question">
                                {{ $faq->question }}
                            </summary>
                            <div class="faq-answer">
                                {!! $faq->answer !!}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-app>