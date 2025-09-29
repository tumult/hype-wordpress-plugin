<?php

/**
 * Tests covering the Tumult Hype Animations block integration.
 */
class HypeAnimations_Blocks_Test extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		do_action( 'init' );
	}

	public function tear_down(): void {
		remove_all_filters( 'hypeanimations_render_block_shortcode_handler' );
		remove_all_filters( 'hypeanimations_render_block_shortcode_atts' );
		remove_all_filters( 'hypeanimations_render_block_output' );

		parent::tear_down();
	}

	public function test_block_registers_on_init() {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertTrue(
			$registry->is_registered( 'tumult-hype-animations/animation' ),
			'Expected the Tumult Hype Animations block to register on init.'
		);
	}

	public function test_render_block_requires_animation_id() {
		$this->assertSame( '', hypeanimations_render_block( array() ) );
	}

	public function test_render_block_maps_attributes_to_shortcode() {
		$captured = null;

		add_filter(
			'hypeanimations_render_block_shortcode_handler',
			function () use ( &$captured ) {
				return function ( $shortcode_atts ) use ( &$captured ) {
					$captured = $shortcode_atts;

					return 'mock-output';
				};
			}
		);

		$attributes = array(
			'animationId'  => 7,
			'width'        => '320px',
			'height'       => '240px',
			'isResponsive' => true,
			'autoHeight'   => true,
			'embedMode'    => 'iframe',
		);

		$output = hypeanimations_render_block( $attributes );

		$this->assertSame( 'mock-output', $output );
		$this->assertSame(
			array(
				'id'         => 7,
				'width'      => '320px',
				'responsive' => '1',
				'auto_height' => '1',
				'embedmode'  => 'iframe',
			),
			$captured
		);
		$this->assertArrayNotHasKey( 'height', $captured );
	}

	public function test_render_block_output_filter_allows_custom_markup() {
		add_filter(
			'hypeanimations_render_block_shortcode_handler',
			function () {
				return function () {
					return 'base-output';
				};
			}
		);

		add_filter(
			'hypeanimations_render_block_output',
			function ( $output ) {
				return strtoupper( $output );
			},
			10,
			3
		);

		$output = hypeanimations_render_block(
			array(
				'animationId' => 5,
			)
		);

		$this->assertSame( 'BASE-OUTPUT', $output );
	}
}
