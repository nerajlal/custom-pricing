@extends('layouts.public')

@section('title', 'Privacy Policy – Custom Pricing & Loyalty')
@section('meta_description', 'Learn how Custom Pricing & Loyalty Rewards handles merchant and customer data.')

@section('content')

<!-- Hero Section -->
<section class="p-hero" style="background: linear-gradient(135deg, #f0f7f5 0%, #e3f1ed 100%); padding: 80px 24px;">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow" style="background: #fff; border-color: #95c9b4;">🛡️ Privacy</div>
        <h1 class="p-hero__title" style="margin-bottom: 16px;">Privacy <span>Policy</span></h1>
        <p class="p-hero__subtitle" style="margin-bottom: 0; font-size: 15px; color: #6d7175;"> Last Updated: December 12, 2025 </p>
    </div>
</section>

<section class="p-page" style="padding: 60px 24px;">

    <!-- Our Commitment -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 20px;">Our Commitment</h2>
        <p style="font-size: 15px; color: var(--p-color-text-secondary); line-height: 1.8; margin-bottom: 20px;">
            Custom Pricing & Loyalty Rewards ("the App", "we", "us") provides discount management, custom pricing, and loyalty reward features for Shopify merchants. We are committed to protecting the privacy of our merchants and their customers. This policy describes how we collect, use, and share personal information when you install or use the App.
        </p>
    </div>

    <!-- Information We Collect -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 24px;">Information We Collect</h2>
        
        <div class="p-grid p-grid--2">
            <div class="p-card" style="display: flex; align-items: flex-start; gap: 16px;">
                <div style="font-size: 24px;">🏢</div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 4px;">From Merchants</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                        When you install the App, we are automatically able to access certain types of information from your Shopify account: shop name, email, and address to manage your account and billing.
                    </p>
                </div>
            </div>

            <div class="p-card" style="display: flex; align-items: flex-start; gap: 16px;">
                <div style="font-size: 24px;">👤</div>
                <div style="flex: 1;">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 4px;">From Customers</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary); margin-bottom: 16px;">To provide our services (like rewards and custom prices), we collect:</p>
                    <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary);">
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Shopify Customer ID</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Customer Email (to award points)</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid var(--p-color-border);">Order Reference & Total (to calculate rewards)</li>
                        <li style="padding: 8px 0;">Reward & Points History</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">How We Use Your Information</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); margin-bottom: 20px;">We use the personal information we collect in order to provide the service and to operate the App. Additionally, we use this information:</p>
        <ul style="list-style: none; padding: 0; font-size: 14px; color: var(--p-color-text-secondary);">
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">To generate and display customer-specific discount prices.</li>
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">To accurately calculate and display loyalty points.</li>
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">To manage the loyalty reward system and customer wallets.</li>
            <li style="padding: 12px 0;">To communicate with you regarding support or app updates.</li>
        </ul>
    </div>

    <!-- Security Callout -->
    <div class="p-callout p-callout--info" style="margin-bottom: 56px; border: 1px solid #95c9b4; background: #e3f1ed;">
        <div class="p-callout__icon" style="color: #008060;">🛡️</div>
        <div class="p-callout__body">
            <h4 style="color: #008060; margin-bottom: 4px;">Data Security & Retention</h4>
            <p style="font-size: 13px; color: #004d3d;">We use industry-standard encryption to protect all data. We retain personal information for as long as you use the App. When you uninstall, we delete all associated merchant and customer data from our servers within 48 hours, in compliance with Shopify's data protection policies.</p>
        </div>
    </div>

    <!-- Rights -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">Your Rights (GDPR & CCPA)</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); line-height: 1.6;">
            If you are a European or California resident, you have the right to access personal information we hold about you and to ask that your personal information be corrected, updated, or deleted. We process these requests automatically via Shopify's official webhooks. For manual inquiries, please contact us at the email below.
        </p>
    </div>

    <!-- Footer Callout -->
    <div class="p-card" style="text-align: center; border: 1px solid var(--p-color-border); background: #fafafa;">
        <p style="font-size: 14px; color: var(--p-color-text-secondary);">
            📧 <strong>Privacy Questions?</strong><br>
            For more information about our privacy practices, please contact us by email at <a href="mailto:apps@task19.com" style="color: var(--p-color-interactive);">apps@task19.com</a>.
        </p>
    </div>

</section>
@endsection
