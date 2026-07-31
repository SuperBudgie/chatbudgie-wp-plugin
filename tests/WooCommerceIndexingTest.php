<?php

use PHPUnit\Framework\TestCase;
use SuperBudgie\ChatBudgie\ChatBudgie;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'CHATBUDGIE_BASE_URL' ) ) {
	define( 'CHATBUDGIE_BASE_URL', 'https://example.com/' );
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $post_id ) {
		return $GLOBALS['chatbudgie_test_product'] ?? null;
	}
}

require_once dirname( __DIR__ ) . '/class-chatbudgie.php';

final class WooCommerceIndexingTest extends TestCase {
	protected function tearDown(): void {
		unset( $GLOBALS['chatbudgie_test_product'] );
	}

	public function test_it_appends_taxonomy_and_custom_product_attributes(): void {
		$taxonomy_attribute = new class() {
			public function get_name() {
				return 'pa_color';
			}

		};

		$custom_attribute = new class() {
			public function get_name() {
				return 'material';
			}

		};

		$GLOBALS['chatbudgie_test_product'] = new class( $taxonomy_attribute, $custom_attribute ) {
			private array $attributes;

			public function __construct( ...$attributes ) {
				$this->attributes = $attributes;
			}

			public function get_attributes() {
				return $this->attributes;
			}

			public function get_attribute( $name ) {
				if ( 'pa_color' === $name ) {
					return 'Blue, Green';
				}

				return 'Cotton, <b>Wool</b>';
			}
		};

		$reflection = new ReflectionClass( ChatBudgie::class );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'append_product_attributes_to_content' );
		$content    = $method->invoke( $instance, 42, 'A comfortable shirt.' );

		self::assertSame(
			"description: A comfortable shirt.\nColor: Blue, Green\nMaterial: Cotton, Wool",
			$content
		);
	}
}
