@extends('layouts.public')

@section('title', 'Terms of Service')

@section('content')
<section class="p-hero" style="padding:48px 24px;">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow">🔒 Legal</div>
        <h1 class="p-hero__title">Terms of <span>Service</span></h1>
        <p class="p-hero__subtitle">Last updated: {{ date('F d, Y') }}</p>
    </div>
</section>

<div class="p-page" style="padding-top: 40px; padding-bottom: 80px;">
    
    <!-- Agreement to Terms -->
    <div style="margin-bottom: 48px; text-align: center;">
        <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 12px;">Agreement to Terms</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); line-height: 1.6; max-width: 500px; margin: 0 auto;">
            By installing or using Custom Pricing & Loyalty Rewards, you agree to these Terms of Service. Please read them carefully before using our app.
        </p>
    </div>

    <!-- Permissions and Features Grid (3 columns) -->
    <div class="p-grid p-grid--3" style="margin-bottom: 24px;">
        <!-- What We Provide -->
        <div class="p-card" style="padding: 24px;">
            <div style="font-size: 24px; margin-bottom: 16px;">📱</div>
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 16px;">What We Provide</h3>
            <ul style="border-top: 1px solid var(--p-color-border);">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Tier-based pricing rules</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Loyalty points system</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Automated reward logic</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Customer specific pricing</li>
                <li style="padding: 12px 0; font-size: 13px; color: var(--p-color-text-secondary);">Storefront pricing scripts</li>
            </ul>
        </div>

        <!-- What You Can Do -->
        <div class="p-card" style="padding: 24px;">
            <div style="font-size: 24px; margin-bottom: 16px;">✅</div>
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 16px;">What You Can Do</h3>
            <ul style="border-top: 1px solid var(--p-color-border);">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Configure VIP pricing tiers</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Reward loyal customers</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Contact support via email</li>
                <li style="padding: 12px 0; font-size: 13px; color: var(--p-color-text-secondary);">Uninstall at any time</li>
            </ul>
        </div>

        <!-- What You Can't Do -->
        <div class="p-card" style="padding: 24px;">
            <div style="font-size: 24px; margin-bottom: 16px;">🚫</div>
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 16px;">What You Can't Do</h3>
            <ul style="border-top: 1px solid var(--p-color-border);">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Use for fraudulent discounts</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Abuse the points system</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Reverse engineer the app</li>
                <li style="padding: 12px 0; font-size: 13px; color: var(--p-color-text-secondary);">Violate Shopify Merchant rules</li>
            </ul>
        </div>
    </div>

    <!-- Responsibilities Grid (2 columns) -->
    <div class="p-grid p-grid--2" style="margin-bottom: 24px;">
        <!-- Your Responsibilities -->
        <div class="p-card" style="padding: 24px;">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;"><span>👤</span> Your Responsibilities</h3>
            <ul style="border-top: 1px solid var(--p-color-border);">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Accuracy of pricing rules</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Honoring reward points</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Merchant account security</li>
                <li style="padding: 12px 0; font-size: 13px; color: var(--p-color-text-secondary);">Compliant storefront scripts</li>
            </ul>
        </div>

        <!-- Our Responsibilities -->
        <div class="p-card" style="padding: 24px;">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;"><span>🛡️</span> Our Responsibilities</h3>
            <ul style="border-top: 1px solid var(--p-color-border);">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Maintain app functionality</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Provide reliable support</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Secure data processing</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border); font-size: 13px; color: var(--p-color-text-secondary);">Uptime for pricing scripts</li>
                <li style="padding: 12px 0; font-size: 13px; color: var(--p-color-text-secondary);">Comply with Shopify policies</li>
            </ul>
        </div>
    </div>

    <!-- Limitation of Liability block -->
    <div class="p-card" style="margin-bottom: 24px; padding: 24px; border: 2px solid var(--p-color-border);">
        <div class="p-grid p-grid--4" style="gap: 24px; align-items: start;">
            <div style="grid-column: span 1; display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 24px;">⚖️</div>
                <h3 style="font-size: 14px; font-weight: 700;">Limitation of Liability</h3>
            </div>
            
            <div style="grid-column: span 1;">
                <p style="font-size: 12px; color: var(--p-color-text-secondary); line-height: 1.5;">To the maximum extent permitted by law:</p>
            </div>
            
            <div style="grid-column: span 1;">
                <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 4px;">No Indirect Damages</h4>
                <p style="font-size: 12px; color: var(--p-color-text-secondary); line-height: 1.5;">We're not liable for lost revenue due to rule errors.</p>
            </div>
            
            <div style="grid-column: span 1;">
                <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 4px;">As-Is Service</h4>
                <p style="font-size: 12px; color: var(--p-color-text-secondary); line-height: 1.5;">Provided without any express warranties.</p>
            </div>
        </div>
    </div>

    <!-- Privacy & Termination Grid (2 columns) -->
    <div class="p-grid p-grid--2" style="margin-bottom: 48px;">
        <!-- Privacy & Data -->
        <div class="p-card" style="padding: 24px;">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;"><span>🔒</span> Privacy & Data</h3>
            <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                We collect minimal data to calculate points. See our <a href="{{ route('privacy-policy') }}" style="color: var(--p-color-interactive); font-weight: 600;">Privacy Policy</a> for complete details.
            </p>
        </div>

        <!-- Termination -->
        <div class="p-card" style="padding: 24px;">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;"><span>⏹</span> Termination</h3>
            <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                You may terminate by uninstalling. Data is removed within 48h per Shopify compliance standards.
            </p>
        </div>
    </div>

    <!-- Questions Alert -->
    <div class="p-callout p-callout--info" style="align-items: center; padding: 16px 24px;">
        <div class="p-callout__icon">💬</div>
        <div class="p-callout__body" style="display: flex; align-items: center; gap: 8px;">
            <strong style="font-size: 14px;">Questions?</strong>
            <p style="font-size: 13px; color: var(--p-color-text-secondary); margin: 0;">
                Contact us for clarification or legal inquiries. <a href="mailto:{{ env('MAIL_FROM_ADDRESS', 'apps@task19.com') }}" style="color: var(--p-color-interactive); font-weight: 600;">Contact Support →</a>
            </p>
        </div>
    </div>
</div>
@endsection
