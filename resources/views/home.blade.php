@extends('layouts.public')

@section('title', 'Custom Pricing & Loyalty Rewards')

@section('content')
    <!-- Hero Section -->
    <section class="p-hero">
        <div class="p-hero__inner">
            <div class="p-hero__eyebrow">✦ BUILD FOR YOUR CUSTOMERS</div>
            <h1 class="p-hero__title">Custom Pricing & <span>Loyalty Rewards</span> Made Simple</h1>
            <p class="p-hero__subtitle">
                Reward your loyal customers with personalized pricing and an engaging loyalty program. Increase retention and drive repeat purchases with our all-in-one solution.
            </p>
            <div class="p-hero__actions">
                <a href="{{ route('install') }}" class="p-btn p-btn--primary p-btn--lg">Add to Shopify — It's Free</a>
                <a href="#features" class="p-btn p-btn--plain p-btn--lg">See How It Works</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="p-page" style="padding-top: 0; margin-top: -40px;">
        <div class="p-card">
            <div class="p-grid p-grid--4">
                <div class="p-stat">
                    <div class="p-stat__value">500+</div>
                    <div class="p-stat__label">Active Stores</div>
                </div>
                <div class="p-stat">
                    <div class="p-stat__value">98%</div>
                    <div class="p-stat__label">Satisfaction Rate</div>
                </div>
                <div class="p-stat">
                    <div class="p-stat__value">2M+</div>
                    <div class="p-stat__label">Points Redeemed</div>
                </div>
                <div class="p-stat">
                    <div class="p-stat__value">24/7</div>
                    <div class="p-stat__label">Support Available</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="p-page">
        <div class="p-section text-center" style="margin-bottom: 56px;">
            <span class="p-badge p-badge--success" style="margin-bottom: 12px;">Everything For Your Business</span>
            <h2 class="p-section__title">Powerful Features</h2>
            <p class="p-section__subtitle" style="margin: 0 auto;">Everything you need to create personalized pricing and reward loyal customers</p>
        </div>

        <div class="p-grid p-grid--3">
            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Tier-Based Pricing</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Create VIP, Gold, or Wholesale customer tiers with exclusive pricing and automated assignments.</p>
            </div>

            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a1 1 0 11-2 0zM13.536 14.95a1 1 0 011.414-1.414l.707.707a1 1 0 01-1.414 1.414l-.707-.707zM6.464 14.95a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707z" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Auto Price Replacement</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Instantly replaces retail prices for logged-in eligible customers across your entire storefront.</p>
            </div>

            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Loyalty Points System</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Automatically reward customers with points on every purchase. Fully configurable redemption rates.</p>
            </div>

            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Manual Adjustments</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Easily add or deduct loyalty points directly from the admin panel for complete control.</p>
            </div>

            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Customer Specifics</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Set exclusive prices for individual customers based on their history or specific requirements.</p>
            </div>

            <div class="p-card">
                <div style="margin-bottom: 20px;">
                    <svg width="40" height="40" fill="var(--p-color-interactive)" viewBox="0 0 20 20">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                        <path d="M12 2.252A8.001 8.001 0 0117.748 8H12V2.252z" />
                    </svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Easy Management</h3>
                <p style="color: var(--p-color-text-secondary); font-size: 0.95rem;">Control your entire pricing strategy and loyalty program from one intuitive, unified dashboard.</p>
            </div>
        </div>
    </section>

    <!-- 🎡 3D App Showcase -->
    <section class="p-section-alt" style="padding: 100px 24px; overflow: hidden; border-top: 1px solid var(--p-color-border);">
        <div class="p-hero__inner" style="max-width: 1000px; margin-bottom: 60px; text-align: center;">
            <div class="p-hero__eyebrow" style="background: #e3f1ed; color: #008060; border: 1px solid #95c9b4;">✨ Visual Tour</div>
            <h2 style="font-size: clamp(32px, 5vw, 48px); font-weight: 800; line-height: 1.1; margin-bottom: 20px;">Experience the <span>Power</span> of Precision</h2>
            <p style="font-size: 18px; color: var(--p-color-text-secondary); line-height: 1.6;">Take a look at how Custom Pricing & Loyalty transforms your store's backend into a sales-driving engine.</p>
        </div>

        <div class="p-page" style="padding: 0;">
            <div class="p-showcase-container">
                <!-- 3D Navigation Sidebar -->
                <div class="p-showcase-sidebar">
                    <button class="p-showcase-nav-btn" onclick="scrollThumbnails('up')" aria-label="Scroll Up">
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M15 13l-5-5-5 5h10z"/></svg>
                    </button>
                    
                    <div class="p-showcase-wheel-viewport">
                        <div class="p-showcase-list" id="thumbnail-list">
                            @php
                                $showcaseItems = [
                                    ['img' => 'custom_pricing.webp', 'title' => 'Advanced Pricing Rules', 'desc' => 'Set up complex pricing logic based on customer history and purchase volume.'],
                                    ['img' => 'loyalty.webp', 'title' => 'Loyalty Program Management', 'desc' => 'Configure points earning rules and redemption options in seconds.'],
                                    ['img' => 'loyalty_hisory.webp', 'title' => 'Detailed Customer Insights', 'desc' => 'Track every point earned and spent with high-precision audit logs.'],
                                    ['img' => 'pricing_tier_detail.webp', 'title' => 'Tiered Discount Configuration', 'desc' => 'Target specific customer segments with granular discount tiers.'],
                                    ['img' => 'pricing_tiers.webp', 'title' => 'Tier-Based Segmenting', 'desc' => 'Manage all your customer segments from one intuitive overview.'],
                                    ['img' => 'settings.webp', 'title' => 'Intuitive Global Settings', 'desc' => 'Full control over app behavior, widget styling, and automated emails.'],
                                ];
                                // Duplicate items for infinite loop
                                $extendedItems = array_merge($showcaseItems, $showcaseItems); 
                            @endphp

                            @foreach($extendedItems as $index => $item)
                                <button class="p-showcase-item {{ $index === 0 ? 'active' : '' }}" 
                                        onclick="updateShowcase(this, '{{ asset('screenshots/'.$item['img']) }}', '{{ $item['title'] }}', '{{ $item['desc'] }}')"
                                        data-index="{{ $index }}"
                                        data-real-index="{{ $index % count($showcaseItems) }}">
                                    <img src="{{ asset('screenshots/'.$item['img']) }}" alt="{{ $item['title'] }}">
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button class="p-showcase-nav-btn" onclick="scrollThumbnails('down')" aria-label="Scroll Down">
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M5 7l5 5 5-5H5z"/></svg>
                    </button>
                </div>

                <!-- Pagination Dots -->
                <div class="p-showcase-dots" id="p-showcase-dots">
                    @foreach($showcaseItems as $i => $item)
                        <div class="p-dot {{ $i === 0 ? 'active' : '' }}" onclick="goToIndex({{ $i }})" data-dot-index="{{ $i }}"></div>
                    @endforeach
                </div>

                <!-- Main Preview Area -->
                <div class="p-showcase-preview" id="showcase-preview-area">
                    <div class="p-showcase-img-container" onclick="openLightbox()">
                        <img id="main-showcase-img" src="{{ asset('screenshots/custom_pricing.webp') }}" alt="Preview" class="fade-in">
                    </div>
                    <div class="p-showcase-details fade-in" id="showcase-text-area">
                        <h3 id="showcase-title">Advanced Pricing Rules</h3>
                        <p id="showcase-desc">Set up complex pricing logic based on customer history and purchase volume.</p>
                    </div>
                </div>

                <!-- Fullscreen Lightbox -->
                <div id="p-lightbox" onclick="this.style.display='none'">
                    <img id="lightbox-img" src="" alt="Fullscreen Preview">
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section style="background:var(--p-color-interactive); padding:80px 24px; text-align:center; border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="p-hero__inner" style="max-width: 800px;">
            <h2 style="font-size:clamp(28px, 4vw, 40px); font-weight:800; color:#fff; margin-bottom:16px; letter-spacing: -0.5px;">Ready to Boost Your Sales?</h2>
            <p style="color:rgba(255,255,255,0.9); font-size:18px; margin-bottom:40px; line-height: 1.6;">Join hundreds of Shopify merchants who are already rewarding their customers and increasing their repeat purchase rate.</p>
            <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                <a href="https://apps.shopify.com" target="_blank" class="p-btn p-btn--lg"
                    style="background:#fff; color:var(--p-color-interactive); border-color:#fff; padding: 14px 32px; font-size: 16px;">
                    Add to Shopify — Free
                </a>
                <a href="{{ route('installation') }}" class="p-btn p-btn--lg"
                    style="background:transparent; color:#fff; border-color:rgba(255,255,255,0.6); padding: 14px 32px; font-size: 16px;">
                    View Install Guide
                </a>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .p-showcase-container {
        display: flex;
        gap: 40px;
        align-items: stretch;
        max-width: 1200px;
        margin: 0 auto;
    }

    .p-showcase-sidebar {
        flex: 0 0 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 100px;
        background: #fff;
        border: 1px solid var(--p-color-border);
        border-radius: 40px;
        padding: 20px 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        min-height: 480px;
    }

    .p-showcase-wheel-viewport {
        flex: 1;
        width: 100%;
        overflow: visible;
        perspective: 1500px;
        display: flex;
        align-items: center;
        justify-content: center;
        -webkit-mask-image: linear-gradient(to bottom, transparent, black 15%, black 85%, transparent);
        mask-image: linear-gradient(to bottom, transparent, black 15%, black 85%, transparent);
    }

    .p-showcase-list {
        display: flex;
        flex-direction: column;
        transform-style: preserve-3d;
        transition: transform 1s cubic-bezier(0.19, 1, 0.22, 1);
        position: relative;
        width: 100%;
        height: 100%;
    }

    .p-showcase-item {
        position: absolute;
        width: 120px;
        height: 75px;
        left: 20px;
        top: calc(50% - 37.5px);
        background: #fff;
        border: 1px solid var(--p-color-border);
        border-radius: 10px;
        padding: 4px;
        cursor: pointer;
        transition: all 0.8s cubic-bezier(0.19, 1, 0.22, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        backface-visibility: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        outline: none;
    }

    .p-showcase-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
        transition: all 0.8s ease;
    }

    .p-showcase-item.active {
        border-color: var(--p-color-interactive);
        box-shadow: 0 0 30px rgba(0, 128, 96, 0.2);
        z-index: 10;
        transform: scale(1.2) translateZ(40px);
    }

    .p-showcase-nav-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--p-color-text-subdued);
        padding: 10px;
        transition: color 0.2s;
    }
    .p-showcase-nav-btn:hover { color: var(--p-color-interactive); }

    .p-showcase-dots {
        display: flex;
        flex-direction: column;
        gap: 16px;
        justify-content: center;
        padding: 0 10px;
    }

    .p-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--p-color-border-secondary);
        cursor: pointer;
        transition: all 0.4s ease;
    }
    .p-dot.active {
        background: var(--p-color-interactive);
        height: 24px;
        border-radius: 4px;
    }

    .p-showcase-preview {
        flex: 1;
        background: #fff;
        border-radius: 20px;
        border: 1px solid var(--p-color-border);
        padding: 32px;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.05);
    }

    .p-showcase-img-container {
        width: 100%;
        aspect-ratio: 16/10;
        background: #f9f9fa;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 24px;
        cursor: zoom-in;
        border: 1px solid var(--p-color-border);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .p-showcase-img-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.5s ease;
    }
    .p-showcase-img-container:hover img { transform: scale(1.02); }

    .p-showcase-details h3 {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 12px;
        color: var(--p-color-text);
    }
    .p-showcase-details p {
        font-size: 16px;
        color: var(--p-color-text-secondary);
        line-height: 1.6;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeIn 0.6s ease-out forwards; }

    #p-lightbox {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 40px;
        cursor: zoom-out;
    }
    #p-lightbox img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }

    /* Mobile Styles */
    @media (max-width: 1024px) {
        .p-showcase-container { flex-direction: column-reverse; gap: 20px; }
        .p-showcase-sidebar { 
            position: static; 
            width: 100%; 
            height: 150px; 
            min-height: auto; 
            flex-direction: row; 
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
        }
        .p-showcase-wheel-viewport {
            -webkit-mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
            mask-image: linear-gradient(to right, transparent, black 15%, black 85%, transparent);
        }
        .p-showcase-item { 
            width: 110px; 
            height: 65px; 
            left: calc(50% - 55px);
            top: calc(50% - 32.5px);
        }
        .p-showcase-dots { flex-direction: row; order: 2; padding: 10px 0; }
        .p-dot.active { height: 8px; width: 24px; }
        .p-showcase-preview { border-radius: 0; border-left: none; border-right: none; }
    }
</style>
@endpush

@push('scripts')
<script>
    let currentRotation = 0;
    let activeIndex = 0;
    const items = document.querySelectorAll('.p-showcase-item');
    const totalItems = items.length;
    const rotateStep = 360 / totalItems; 

    const mobileQuery = window.matchMedia('(max-width: 1024px)');
    const isMobile = () => mobileQuery.matches;
    const getRadius = () => isMobile() ? 160 : 250;
    const getAxis = () => isMobile() ? 'rotateY' : 'rotateX';

    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('DOMContentLoaded', () => {
        initShowcase3D();
        window.addEventListener('resize', initShowcase3D);
        
        // Swipe Support
        const list = document.getElementById('thumbnail-list');
        list.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, {passive: true});
        list.addEventListener('touchend', e => { touchEndX = e.changedTouches[0].screenX; handleSwipe(); }, {passive: true});
    });

    function handleSwipe() {
        if (!isMobile()) return;
        const threshold = 50;
        if (touchEndX < touchStartX - threshold) scrollThumbnails('down');
        else if (touchEndX > touchStartX + threshold) scrollThumbnails('up');
    }

    function initShowcase3D() {
        const r = getRadius();
        const axis = getAxis();
        items.forEach((item, i) => {
            const angle = i * rotateStep;
            item.style.transform = `${axis}(${angle}deg) translateZ(${r}px)`;
        });
        document.getElementById('thumbnail-list').style.transform = `${axis}(${currentRotation}deg)`;
        updateItemVisuals(activeIndex);
    }

    function updateItemVisuals(idx) {
        items.forEach((item, i) => {
            let diff = Math.abs(i - idx);
            if (diff > totalItems / 2) diff = totalItems - diff;
            const img = item.querySelector('img');
            
            if (i === idx) {
                item.style.opacity = "1";
                item.style.zIndex = "10";
                if (img) img.style.filter = "grayscale(0%) blur(0)";
            } else if (diff === 1) {
                item.style.opacity = "0.9";
                item.style.zIndex = "5";
                if (img) img.style.filter = "grayscale(20%) blur(0.5px)";
            } else if (diff === 2) {
                item.style.opacity = "0.6";
                item.style.zIndex = "2";
                if (img) img.style.filter = "grayscale(50%) blur(1.5px)";
            } else {
                item.style.opacity = "0.2";
                item.style.zIndex = "1";
                if (img) img.style.filter = "grayscale(80%) blur(3px)";
            }
        });
    }

    function updateShowcase(element, imgSrc, title, desc) {
        const newIndex = parseInt(element.getAttribute('data-index'));
        const realIndex = parseInt(element.getAttribute('data-real-index'));
        
        let delta = newIndex - activeIndex;
        if (delta > totalItems / 2) delta -= totalItems;
        if (delta < -totalItems / 2) delta += totalItems;
        
        currentRotation -= delta * rotateStep;
        activeIndex = newIndex;

        items.forEach(item => item.classList.remove('active'));
        element.classList.add('active');

        // Update Dots
        document.querySelectorAll('.p-dot').forEach(dot => dot.classList.remove('active'));
        const activeDot = document.querySelector(`.p-dot[data-dot-index="${realIndex}"]`);
        if (activeDot) activeDot.classList.add('active');

        document.getElementById('thumbnail-list').style.transform = `${getAxis()}(${currentRotation}deg)`;
        updateItemVisuals(activeIndex);

        // Update Main Content with Animation
        const previewImg = document.getElementById('main-showcase-img');
        const previewTitle = document.getElementById('showcase-title');
        const previewDesc = document.getElementById('showcase-desc');

        previewImg.classList.remove('fade-in');
        void previewImg.offsetWidth; 
        previewImg.src = imgSrc;
        previewImg.classList.add('fade-in');

        previewTitle.innerText = title;
        previewDesc.innerText = desc;
    }

    function scrollThumbnails(direction) {
        let nextIndex = (direction === 'up') ? activeIndex - 1 : activeIndex + 1;
        if (nextIndex < 0) nextIndex = totalItems - 1;
        if (nextIndex >= totalItems) nextIndex = 0;
        const nextBtn = document.querySelector(`.p-showcase-item[data-index="${nextIndex}"]`);
        if (nextBtn) nextBtn.click();
    }

    function goToIndex(idx) {
        const targetItems = document.querySelectorAll(`.p-showcase-item[data-real-index="${idx}"]`);
        let closestItem = targetItems[0];
        let minDelta = 999;
        targetItems.forEach(item => {
            const newIdx = parseInt(item.getAttribute('data-index'));
            let delta = Math.abs(newIdx - activeIndex);
            if (delta > totalItems / 2) delta = totalItems - delta;
            if (delta < minDelta) { minDelta = delta; closestItem = item; }
        });
        if (closestItem) closestItem.click();
    }

    function openLightbox() {
        const lb = document.getElementById('p-lightbox');
        const lbImg = document.getElementById('lightbox-img');
        const mainImg = document.getElementById('main-showcase-img');
        lbImg.src = mainImg.src;
        lb.style.display = 'flex';
    }
</script>
@endpush
