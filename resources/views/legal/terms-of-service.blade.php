@extends('layouts.public')

@section('title', 'Terms of Service – Custom Pricing & Loyalty')
@section('meta_description', 'Review the terms and conditions for using Custom Pricing & Loyalty Rewards.')

@section('content')

<!-- Hero Section -->
<section class="p-hero" style="background: linear-gradient(135deg, #fdf8f4 0%, #fef3ec 100%); padding: 80px 24px;">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow" style="background: #fff; border-color: #f4be95;">⚖️ Legal</div>
        <h1 class="p-hero__title" style="margin-bottom: 16px;">Terms of <span>Service</span></h1>
        <p class="p-hero__subtitle" style="margin-bottom: 0; font-size: 15px; color: #6d7175;"> Last Updated: December 12, 2025 </p>
    </div>
</section>

<section class="p-page" style="padding: 60px 24px;">

    <!-- Introduction -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 20px;">Agreement to Terms</h2>
        <p style="font-size: 15px; color: var(--p-color-text-secondary); line-height: 1.8; margin-bottom: 20px;">
            By installing and using Custom Pricing & Loyalty Rewards ("the App"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please uninstall the app immediately. These terms apply to all merchants, store owners, and delegated staff using the App.
        </p>
    </div>

    <!-- Usage Rules -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 24px;">Usage & Responsibilities</h2>
        
        <div class="p-grid p-grid--2">
            <div class="p-card">
                <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">✅ Merchant Obligations</h3>
                <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                    You are responsible for the accuracy of the pricing rules and loyalty point configurations you set. We are not liable for any revenue loss due to incorrectly configured discount tiers or reward points.
                </p>
            </div>
            <div class="p-card">
                <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">🚫 Prohibited Actions</h3>
                <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                    You may not use the app for any illegal purposes or to violate any laws in your jurisdiction. Attempts to reverse engineer or circumvent app billing and security features are strictly prohibited.
                </p>
            </div>
        </div>
    </div>

    <!-- Service Scope -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">Service Performance</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); margin-bottom: 20px;">While we strive for 100% uptime and accuracy, we provide the service "as is":</p>
        <ul style="list-style: none; padding: 0; font-size: 14px; color: var(--p-color-text-secondary);">
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">Price replacement speed may vary based on Shopify theme structure and server load.</li>
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">We reserve the right to modify or discontinue features with reasonable notice.</li>
            <li style="padding: 12px 0; border-bottom: 1px solid var(--p-color-border);">Maintenance windows may occur outside of peak shopping hours.</li>
            <li style="padding: 12px 0;">We are not responsible for conflicts caused by third-party apps or custom theme code.</li>
        </ul>
    </div>

    <!-- Billing & Termination -->
    <div style="margin-bottom: 56px;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">Billing & Termination</h2>
        <p style="font-size: 14px; color: var(--p-color-text-secondary); line-height: 1.6; margin-bottom: 24px;">
            The app is billed through Shopify. You are responsible for all fees associated with your plan.
        </p>
        <div class="p-grid p-grid--2" style="margin-bottom: 24px;">
            <div class="p-card">
                <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">💳 Payments</h3>
                <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                    Fees are charged on a recurring 30-day cycle. Some plans may include usage fees for high-volume stores or advanced API features.
                </p>
            </div>
            <div class="p-card">
                <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 12px;">📊 Termination</h3>
                <p style="font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6;">
                    You may terminate by uninstalling the app. All of your store's data will be deleted within 48 hours in compliance with Shopify's data protection rules.
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Callout -->
    <div class="p-callout p-callout--info" style="border: 1px solid #f4be95; background: #fffaf7; text-align: center;">
        <div class="p-callout__body">
            <p style="font-size: 14px; color: #4a2a14;">💬 <strong>Questions about these terms?</strong> Contact our support team for any clarification. <a href="mailto:apps@task19.com" style="color: #c05621; font-weight: 700;">Contact Support →</a></p>
        </div>
    </div>

</section>
@endsection
