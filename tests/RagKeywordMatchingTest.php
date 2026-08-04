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

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		$posts = array(
			10 => (object) array(
				'ID'        => 10,
				'post_type' => 'post',
				'post_title' => 'First [Post]',
			),
			20 => (object) array(
				'ID'        => 20,
				'post_type' => 'page',
				'post_title' => 'Second Post',
			),
		);

		return $posts[ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post ) {
		return $post->post_title;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post ) {
		return 'https://example.com/post/' . $post->ID;
	}
}

require_once dirname( __DIR__ ) . '/class-chatbudgie.php';

final class RagKeywordMatchingTest extends TestCase {
	private ChatBudgie $chatbudgie;
	private ReflectionMethod $calculate_keyword_match;
	private ReflectionMethod $group_ranked_chunks_by_post;

	protected function setUp(): void {
		$reflection                         = new ReflectionClass( ChatBudgie::class );
		$this->chatbudgie                   = $reflection->newInstanceWithoutConstructor();
		$this->calculate_keyword_match      = $reflection->getMethod( 'calculate_keyword_match' );
		$this->group_ranked_chunks_by_post = $reflection->getMethod( 'group_ranked_chunks_by_post' );
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

	public function test_it_groups_ranked_chunks_by_post_and_restores_chunk_order(): void {
		$ranked = array(
			array(
				'id'             => '10_2',
				'post_id'        => 10,
				'content'        => 'Best chunk from the first post.',
				'score'          => 0.9,
				'semantic_score' => 0.8,
				'keyword_score'  => 1.0,
			),
			array(
				'id'             => '20_0',
				'post_id'        => 20,
				'content'        => 'Chunk from the second post.',
				'score'          => 0.8,
				'semantic_score' => 0.7,
				'keyword_score'  => 0.9,
			),
			array(
				'id'             => '10_0',
				'post_id'        => 10,
				'content'        => 'Another chunk from the first post.',
				'score'          => 0.7,
				'semantic_score' => 0.6,
				'keyword_score'  => 0.8,
			),
		);

		$result = $this->group_ranked_chunks_by_post->invoke( $this->chatbudgie, $ranked );

		self::assertSame( array( 10, 20 ), array_column( array_column( $result, 'doc' ), 'id' ) );
		self::assertSame(
			array(
				'content',
				'score',
				'semantic_score',
				'keyword_score',
			),
			array_keys( $result[0]['chunks'][0] )
		);
		self::assertSame(
			array(
				'Another chunk from the first post.',
				'Best chunk from the first post.',
			),
			array_column( $result[0]['chunks'], 'content' )
		);
		self::assertSame( '[First \[Post\]](https://example.com/post/10)', $result[0]['doc']['citation'] );
	}
}
