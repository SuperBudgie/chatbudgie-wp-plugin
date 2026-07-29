<?php

use PHPUnit\Framework\TestCase;
use SuperBudgie\ChatBudgie\ChatBudgie;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'CHATBUDGIE_BASE_URL' ) ) {
	define( 'CHATBUDGIE_BASE_URL', 'https://example.com/' );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		return trim( strip_tags( $text ) );
	}
}

require_once dirname( __DIR__ ) . '/class-chatbudgie.php';

final class RagKeywordMatchingTest extends TestCase {
	private ChatBudgie $chatbudgie;
	private ReflectionMethod $calculate_keyword_match;

	protected function setUp(): void {
		$reflection                    = new ReflectionClass( ChatBudgie::class );
		$this->chatbudgie              = $reflection->newInstanceWithoutConstructor();
		$this->calculate_keyword_match = $reflection->getMethod( 'calculate_keyword_match' );
	}

	public function test_it_normalizes_keyword_frequency(): void {
		$text = 'WordPress uses local vector search. Vector search is useful.';
		$result = $this->calculate_keyword_match->invoke(
			$this->chatbudgie,
			$text,
			array(
				'wordpress',
				'vector',
				'missing',
			)
		);

		$expected_score = 3 / log( strlen( $text ) + 1 ) + ( 0.1 * 2 / 3 );
		self::assertEqualsWithDelta( $expected_score, $result, 0.000001 );
	}

	public function test_it_returns_zero_without_keywords(): void {
		$result = $this->calculate_keyword_match->invoke( $this->chatbudgie, 'Some text', array() );

		self::assertSame( 0.0, $result );
	}

	public function test_it_returns_zero_for_empty_text(): void {
		$result = $this->calculate_keyword_match->invoke( $this->chatbudgie, '', array( 'keyword' ) );

		self::assertSame( 0.0, $result );
	}
}
