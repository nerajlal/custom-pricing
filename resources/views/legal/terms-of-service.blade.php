@extends('layouts.public')

@section('title', 'Terms of Service – Custom Pricing & Loyalty')

@section('content')

<!-- Hero Section -->
<section class="p-hero" style="background: linear-gradient(135deg, #f0f7f5 0%, #e3f1ed 100%); padding: 80px 24px;">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow" style="background: #fff; border-color: #95c9b4;">📖 Legal</div>
        <h1 class="p-hero__title" style="margin-bottom: 16px;">Terms of <span>Service</span></h1>
        <p class="p-hero__subtitle" style="margin-bottom: 0; font-size: 15px; color: #6d7175;"> Last Updated: December 12, 2025 </p>
    </div>
</section>

<section class="p-page" style="padding: 60px 24px;">

    <div class="p-section text-center" style="margin-bottom: 56px;">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 12px;">Agreement to Terms</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); max-width: 600px; margin: 0 auto;">
            By installing or using Custom Pricing & Loyalty Rewards, you agree to these Terms of Service. Please read them carefully before using our app.
        </p>
    </div>

    <!-- Triple Grid Provisons -->
    <div class="p-grid p-grid--3" style="margin-bottom: 48px;">
        <div class="p-card">
            <div style="font-size: 24px; margin-bottom: 16px;">📱</div>
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 8px;">What We Provide</h3>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Custom customer pricing display</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Loyalty points tracking system</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Automated tier assignments</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Storefront reward widgets</li>
                <li style="padding: 8px 0;">Admin management dashboard</li>
            </ul>
        </div>

        <div class="p-card">
            <div style="font-size: 24px; margin-bottom: 16px;">✅</div>
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 8px;">What You Can Do</h3>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Use for intended storefront purposes</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Configure reward settings</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Contact merchant support</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Manage customer loyalty points</li>
                <li style="padding: 8px 0;">Uninstall at any time</li>
            </ul>
        </div>

        <div class="p-card">
            <div style="font-size: 24px; margin-bottom: 16px;">🚫</div>
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 8px;">What You Can't Do</h3>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Use for illegal purposes</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Reverse engineer the app</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Display false point balances</li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Violate Shopify platform policies</li>
                <li style="padding: 8px 0;">Abuse the automated pricing engine</li>
            </ul>
        </div>
    </div>

    <!-- Side-by-Side Responsibilities -->
    <div class="p-grid p-grid--2" style="margin-bottom: 48px;">
        <div class="p-card">
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">📜 Your Responsibilities</h3>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Provide accurate pricing data</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Honor reward redemptions</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Comply with local trade laws</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Maintain your Shopify account security</li>
                <li style="padding: 10px 0;">Use the app features responsibly</li>
            </ul>
        </div>

        <div class="p-card">
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">🛡️ Our Responsibilities</h3>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Maintain core app functionality</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Ensure reasonable uptime performance</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Protect and encrypt your store data</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--p-color-border);">Provide technical merchant support</li>
                <li style="padding: 10px 0;">Comply with Shopify partner rules</li>
            </ul>
        </div>
    </div>

    <!-- Limitation Box -->
    <div class="p-card" style="border: 2px solid var(--p-color-text); margin-bottom: 48px; padding: 32px;">
        <div class="p-grid p-grid--4" style="align-items: start;">
            <div>
                <h3 style="font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Limitation of Liability</h3>
            </div>
            <div style="font-size: 13px; color: var(--p-color-text-secondary);">
                To the maximum extent permitted by law, we are not liable for indirect or consequential damages.
            </div>
            <div style="font-size: 13px; color: var(--p-color-text-secondary);">
                Total liability is limited to fees paid in the last 12 months.
            </div>
            <div style="font-size: 13px; color: var(--p-color-text-secondary);">
                App provided "as is" without warranties of any kind.
            </div>
        </div>
    </div>

    <!-- Bottom Secondary Tasks -->
    <div class="p-grid p-grid--2" style="margin-bottom: 48px;">
        <div class="p-card">
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">🔒 Privacy & Data</h3>
            <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                We collect minimal data to provide the service. See our <a href="{{ route('privacy-policy') }}" style="color: var(--p-color-interactive);">Privacy Policy</a> for complete details on data handling.
            </p>
        </div>
        <div class="p-card">
            <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">📊 Termination</h3>
            <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                You may terminate by uninstalling the app. All data will be deleted within 48 hours. No refunds are provided for partial months.
            </p>
        </div>
    </div>

    <!-- Callout Call -->
    <div class="p-callout p-callout--info" style="border: 1px solid #95c9b4; background: #e3f1ed; text-align: center;">
        <div class="p-callout__body">
            <p style="font-size: 14px; color: #004d3d;">💬 <strong>Questions about terms?</strong> Contact us for clarification or legal inquiries. <a href="mailto:{{ $brand['support_email'] ?? 'apps@task19.com' }}" style="color: #008060; font-weight: 700;">Contact Support →</a></p>
        </div>
    </div>

</section>
@endsection
