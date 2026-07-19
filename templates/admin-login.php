<?php
/**
 * Template for the ChatBudgie admin login page
 *
 * @package ChatBudgie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the OAuth2 login URL for a specific provider.
 *
 * @param string $provider The OAuth2 provider (e.g., google, microsoft, github).
 * @return string The formatted authorization URL.
 */
function chatbudgie_get_login_url( string $provider ) {
	$state        = wp_create_nonce( 'chatbudgie_login_callback' );
	$callback_url = add_query_arg(
		array(
			'action' => 'chatbudgie_login_callback',
			'state'  => $state,
		),
		admin_url( 'admin-post.php' )
	);

	return CHATBUDGIE_BASE_URL . 'oauth2/authorization/' . $provider . '?appname=' . rawurlencode( CHATBUDGIE_APP_NAME ) . '&callback=' . rawurlencode( $callback_url );
}

?>
<div class="page">
	<!-- Header -->
	<header class="header">
		<div class="brand">
			<img class="brand__mark" src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/logo.png' ); ?>" alt="" />
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
						<?php echo esc_html__( 'Get Started', 'chatbudgie' ); ?>
						<span class="wave" role="img" aria-label="<?php echo esc_attr__( 'waving hand', 'chatbudgie' ); ?>">👋</span>
					</h1>
					<p class="login__sub"><?php echo esc_html__( 'Login to your ChatBudgie account.', 'chatbudgie' ); ?> <br /><?php echo esc_html__( 'New users will receive a certain amount of free tokens.', 'chatbudgie' ); ?></p>
				</div>

				<div class="providers">
					<a href="<?php echo esc_url( chatbudgie_get_login_url( 'google' ) ); ?>" class="provider">
						<span class="provider__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/google.svg' ); ?>" alt="" />
						</span>
						<span class="provider__label"><?php echo esc_html__( 'Sign in with Google', 'chatbudgie' ); ?></span>
					</a>

					<a href="<?php echo esc_url( chatbudgie_get_login_url( 'microsoft' ) ); ?>" class="provider">
						<span class="provider__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/microsoft.svg' ); ?>" alt="" />
						</span>
						<span class="provider__label"><?php echo esc_html__( 'Sign in with Microsoft', 'chatbudgie' ); ?></span>
					</a>

					<a href="<?php echo esc_url( chatbudgie_get_login_url( 'github' ) ); ?>" class="provider">
						<span class="provider__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/github.svg' ); ?>" alt="" />
						</span>
						<span class="provider__label"><?php echo esc_html__( 'Sign in with GitHub', 'chatbudgie' ); ?></span>
					</a>

					<div class="divider" aria-hidden="true">
						<span class="divider__line"></span>
					</div>

					<p class="legal">
						<img class="legal__icon" src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/lock.svg' ); ?>" alt="" />
						<span>
							<?php
							$chatbudgie_terms_link   = sprintf(
								'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
								esc_url( 'https://chat.superbudgie.com/terms-of-service' ),
								esc_html__( 'Terms of Service', 'chatbudgie' )
							);
							$chatbudgie_privacy_link = sprintf(
								'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
								esc_url( 'https://chat.superbudgie.com/privacy-policy' ),
								esc_html__( 'Privacy Policy', 'chatbudgie' )
							);

							/* translators: 1: Terms of Service link, 2: Privacy Policy link. */
							$chatbudgie_legal_text = sprintf( __( 'By continuing, you agree to our %1$s and %2$s.', 'chatbudgie' ), $chatbudgie_terms_link, $chatbudgie_privacy_link );

							echo wp_kses(
								$chatbudgie_legal_text,
								array(
									'a' => array(
										'href'   => true,
										'target' => true,
										'rel'    => true,
									),
								)
							);
							?>
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
						<?php echo esc_html__( 'Smart AI Chatbot for WordPress', 'chatbudgie' ); ?>
					</h2>
					<p class="promo__sub">
						<?php echo esc_html__( 'Turn your WordPress to a smart chatbot.', 'chatbudgie' ); ?>
					</p>
				</div>

				<ul class="features">
					<li class="feature">
						<span class="feature__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/f-chat.svg' ); ?>" alt="" />
						</span>
						<div class="feature__body">
							<h3 class="feature__title"><?php echo esc_html__( 'Smart Chat', 'chatbudgie' ); ?></h3>
							<p class="feature__text"><?php echo esc_html__( 'Answers from your content, not the internet.', 'chatbudgie' ); ?></p>
						</div>
					</li>

					<li class="feature">
						<span class="feature__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/f-bolt.svg' ); ?>" alt="" />
						</span>
						<div class="feature__body">
							<h3 class="feature__title"><?php echo esc_html__( 'Easy to Setup', 'chatbudgie' ); ?></h3>
							<p class="feature__text"><?php echo esc_html__( 'Get started in just a few minutes.', 'chatbudgie' ); ?></p>
						</div>
					</li>

					<li class="feature">
						<span class="feature__icon">
							<img src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/f-gear.svg' ); ?>" alt="" />
						</span>
						<div class="feature__body">
							<h3 class="feature__title"><?php echo esc_html__( 'Zero Maintenance', 'chatbudgie' ); ?></h3>
							<p class="feature__text"><?php echo esc_html__( 'We keep your knowledge base up to date automatically.', 'chatbudgie' ); ?></p>
						</div>
					</li>
				</ul>

				<div class="mascot">
					<img class="mascot__bubble" src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/bubble.svg' ); ?>" alt="" />
					<img class="mascot__img" src="<?php echo esc_url( CHATBUDGIE_PLUGIN_URL . 'assets/images/budgie.png' ); ?>" alt="<?php echo esc_attr__( 'ChatBudgie mascot', 'chatbudgie' ); ?>" />
				</div>
			</div>
		</section>
	</main>

	<!-- Footer -->
	<footer class="footer">
		<p class="footer__copy">
			<?php
			/* translators: %s: Current year. */
			echo esc_html( sprintf( __( '© %s ChatBudgie. All rights reserved.', 'chatbudgie' ), gmdate( 'Y' ) ) );
			?>
		</p>
	</footer>
</div>
