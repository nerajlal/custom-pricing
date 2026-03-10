@extends('layouts.public')

@section('title', 'Support – Custom Pricing & Loyalty')
@section('meta_description', 'Get help with Custom Pricing & Loyalty Rewards. Browse FAQs, read documentation or contact our support team.')

@section('content')

<section class="p-hero">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow">💬 Support Center</div>
        <h1 class="p-hero__title">How can we <span>help</span> you?</h1>
        <p class="p-hero__subtitle">Find answers to common questions about custom pricing, loyalty points, and app setup.</p>
    </div>
</section>

<section class="p-page">

    {{-- Contact Cards --}}
    <div class="p-grid p-grid--3" style="margin-bottom:48px;">
        <div class="p-card" style="text-align:center; padding:32px 24px;">
            <div style="font-size:36px; margin-bottom:12px;">📧</div>
            <h3 style="font-size:16px; font-weight:700; margin-bottom:6px;">Email Support</h3>
            <p style="font-size:13px; color:var(--p-color-text-secondary); margin-bottom:16px; line-height:1.6;">We respond to all merchant inquiries within 24 hours.</p>
            @php
                $supportEmail = $brand['support_email'] ?? 'apps@task19.com';
            @endphp
            <a href="mailto:{{ $supportEmail }}" class="p-btn p-btn--primary">Send a Message</a>
        </div>
        <div class="p-card" style="text-align:center; padding:32px 24px;">
            <div style="font-size:36px; margin-bottom:12px;">📖</div>
            <h3 style="font-size:16px; font-weight:700; margin-bottom:6px;">Documentation</h3>
            <p style="font-size:13px; color:var(--p-color-text-secondary); margin-bottom:16px; line-height:1.6;">Learn how to configure advanced pricing rules and rewards.</p>
            <a href="{{ route('admin.documentation') }}" class="p-btn p-btn--plain">View Docs</a>
        </div>
        <div class="p-card" style="text-align:center; padding:32px 24px;">
            <div style="font-size:36px; margin-bottom:12px;">🗺️</div>
            <h3 style="font-size:16px; font-weight:700; margin-bottom:6px;">Setup Guide</h3>
            <p style="font-size:13px; color:var(--p-color-text-secondary); margin-bottom:16px; line-height:1.6;">Follow our step-by-step guide for a perfect installation.</p>
            <a href="{{ route('installation') }}" class="p-btn p-btn--plain">Read Guide</a>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="p-section">
        <h2 class="p-section__title">Frequently Asked Questions</h2>
    </div>

    @php
    $categories = [
        [
            'icon' => '⚙️',
            'title' => 'Installation & Setup',
            'faqs' => [
                ['How do I activate the pricing widget?', 'After installing the app, navigate to the "Theme Integration" section in your dashboard. Click "Enable Widget" to automatically add the pricing engine to your Shopify theme.'],
                ['Does this app require coding skills?', 'No! All features are designed to work out-of-the-box. We use Shopify\'s latest app blocks and script tags to ensure a zero-code setup for most themes.'],
            ]
        ],
        [
            'icon' => '🏷️',
            'title' => 'Custom Pricing & Tiers',
            'faqs' => [
                ['How do I set different prices for VIP customers?', 'You can create "Pricing Groups" based on customer tags (e.g., "Wholesale" or "VIP"). Define the discount percentage or fixed price for each tag, and it will apply automatically when they log in.'],
                ['Can I set individual prices for specific customers?', 'Yes. Use the "Customer Specific Pricing" tool to search for a customer and set a unique price for any product in your catalog.'],
                ['Will the discounted prices show for all visitors?', 'No. Custom prices are only visible to eligible logged-in customers. Regular visitors will continue to see your standard retail prices.'],
            ]
        ],
        [
            'icon' => '🪙',
            'title' => 'Loyalty & Rewards',
            'faqs' => [
                ['How do customers earn points?', 'You can configure "Earning Rules" such as 1 point for every $1 spent. Points are automatically calculated and added to the customer\'s balance after their order is marked as paid.'],
                ['How can customers redeem their points?', 'Customers can redeem points for discount codes directly through the loyalty widget on your storefront. You can set the "Points-to-Cash" ratio in your dashboard settings.'],
                ['Can I manually adjust a customer\'s balance?', 'Absolutely. In the "Customer Management" section of the app admin, you can search for any customer and manually add or deduct points.'],
            ]
        ],
        [
            'icon' => '🔐',
            'title' => 'Privacy & Billing',
            'faqs' => [
                ['How is the app billed?', 'All billing is handled securely via the Shopify Billing API. You will see the app charges on your regular monthly Shopify invoice.'],
                ['What data is shared with the app?', 'We only access data necessary for the app to function, such as Customer IDs (for tag-based pricing) and Order details (for loyalty points). We are fully GDPR compliant.'],
            ]
        ],
    ];
    @endphp

    @foreach($categories as $cat)
    <div style="margin-bottom:32px;">
        <h3 style="font-size:16px; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            {{ $cat['icon'] }} {{ $cat['title'] }}
        </h3>
        <div class="p-card">
            @foreach($cat['faqs'] as $faq)
            <details style="padding:14px 0; border-bottom:1px solid var(--p-color-border);" {{ $loop->first ? 'open' : '' }}>
                <summary style="font-weight:600; font-size:14px; cursor:pointer; list-style:none; display:flex; justify-content:space-between; align-items:center;">
                    {{ $faq[0] }}
                    <span style="color:var(--p-color-interactive); font-size:18px;">+</span>
                </summary>
                <p style="margin-top:10px; font-size:13px; color:var(--p-color-text-secondary); line-height:1.6;">{!! $faq[1] !!}</p>
            </details>
            @endforeach
            <div style="padding-top:14px"></div>
        </div>
    </div>
    @endforeach

</section>
@endsection
