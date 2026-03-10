@extends('layouts.public')

@section('title', 'Installation Guide - Custom Pricing & Loyalty')

@section('content')

<!-- Hero Section -->
<section class="p-hero" style="background: linear-gradient(135deg, #f0f7f5 0%, #e3f1ed 100%); padding: 80px 24px;">
    <div class="p-hero__inner">
        <div class="p-hero__eyebrow" style="background: #fff; border-color: #95c9b4;">� SETUP GUIDE</div>
        <h1 class="p-hero__title" style="margin-bottom: 16px;">Launch Your Custom Pricing in <span>5 Minutes</span></h1>
        <p class="p-hero__subtitle" style="margin-bottom: 0; font-size: 15px; color: #6d7175;">Activate your tiered rewards, configure wholesale rules, and go live with your loyalty program.</p>
    </div>
</section>

<section class="p-page" style="padding-top: 60px;">

    <!-- Top Grid: Step 1 & 2 -->
    <div class="p-grid p-grid--2" style="margin-bottom: 24px;">
        
        <!-- Step 1 -->
        <div class="p-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #008060; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                <span style="background: #008060; color: #fff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">1</span>
                Step 1: Install the App
            </h2>
            <hr style="border: none; border-top: 1px solid var(--p-color-border); margin-bottom: 24px;">
            
            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">1</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Visit the Shopify App Store</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Search for <strong>Custom Pricing & Loyalty</strong> in the Shopify App Store.</p>
                </div>
            </div>

            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">2</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Click "Install"</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Click the "Install" button to start the authorization process.</p>
                </div>
            </div>

            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">3</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Authorize the App</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Review the permissions and click "Install App" to grant access to your store.</p>
                </div>
            </div>

            <div class="p-callout p-callout--info" style="margin-top: 24px; border-color: #abd0ef; background: #f0f7fe; padding: 12px 16px;">
                <div style="font-size: 16px; margin-bottom: 8px;">📋 <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #2c6ecb;">Permissions Required</span></div>
                <ul style="list-style: none; padding: 0; font-size: 12px; color: #2c6ecb; line-height: 1.6;">
                    <li>• Manage customers & segments</li>
                    <li>• Read/Write discounts and price rules</li>
                    <li>• Access store settings for theme setup</li>
                </ul>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="p-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #008060; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                <span style="background: #008060; color: #fff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">2</span>
                Step 2: Set Up Your Rules
            </h2>
            <hr style="border: none; border-top: 1px solid var(--p-color-border); margin-bottom: 24px;">

            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">1</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Select Your Plan</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Choose a plan that fits your volume. All plans include a 14-day free trial.</p>
                </div>
            </div>

            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">2</div>
                <div style="flex: 1;">
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Initialize Default Settings</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary); margin-bottom: 12px;">Click "Activate Engine" to let the app start tracking orders and issuing rewards.</p>
                    <div style="background: #fffbef; border: 1px solid #ffd54f; border-radius: 6px; padding: 12px; font-size: 12px;">
                        <strong>💡 Note:</strong> Custom prices only apply to customers with specific tags after they login.
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 16px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">3</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Verify Data Sync</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Our system registers webhooks instantly. You'll see "Connected" status on your dashboard.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Full Width -->
    <div class="p-card" style="margin-bottom: 24px;">
        <h2 style="font-size: 16px; font-weight: 800; color: #008060; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <span style="background: #008060; color: #fff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">3</span>
            Step 3: Customize Your Tiers & Rules
        </h2>
        <hr style="border: none; border-top: 1px solid var(--p-color-border); margin-bottom: 24px;">

        <div class="p-grid p-grid--3" style="margin-bottom: 24px;">
            <div style="display: flex; gap: 16px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">1</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Navigate to Pricing</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Head over to the <strong>Pricing Rules</strong> tab in your app dashboard.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">2</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Set Tag-Based Rules</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Define the discount percentage or fixed price for each customer tag (VIP, Wholesale, etc).</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">3</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Add Loyalty Points</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Configure how many points customers earn per dollar spent in the Loyalty tab.</p>
                </div>
            </div>
        </div>

        <div style="background: #f4fcf4; border: 1px solid #95c9b4; border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 20px;">🪄</span>
            <p style="font-size: 13px; margin-bottom: 0;"><strong>Pro Tip: Reward Your Best Customers.</strong> Try offering a higher point ratio or exclusive prices for your most loyal customer tags to drive engagement.</p>
        </div>
    </div>

    <!-- Bottom Grid: Step 4 & 5 -->
    <div class="p-grid p-grid--2" style="margin-bottom: 48px;">
        <!-- Step 4 -->
        <div class="p-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #008060; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                <span style="background: #008060; color: #fff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">4</span>
                Step 4: Add to Your Storefront
            </h2>
            <hr style="border: none; border-top: 1px solid var(--p-color-border); margin-bottom: 24px;">

            <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">1</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Open Theme Editor</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Go to <strong>Online Store > Themes > Customize</strong> in your Shopify admin.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">2</div>
                <div style="flex: 1;">
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Enable App Embed</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary); margin-bottom: 12px;">In the left Sidebar, click <strong>App Embeds</strong> and toggle <strong>Custom Pricing & Loyalty</strong> to ON.</p>
                    <div style="background: #fff5f5; border: 1px solid #ffcccc; color: #cc0000; border-radius: 6px; padding: 12px; font-size: 12px; font-weight: 600;">
                        ⚠️ Important: Don't forget to click the Save button in the top right corner!
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 16px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">3</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Done & Live!</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">The widgets are now live on your customer account and product pages.</p>
                </div>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="p-card">
            <h2 style="font-size: 16px; font-weight: 800; color: #008060; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                <span style="background: #008060; color: #fff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">5</span>
                Step 5: Test Your Setup
            </h2>
            <hr style="border: none; border-top: 1px solid var(--p-color-border); margin-bottom: 24px;">

            <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">1</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Log in as a Customer</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary);">Create or log in to a customer account with an active discount tag.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                <div style="background: #008060; color: #fff; min-width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">2</div>
                <div style="flex: 1;">
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Check Your Prices & Wallet</h3>
                    <p style="font-size: 13px; color: var(--p-color-text-secondary); margin-bottom: 12px;">Navigate to a product page to see the custom prices and check your point balance.</p>
                    <div style="background: #f4fcf4; border: 1px solid #95c9b4; border-radius: 8px; padding: 12px;">
                        <span style="font-size: 12px; font-weight: 700; color: #008060; display: block; margin-bottom: 8px;">✅ What You Should See:</span>
                        <ul style="list-style: none; padding: 0; font-size: 11px; color: #005e46; line-height: 1.6;">
                            <li>• Price replaced by your custom tag-based price</li>
                            <li>• Floating loyalty badge or wallet widget</li>
                            <li>• Your current point balance correctly displayed</li>
                            <li>• Redemption history list (if any)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div style="margin-bottom: 48px;">
        <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 24px;">🔧</span> Troubleshooting
        </h2>
        <div class="p-card">
            <div class="p-grid p-grid--2">
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">Widget not updating?</h3>
                    <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary); line-height: 2;">
                        <li>• Ensure "App Embed" is toggled ON in Shopify Theme Editor.</li>
                        <li>• Clear your browser cache and refresh the storefront.</li>
                        <li>• Verify the app is installed and authorized.</li>
                    </ul>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">Prices not changing?</h3>
                    <ul style="list-style: none; padding: 0; font-size: 13px; color: var(--p-color-text-secondary); line-height: 2;">
                        <li>• Check if the customer has the correct tag assigned.</li>
                        <li>• Verify the pricing rule is active in the App Dashboard.</li>
                        <li>• Wait up to 1 minute for Shopify webhooks to process.</li>
                    </ul>
                </div>
            </div>
            <hr style="border: none; border-top: 1px solid var(--p-color-border); margin: 24px 0;">
            <p style="font-size: 13px; color: var(--p-color-text-secondary); text-align: center;">
                Need more help? Contact our support team at <a href="mailto:apps@task19.com" style="color: var(--p-color-interactive); font-weight: 600;">apps@task19.com</a> and we'll be happy to help!
            </p>
        </div>
    </div>

    <!-- Ready banner -->
    <div class="p-card" style="background: #fafafa; padding: 32px; display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 60px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="background: #fff; width: 48px; height: 48px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 24px;">🚀</div>
            <div>
                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 4px;">Ready to get started?</h3>
                <p style="font-size: 13px; color: var(--p-color-text-secondary); margin-bottom: 0;">Launch your loyalty program today and boost your customer retention.</p>
            </div>
        </div>
        <a href="{{ route('install') }}" class="p-btn p-btn--primary p-btn--lg" style="background: #008060; border-radius: 6px;">Add to Shopify →</a>
    </div>

</section>
@endsection
