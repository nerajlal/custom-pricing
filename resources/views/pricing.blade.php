@extends('layouts.public')

@section('title', 'Pricing – Custom Pricing & Loyalty')
@section('meta_description', 'Custom Pricing & Loyalty is free to install. Pay nothing until your store is growing. Simple, transparent pricing.')

@section('content')

<section class="p-hero">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow">💳 Pricing</div>
        <h1 class="p-hero__title">Simple, <span>Transparent</span> Pricing</h1>
        <p class="p-hero__subtitle">Start free. Scale as you grow. No hidden fees, no contracts.</p>
    </div>
</section>

<section class="p-page">

    <div class="p-grid p-grid--3" style="align-items:start; margin-bottom:48px;">

        {{-- Starter Plan --}}
        <div class="p-card" style="border:2px solid var(--p-color-border);">
            <div style="margin-bottom:20px;">
                <span class="p-badge p-badge--info">Starter</span>
            </div>
            <div style="font-size:40px; font-weight:800; color:var(--p-color-text); margin-bottom:4px;">$19</div>
            <div style="font-size:13px; color:var(--p-color-text-subdued); margin-bottom:24px;">per month</div>
            <ul class="p-list">
                <li><i class="icon">✓</i> Up to 500 vouchers/month</li>
                <li><i class="icon">✓</i> All active rules</li>
                <li><i class="icon">✓</i> Campaign dashboard</li>
                <li><i class="icon">✓</i> Advanced analytics</li>
                <li><i class="icon">✓</i> Webhook-driven rewards</li>
            </ul>
            <div style="margin-top:24px;">
                <a href="{{ route('install') }}" class="p-btn p-btn--plain" style="width:100%; justify-content:center;">Start 14-Day Free Trial</a>
            </div>
        </div>

        {{-- Pro Plan --}}
        <div class="p-card" style="border:2px solid var(--p-color-interactive); position:relative;">
            <div style="position:absolute; top:-14px; left:50%; transform:translateX(-50%);">
                <span class="p-badge p-badge--success" style="box-shadow:0 2px 8px rgba(0,128,96,0.3);">⭐ Most Popular</span>
            </div>
            <div style="margin-bottom:20px; margin-top:8px;">
                <span class="p-badge p-badge--success">Pro</span>
            </div>
            <div style="font-size:40px; font-weight:800; color:var(--p-color-interactive); margin-bottom:4px;">$49</div>
            <div style="font-size:13px; color:var(--p-color-text-subdued); margin-bottom:24px;">per month</div>
            <ul class="p-list">
                <li><i class="icon">✓</i> Unlimited vouchers</li>
                <li><i class="icon">✓</i> Budget cap alerts</li>
                <li><i class="icon">✓</i> Manual credit tool</li>
                <li><i class="icon">✓</i> Priority email support</li>
                <li><i class="icon">✓</i> Multi-campaign management</li>
            </ul>
            <div style="margin-top:24px;">
                <a href="{{ route('install') }}" class="p-btn p-btn--primary" style="width:100%; justify-content:center;">Start 14-Day Free Trial</a>
            </div>
        </div>

        {{-- Enterprise --}}
        <div class="p-card" style="border:2px solid var(--p-color-border);">
            <div style="margin-bottom:20px;">
                <span class="p-badge p-badge--warning">Enterprise</span>
            </div>
            <div style="font-size:40px; font-weight:800; color:var(--p-color-text); margin-bottom:4px;">$99</div>
            <div style="font-size:13px; color:var(--p-color-text-subdued); margin-bottom:24px;">per month</div>
            <ul class="p-list">
                <li><i class="icon">✓</i> Everything in Pro</li>
                <li><i class="icon">✓</i> Custom tier probabilities</li>
                <li><i class="icon">✓</i> Dedicated onboarding</li>
                <li><i class="icon">✓</i> API access</li>
            </ul>
            <div style="margin-top:24px;">
                @php
                    $shopParam = request()->get('shop') ? '?shop=' . request()->get('shop') : '';
                @endphp
                <a href="{{ route('support') . $shopParam }}" class="p-btn p-btn--plain" style="width:100%; justify-content:center;">Contact Us</a>
            </div>
        </div>

    </div>

    {{-- FAQ --}}
    <div class="p-section">
        <h2 class="p-section__title">Frequently Asked Questions</h2>
    </div>

    @php
    $faqs = [
        ['Do you offer a free trial?', 'Yes! Every paid plan comes with a **14-day free trial**. You can explore all features risk-free and cancel anytime before the trial ends.'],
        ['What counts as a voucher?', 'Every coin/voucher issued to a customer counts. If a customer places an order and wins a coin, that\'s 1 voucher recorded in your dashboard.'],
        ['Can I upgrade or downgrade at any time?', 'Yes. Plans are billed monthly through Shopify\'s billing system and can be changed instantly from your Shopify admin settings.'],
        ['Does Shopify handle the billing?', 'Yes. All payments are processed securely through Shopify Billing — we never store your credit card details.'],
        ['What happens when I reach my voucher limit?', 'On the Starter plan, voucher issuance pauses until the start of the next billing cycle. Upgrade to Pro for unlimited vouchers.'],
    ];
    @endphp

    <div class="p-card">
        @foreach($faqs as $faq)
        <details style="padding:16px 0; border-bottom:1px solid var(--p-color-border);" {{ $loop->first ? 'open' : '' }}>
            <summary style="font-weight:600; font-size:14px; cursor:pointer; list-style:none; display:flex; justify-content:space-between; align-items:center;">
                {{ $faq[0] }}
                <span style="color:var(--p-color-interactive); font-size:18px; transition:transform 0.2s;">+</span>
            </summary>
            <p style="margin-top:10px; font-size:13px; color:var(--p-color-text-secondary); line-height:1.6;">{{ $faq[1] }}</p>
        </details>
        @endforeach
        <div style="padding-top:16px"></div>
    </div>

    {{-- CTA --}}
    <div class="p-callout p-callout--success" style="margin-top:40px;">
        <div class="p-callout__icon">🚀</div>
        <div class="p-callout__body">
            <h4>Ready to get started?</h4>
            <p>Install Custom Pricing & Loyalty from the Shopify App Store. Free plan available with no setup required. <a href="{{ route('install') }}">Add to Shopify →</a></p>
        </div>
    </div>

</section>
@endsection
