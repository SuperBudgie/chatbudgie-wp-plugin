<?php

/**
 * Template for the ChatBudgie admin login page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page">
    <!-- Header -->
    <header class="header">
        <div class="brand">
            <img class="brand__mark" src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/logo.png'; ?>" alt="" />
            <span class="brand__name">Chat<span class="brand__name--accent">Budgie</span></span>
        </div>
    </header>
    
    <!-- Card -->
    <main class="cb-card" role="main">
        <!-- Left: Login -->
        <section class="panel panel--login" aria-labelledby="login-title">
            <div class="panel__inner">
                <div class="login__head">
                    <h1 id="login-title" class="login__title">
                        Get Started
                        <span class="wave" role="img" aria-label="waving hand">👋</span>
                    </h1>
                    <p class="login__sub">Login to your ChatBudgie account. <br />New users will receive a certain amount of free tokens.</p>
                </div>

                <div class="providers">
                    <button class="provider" type="button">
                        <span class="provider__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/google.svg'; ?>" alt="" />
                        </span>
                        <span class="provider__label">Sign in with Google</span>
                    </button>

                    <button class="provider" type="button">
                        <span class="provider__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/microsoft.svg'; ?>" alt="" />
                        </span>
                        <span class="provider__label">Sign in with Microsoft</span>
                    </button>

                    <button class="provider" type="button">
                        <span class="provider__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/github.svg'; ?>" alt="" />
                        </span>
                        <span class="provider__label">Sign in with GitHub</span>
                    </button>

                    <div class="divider" aria-hidden="true">
                        <span class="divider__line"></span>
                    </div>

                    <p class="legal">
                        <img class="legal__icon" src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/lock.svg'; ?>" alt="" />
                        <span>
                            By continuing, you agree to our
                            Terms of Service
                            and Privacy Policy.
                        </span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Right: Marketing -->
        <section class="panel panel--promo" aria-labelledby="promo-title">
            <div class="promo__bg" aria-hidden="true">
                <svg class="promo__blob" viewBox="0 0 400 400" preserveAspectRatio="none">
                    <defs>
                        <radialGradient id="g1" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#dbeafe" stop-opacity="0.8" />
                            <stop offset="100%" stop-color="#dbeafe" stop-opacity="0" />
                        </radialGradient>
                    </defs>
                    <circle cx="320" cy="320" r="220" fill="url(#g1)" />
                </svg>
                <div class="promo__dots"></div>
            </div>

            <div class="panel__inner promo__inner">
                <div class="promo__head">
                    <h2 id="promo-title" class="promo__title">
                        Smart AI Chatbot for WordPress
                    </h2>
                    <p class="promo__sub">
                        Turn your wordpress to a smart chatbot.
                    </p>
                </div>

                <ul class="features">
                    <li class="feature">
                        <span class="feature__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/f-chat.svg'; ?>" alt="" />
                        </span>
                        <div class="feature__body">
                            <h3 class="feature__title">Smart Chat</h3>
                            <p class="feature__text">Answers from your content, not the internet.</p>
                        </div>
                    </li>

                    <li class="feature">
                        <span class="feature__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/f-bolt.svg'; ?>" alt="" />
                        </span>
                        <div class="feature__body">
                            <h3 class="feature__title">Easy to Setup</h3>
                            <p class="feature__text">Get started in just a few minutes.</p>
                        </div>
                    </li>

                    <li class="feature">
                        <span class="feature__icon">
                            <img src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/f-gear.svg'; ?>" alt="" />
                        </span>
                        <div class="feature__body">
                            <h3 class="feature__title">Zero Maintenance</h3>
                            <p class="feature__text">We keep your knowledge base up to date automatically.</p>
                        </div>
                    </li>
                </ul>

                <div class="mascot">
                    <img class="mascot__bubble" src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/bubble.svg'; ?>" alt="" />
                    <img class="mascot__img" src="<?php echo CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie.png'; ?>" alt="ChatBudgie mascot" />
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p class="footer__copy">© <?php echo date('Y'); ?> ChatBudgie. All rights reserved.</p>
    </footer>
</div>