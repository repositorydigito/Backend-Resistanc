@php
use App\Services\LegalContentService;

$privacyPolicy = LegalContentService::getPrivacyPolicy();
$privacyFaqs = LegalContentService::getPrivacyFaqs();
@endphp

<x-app>
            {{-- Header Section --}}
            <section class="hero" id="inicio">
                <div class="containerBanner">
                    <h1>{{ $privacyPolicy->title ?? 'Políticas de Privacidad' }}</h1>
                    @if($privacyPolicy && $privacyPolicy->subtitle)
                        <p class="subtitle">{{ $privacyPolicy->subtitle }}</p>
                    @endif
                </div>
            </section>

            {{-- FAQs Section --}}
            @if($privacyFaqs->count() > 0)
                <section class="section" id="faq">
                    <div class="containerFaq">
                        <div class="faq-container">
                            @foreach($privacyFaqs as $faq)
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

    <style>
        .faq-answer {
            line-height: 1.6;
        }
        
        .faq-answer p {
            margin: 0.5rem 0;
        }
        
        .faq-answer ul, .faq-answer ol {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }
        
        .faq-answer li {
            margin: 0.25rem 0;
        }
        
        .faq-answer strong {
            color: #B0694C;
            font-weight: 600;
        }
        
        .faq-answer h2, .faq-answer h3 {
            color: #B0694C;
            margin: 1rem 0 0.5rem 0;
        }
        
        .faq-answer a {
            color: #B0694C;
            text-decoration: underline;
        }
        
        .faq-answer a:hover {
            color: #8B5A3C;
        }
    </style>
</x-app>