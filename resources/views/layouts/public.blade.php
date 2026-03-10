<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Custom Pricing') – Shopify App</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <meta name="description" content="@yield('meta_description', 'High-performance custom pricing and automated loyalty rewards for Shopify merchants.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Shopify Polaris Design System ───────────────────── */
        :root {
            --p-color-bg:                  #f6f6f7;
            --p-color-bg-surface:          #ffffff;
            --p-color-bg-surface-secondary:#f1f2f3;
            --p-color-border:              #e1e3e5;
            --p-color-border-secondary:    #c9cccf;
            --p-color-text:                #202223;
            --p-color-text-secondary:      #6d7175;
            --p-color-text-subdued:        #8c9196;
            --p-color-interactive:         #008060;
            --p-color-interactive-hovered: #006e52;
            --p-color-interactive-pressed: #005e46;
            --p-color-decorative-one-bg:   #e3f1ed;
            --p-border-radius-base:        8px;
            --p-border-radius-large:       12px;
            --p-shadow-card:               0 1px 3px 0 rgba(63,63,68,0.15), 0 0 0 1px rgba(63,63,68,0.05);
            --p-shadow-popover:            0 4px 16px rgba(0,0,0,0.12);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--p-color-bg);
            color: var(--p-color-text);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--p-color-interactive); text-decoration: none; }
        a:hover { color: var(--p-color-interactive-hovered); text-decoration: underline; }

        /* ── Top Nav ───────────────────────────────────────── */
        .p-topbar {
            background: var(--p-color-bg-surface);
            border-bottom: 1px solid var(--p-color-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .p-topbar__inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .p-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--p-color-text);
            text-decoration: none;
        }
        .p-logo img {
            max-height: 38px;
            width: auto;
            display: block;
        }
        .p-logo span {
            font-weight: 800;
            font-size: 16px;
            letter-spacing: -0.6px;
            color: var(--p-color-text);
        }
        .p-logo:hover { text-decoration: none; }
        .p-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }
        .p-nav a {
            display: block;
            padding: 6px 12px;
            color: var(--p-color-text-secondary);
            font-weight: 500;
            font-size: 14px;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s;
        }
        .p-nav a:hover { background: var(--p-color-bg-surface-secondary); color: var(--p-color-text); text-decoration: none; }
        .p-nav a.active { color: var(--p-color-interactive); background: var(--p-color-decorative-one-bg); }
        .p-nav__actions { display: flex; align-items: center; gap: 8px; }
        .p-hamburger { display: none; background: none; border: none; cursor: pointer; padding: 6px; }
        .p-hamburger span { display: block; width: 20px; height: 2px; background: var(--p-color-text-secondary); margin: 4px 0; transition: 0.3s; }

        /* ── Buttons ───────────────────────────────────────── */
        .p-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: var(--p-border-radius-base);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .p-btn--primary {
            background: var(--p-color-interactive);
            color: #fff;
            border-color: var(--p-color-interactive);
        }
        .p-btn--primary:hover { background: var(--p-color-interactive-hovered); border-color: var(--p-color-interactive-hovered); color: #fff; text-decoration: none; }
        .p-btn--plain {
            background: transparent;
            color: var(--p-color-text-secondary);
            border-color: var(--p-color-border-secondary);
        }
        .p-btn--plain:hover { background: var(--p-color-bg-surface-secondary); color: var(--p-color-text); text-decoration: none; }
        .p-btn--lg { padding: 12px 24px; font-size: 15px; }

        /* ── Layout ────────────────────────────────────────── */
        .p-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        .p-page--narrow { max-width: 800px; }

        /* ── Section Backgrounds ──────────────────────────── */
        .p-section-alt {
            background: #fdfdfd; 
            border-top: 1px solid var(--p-color-border);
            border-bottom: 1px solid var(--p-color-border);
        }

        /* ── Cards ─────────────────────────────────────────── */
        .p-card {
            background: var(--p-color-bg-surface);
            border-radius: var(--p-border-radius-large);
            box-shadow: var(--p-shadow-card);
            padding: 24px;
            border: 1px solid var(--p-color-border);
        }
        .p-card + .p-card { margin-top: 16px; }

        /* ── Section Header ─────────────────────────────────── */
        .p-section {
            margin-bottom: 40px;
        }
        .p-section__title {
            font-size: 24px;
            font-weight: 700;
            color: var(--p-color-text);
            margin-bottom: 8px;
        }
        .p-section__subtitle {
            font-size: 15px;
            color: var(--p-color-text-secondary);
            max-width: 600px;
        }

        /* ── Badge ─────────────────────────────────────────── */
        .p-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }
        .p-badge--success { background: #e3f1ed; color: #008060; }
        .p-badge--info    { background: #e8f4fd; color: #2c6ecb; }
        .p-badge--warning { background: #fff3cd; color: #916a00; }

        /* ── Callout Banner ─────────────────────────────────── */
        .p-callout {
            display: flex;
            gap: 12px;
            padding: 16px;
            border-radius: var(--p-border-radius-base);
            border: 1px solid;
            margin-bottom: 24px;
        }
        .p-callout--info    { border-color: #abd0ef; background: #e8f4fd; }
        .p-callout--success { border-color: #95c9b4; background: #e3f1ed; }
        .p-callout__icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .p-callout__body h4 { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
        .p-callout__body p  { font-size: 13px; color: var(--p-color-text-secondary); }

        /* ── Hero ──────────────────────────────────────────── */
        .p-hero {
            background: linear-gradient(135deg, #f6f6f7 0%, #e3f1ed 100%);
            border-bottom: 1px solid var(--p-color-border);
            padding: 72px 24px;
            text-align: center;
        }
        .p-hero__inner { max-width: 720px; margin: 0 auto; }
        .p-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--p-color-decorative-one-bg);
            color: var(--p-color-interactive);
            border: 1px solid #95c9b4;
            border-radius: 100px;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .p-hero__title {
            font-size: clamp(32px, 5vw, 52px);
            font-weight: 800;
            line-height: 1.1;
            color: var(--p-color-text);
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }
        .p-hero__title span { color: var(--p-color-interactive); }
        .p-hero__subtitle {
            font-size: 17px;
            color: var(--p-color-text-secondary);
            margin-bottom: 36px;
            line-height: 1.6;
        }
        .p-hero__actions { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }

        /* ── Grid ──────────────────────────────────────────── */
        .p-grid { display: grid; gap: 20px; }
        .p-grid--2 { grid-template-columns: repeat(2, 1fr); }
        .p-grid--3 { grid-template-columns: repeat(3, 1fr); }
        .p-grid--4 { grid-template-columns: repeat(4, 1fr); }
        @media(max-width: 900px) {
            .p-grid--4, .p-grid--3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media(max-width: 600px) {
            .p-grid--4 { grid-template-columns: repeat(2, 1fr); }
            .p-grid--3, .p-grid--2 { grid-template-columns: 1fr; }
            .p-nav { display: none; }
            .p-nav.open { display: flex; flex-direction: column; position: absolute; top: 56px; left: 0; right: 0; background: #fff; border-bottom: 1px solid var(--p-color-border); padding: 8px; z-index: 200; }
            .p-hamburger { display: block; }
            .p-hero { padding: 48px 20px; }
        }

        /* ── List ──────────────────────────────────────────── */
        .p-list { list-style: none; }
        .p-list li {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--p-color-border);
            font-size: 14px;
            color: var(--p-color-text-secondary);
        }
        .p-list li:last-child { border-bottom: none; padding-bottom: 0; }
        .p-list li .icon { color: var(--p-color-interactive); font-style: normal; flex-shrink: 0; }

        .p-list-checks { list-style: none; }
        .p-list-checks li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--p-color-text-secondary);
            margin-bottom: 8px;
        }
        .p-list-checks li::before {
            content: '✓';
            color: var(--p-color-interactive);
            font-weight: 800;
        }

        /* ── Step ──────────────────────────────────────────── */
        .p-step {
            display: flex;
            gap: 16px;
            padding: 24px 0;
            border-bottom: 1px solid var(--p-color-border);
        }
        .p-step:last-child { border-bottom: none; padding-bottom: 0; }
        .p-step__num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--p-color-interactive);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .p-step__title { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .p-step__desc  { font-size: 13px; color: var(--p-color-text-secondary); line-height: 1.6; }
        .p-step__code  {
            margin-top: 10px;
            background: var(--p-color-bg);
            border: 1px solid var(--p-color-border);
            border-radius: 6px;
            padding: 10px 14px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: var(--p-color-text);
        }

        /* ── Stat Card ─────────────────────────────────────── */
        .p-stat {
            text-align: center;
            padding: 28px 16px;
        }
        .p-stat__value {
            font-size: 40px;
            font-weight: 800;
            color: var(--p-color-interactive);
            line-height: 1;
            margin-bottom: 6px;
        }
        .p-stat__label { font-size: 13px; color: var(--p-color-text-secondary); font-weight: 500; }

        /* ── Divider ────────────────────────────────────────── */
        .p-divider { border: none; border-top: 1px solid var(--p-color-border); margin: 40px 0; }

        /* ── Footer ─────────────────────────────────────────── */
        .p-footer {
            border-top: 1px solid var(--p-color-border);
            background: var(--p-color-bg-surface);
            padding: 40px 24px;
        }
        .p-footer__inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 32px;
        }
        .p-footer__brand p { font-size: 13px; color: var(--p-color-text-subdued); margin-top: 10px; line-height: 1.6; }
        .p-footer__col h4 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--p-color-text-subdued); margin-bottom: 12px; }
        .p-footer__col ul { list-style: none; }
        .p-footer__col ul li { margin-bottom: 8px; }
        .p-footer__col ul a { font-size: 13px; color: var(--p-color-text-secondary); }
        .p-footer__col ul a:hover { color: var(--p-color-interactive); text-decoration: none; }
        .p-footer__bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid var(--p-color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .p-footer__bottom p { font-size: 12px; color: var(--p-color-text-subdued); }
        @media(max-width: 768px) {
            .p-footer__inner { grid-template-columns: 1fr 1fr; }
            .p-footer__brand { grid-column: span 2; }
        }
        @media(max-width: 480px) {
            .p-footer__inner { grid-template-columns: 1fr 1fr; gap: 32px 20px; }
            .p-footer__brand { grid-column: span 2; }
            .p-footer__inner > .p-footer__col:nth-of-type(1) { grid-column: span 2; } /* Product spans 2 */
            /* Support and Legal will naturally share the next row */
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Top Navigation -->
    <header class="p-topbar">
        <div class="p-topbar__inner">
            <a href="{{ route('home') }}" class="p-logo">
                <img src="{{ asset('logo.png') }}" alt="Custom Pricing">
                <span>Custom Pricing</span>
            </a>
            <button class="p-hamburger" onclick="document.querySelector('.p-nav').classList.toggle('open')" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <nav>
                <ul class="p-nav" id="main-nav">
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                    <li><a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'active' : '' }}">Pricing</a></li>
                    <li><a href="{{ route('installation') }}" class="{{ request()->routeIs('installation') ? 'active' : '' }}">Install Guide</a></li>
                    <li><a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? 'active' : '' }}">Support</a></li>
                    <!-- <li><a href="{{ route('privacy-policy') }}" class="{{ request()->routeIs('privacy-policy') ? 'active' : '' }}">Privacy</a></li>
                    <li><a href="{{ route('terms-of-service') }}" class="{{ request()->routeIs('terms-of-service') ? 'active' : '' }}">Terms</a></li> -->
                </ul>
            </nav>
            <div class="p-nav__actions">
                <a href="{{ route('install') }}" class="p-btn p-btn--primary">
                    Add to Shopify
                </a>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="p-footer">
        <div class="p-footer__inner">
            <div class="p-footer__brand">
                <a href="{{ route('home') }}" class="p-logo">
                    <img src="{{ asset('logo.png') }}" alt="Custom Pricing" style="max-height: 44px;">
                    <span style="font-size: 18px;">Custom Pricing</span>
                </a>
                <p>High-performance custom pricing and automated loyalty rewards built for Shopify merchants. Grow your repeat sales with ease.</p>
            </div>
            <div class="p-footer__col">
                <h4>Product</h4>
                <ul>
                    <li><a href="{{ route('home') }}#features">Features</a></li>
                    <li><a href="{{ route('pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('installation') }}">Install Guide</a></li>
                </ul>
            </div>
            <div class="p-footer__col">
                <h4>Support</h4>
                <ul>
                    <li><a href="mailto:customepriceapp@task19.com">Contact Us</a></li>
                    <li><a href="{{ route('support') }}">Support</a></li>
                </ul>
            </div>
            <div class="p-footer__col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('terms-of-service') }}">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="p-footer__bottom">
            <p>&copy; {{ date('Y') }} Custom Pricing. All rights reserved.</p>
            <p>Developed by <a href="https://task19.com/" target="_blank" style="color: inherit; text-decoration: underline;">task19 technologies</a></p>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
