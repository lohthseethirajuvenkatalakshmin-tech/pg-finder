<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
start_app_session();
$user = current_user();
$token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f7f2">
    <meta name="csrf-token" content="<?= $token ?>">
    <title>Roomie — Find a place that feels like yours</title>
    <meta name="description" content="Thoughtfully picked student homes, verified for your peace of mind. Find your next stay with Roomie.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="announcement"><span class="announcement-dot"></span> Your next chapter starts with the right address <span class="announcement-arrow">↗</span></div>
    <header class="site-header">
        <a class="brand" href="#" aria-label="Roomie home"><span class="brand-mark">r<span>.</span></span><span>roomie</span></a>
        <nav class="main-nav" aria-label="Main navigation">
            <a class="nav-link active" href="#properties">Find a stay</a>
            <a class="nav-link" href="#how-it-works">How it works</a>
            <a class="nav-link" href="#why-roomie">Why Roomie</a>
        </nav>
        <div class="header-actions">
            <button class="shortlist-trigger" id="shortlist-trigger" aria-label="Open saved stays"><span class="heart-outline">♡</span><span class="shortlist-label">Shortlist</span><span class="shortlist-count" id="shortlist-count">0</span></button>
            <button class="button button-dark header-signin" id="account-button"><?= $user ? 'Hi, ' . htmlspecialchars(explode(' ', $user['name'])[0], ENT_QUOTES, 'UTF-8') : 'Sign in' ?></button>
            <button class="menu-toggle" id="menu-toggle" aria-label="Open navigation" aria-expanded="false"><span></span><span></span></button>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow"><span class="eyebrow-line"></span> DECENT PG ROOMS. STUDENT-FRIENDLY RENTS.</p>
                <h1>A good room.<br>A <span>better rent.</span></h1>
                <p class="hero-description">Find a straightforward PG with the essentials,<br>a friendly neighbourhood, and a monthly price you can manage.</p>
                <div class="hero-social"><div class="avatar-stack"><span class="avatar avatar-one">A</span><span class="avatar avatar-two">S</span><span class="avatar avatar-three">R</span><span class="avatar avatar-four">+</span></div><div><strong>2,400+ students</strong><span>found their place with us</span></div><span class="hero-divider"></span><div class="rating-mini"><span>★</span><strong>4.9</strong><span>from our community</span></div></div>
            </div>
            <div class="hero-art" aria-label="A simple shared PG bedroom with bunk beds">
                <div class="hero-image-main"><img src="../assets/images/pg-room.svg" alt="A simple shared PG bedroom with a bed, study desk, and window"></div>
                <div class="hero-image-note"><span class="note-icon">✳</span><span><strong>Room, bed, Wi-Fi. Sorted.</strong><small>Simple stays. Clear monthly prices.</small></span></div>
                <div class="floating-stamp"><span>GOOD</span><span>ROOMS</span><span>FAIR RENT <b>↗</b></span></div>
                <div class="hero-sparkle sparkle-one">✳</div><div class="hero-sparkle sparkle-two">✳</div>
            </div>
            <div class="hero-bottom-note"><span class="scroll-mark">↓</span> A BETTER SEARCH STARTS HERE <span class="hero-bottom-line"></span><span>01 / 04</span></div>
        </section>

        <section class="search-section" id="stays">
            <div class="search-card">
                <div class="search-top"><div><span class="search-kicker">FIND A PG THAT FITS YOUR BUDGET</span><h2>Pick your city. Set your monthly rent.</h2></div><span class="verified-note"><span>✓</span> Clear prices, no booking fee</span></div>
                <div class="search-controls">
                    <label class="search-field city-field"><span class="field-icon">⌖</span><span class="field-copy"><span class="field-label">CITY OR NEIGHBORHOOD</span><select id="city-filter" aria-label="Choose a city"><option value="">Anywhere</option><option>Bengaluru</option><option>Mumbai</option><option>Pune</option><option>Delhi</option><option>Hyderabad</option><option>Chennai</option></select></span><span class="select-chevron">⌄</span></label>
                    <label class="search-field budget-field"><span class="field-icon">₹</span><span class="field-copy"><span class="field-label">MONTHLY BUDGET</span><select id="budget-filter" aria-label="Choose your monthly budget"><option value="">Any budget</option><option value="under-5">Under ₹5,000</option><option value="5-8">₹5,000 – ₹8,000</option><option value="8-12">₹8,000 – ₹12,000</option><option value="over-12">₹12,000+</option></select></span><span class="select-chevron">⌄</span></label>
                    <label class="search-field gender-field"><span class="field-icon">⌁</span><span class="field-copy"><span class="field-label">LOOKING FOR</span><select id="gender-filter" aria-label="Choose room gender"><option value="">Everyone</option><option value="Women">Women only</option><option value="Men">Men only</option></select></span><span class="select-chevron">⌄</span></label>
                    <button class="button button-search" id="search-button"><span>Find my stay</span><span class="search-arrow">↗</span></button>
                </div>
                <div class="search-bottom"><span>Popular near you</span><button class="city-chip" data-city="Bengaluru">Bengaluru</button><button class="city-chip" data-city="Pune">Pune</button><button class="city-chip" data-city="Mumbai">Mumbai</button><span class="search-hint">Good shared rooms can start under ₹5,000.</span></div>
            </div>
        </section>

        <section class="listing-section" id="properties">
            <div class="section-heading">
                <div><p class="eyebrow"><span class="eyebrow-line"></span> A PLACE TO LAND</p><h2>Find your <span>somewhere.</span></h2></div>
                <p class="section-subtitle">Real homes, lovely people, and neighborhoods that make a new city feel a little smaller.</p>
            </div>
            <div class="results-toolbar">
                <span class="results-count"><span id="all-count">00</span><span id="results-label"> stays</span></span>
                <div class="listing-tools">
                    <label class="quick-search"><span>⌕</span><input id="quick-search" type="search" placeholder="Try “Indiranagar”" aria-label="Search stays"></label>
                    <span class="sort-label">Sort by</span>
                    <button class="sort-button" id="sort-button" aria-label="Sort by recommended">Recommended <span>⌄</span></button>
                </div>
            </div>
            <div class="filter-row"><div class="filter-tabs" role="group" aria-label="Filter by room type"><button class="filter-tab active" data-gender="">All stays</button><button class="filter-tab" data-gender="Women">Women only</button><button class="filter-tab" data-gender="Men">Men only</button></div><button class="map-button" id="clear-filters"><span>↺</span> Clear filters</button></div>
            <div class="properties-grid" id="properties-grid" aria-live="polite" aria-busy="true"></div>
            <div class="empty-state" id="empty-state" hidden><span>⌂</span><h3>No stays found just yet</h3><p>Try a different city or loosen your filters. Your place is out there.</p><button class="button button-dark" id="empty-reset">Clear all filters</button></div>
            <div class="load-error" id="load-error" hidden><span>We couldn't load stays right now.</span><button id="retry-button">Try again</button></div>
            <div class="listing-footer"><span><span class="footer-spark">✳</span> The right place changes everything.</span><button id="show-more" class="show-more">You're all caught up <span>✓</span></button></div>
        </section>

        <section class="promise-section" id="why-roomie"><div class="promise-visual"><img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1100&q=85" alt="Warm, welcoming shared apartment"></div><div class="promise-copy"><p class="eyebrow"><span class="eyebrow-line"></span> A LITTLE MORE ROOM TO BE YOU</p><h2>Moving to a new city<br>is a big deal. <span>We've got you.</span></h2><p>Finding a place shouldn't feel like another full-time job. We do the legwork, so you can focus on what comes next.</p><div class="promise-points"><div><span class="promise-icon">✳</span><span><strong>Actually checked, not just listed</strong><small>We visit every home and verify every detail.</small></span></div><div><span class="promise-icon">♡</span><span><strong>Roomies you can feel good about</strong><small>A community, not just a room number.</small></span></div><div><span class="promise-icon">↗</span><span><strong>Here when you need us</strong><small>Real people to help you find your feet.</small></span></div></div><a class="text-link" href="#how-it-works">Get to know Roomie <span>↗</span></a></div></section>

        <section class="steps-section" id="how-it-works"><div class="steps-heading"><p class="eyebrow"><span class="eyebrow-line"></span> GOODBYES TO GUESSWORK</p><h2>Three little steps.<br><span>One big move.</span></h2></div><div class="step-cards"><article><span class="step-number">01</span><span class="step-icon">⌕</span><h3>Find your kind of place</h3><p>Pick a city, set your budget and explore stays that fit your life.</p><span class="step-arrow">↗</span></article><article><span class="step-number">02</span><span class="step-icon">♡</span><h3>Save the ones you love</h3><p>Shortlist your favourites and come back to them whenever you like.</p><span class="step-arrow">↗</span></article><article><span class="step-number">03</span><span class="step-icon">✳</span><h3>Make it yours</h3><p>Reach out, ask your questions and get ready for a fresh start.</p><span class="step-arrow">↗</span></article></div></section>

        <section class="closing-cta"><div><span class="cta-spark">✳</span><p>GOOD PLACES. GOOD PEOPLE. GOOD STARTS.</p><h2>Your next chapter<br>is looking <em>good.</em></h2></div><a href="#stays" class="button button-cream">Find your stay <span>↗</span></a><span class="cta-orbit orbit-one"></span><span class="cta-orbit orbit-two"></span></section>
    </main>

    <footer class="site-footer"><a class="brand footer-brand" href="#"><span class="brand-mark">r<span>.</span></span><span>roomie</span></a><span>Better places. Better beginnings.</span><span>Made for wherever you're headed next. <span class="footer-heart">♥</span></span><span>© 2025 Roomie stays</span></footer>

    <div class="drawer-backdrop" id="drawer-backdrop"></div>
    <aside class="shortlist-drawer" id="shortlist-drawer" aria-label="Saved stays" aria-hidden="true"><div id="shortlist-root"></div></aside>

    <div class="modal-backdrop" id="modal-backdrop" hidden>
        <section class="property-modal" id="property-modal" role="dialog" aria-modal="true" aria-label="Property details"><button class="modal-close" data-close-modal aria-label="Close details">×</button><div id="property-modal-content"></div></section>
    </div>
    <div class="modal-backdrop auth-backdrop" id="auth-backdrop" hidden>
        <section class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-title"><button class="modal-close" data-close-auth aria-label="Close sign in">×</button><div class="auth-mark">r<span>.</span></div><p class="eyebrow"><span class="eyebrow-line"></span> GOOD TO HAVE YOU HERE</p><h2 id="auth-title">Find your place.</h2><p class="auth-intro">Sign in to save stays and pick up right where you left off.</p><div class="auth-tabs"><button class="auth-tab active" data-mode="login">Sign in</button><button class="auth-tab" data-mode="register">Create account</button></div><form id="auth-form"><label class="auth-field" id="name-field" hidden>Your name<input name="name" autocomplete="name" maxlength="100" placeholder="e.g. Aanya Sharma"></label><label class="auth-field">Email address<input type="email" name="email" autocomplete="email" required placeholder="you@example.com"></label><label class="auth-field" id="phone-field" hidden>Phone (optional)<input name="phone" type="tel" autocomplete="tel" placeholder="+91 98765 43210"></label><label class="auth-field">Password<input name="password" type="password" autocomplete="current-password" minlength="8" required placeholder="At least 8 characters"></label><p class="form-error" id="auth-error" role="alert" hidden></p><button class="button button-dark auth-submit" type="submit">Sign in <span>↗</span></button></form><p class="auth-footnote">By continuing, you agree to keep things kind. That's the Roomie way.</p></section>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script>window.ROOMIE_USER = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="../assets/js/app.js" defer></script>
</body>
</html>
