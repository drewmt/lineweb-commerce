<?php
/**
 * Automatic placements for useful Lineweb Commerce blocks.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether one automatic placement is enabled.
 *
 * Existing placements retain their historical values. Product delivery also
 * requires an explicitly confirmed global profile, so activation alone never
 * publishes an unverified delivery promise.
 *
 * @param string $placement Placement identifier.
 * @return bool
 */
function lineweb_commerce_auto_placement_enabled( $placement ) {
	$option_names = array(
		'product_delivery' => 'lineweb_commerce_auto_product_delivery',
		'cart_progress'    => 'lineweb_commerce_auto_cart_progress',
		'mini_cart_progress' => 'lineweb_commerce_auto_mini_cart_progress',
	);

	if ( ! isset( $option_names[ $placement ] ) ) {
		return false;
	}

	$enabled = 'yes' === get_option( $option_names[ $placement ], 'yes' );

	if ( 'product_delivery' === $placement && ! lineweb_commerce_delivery_profile()['confirmed'] ) {
		$enabled = false;
	}

	/**
	 * Filter whether a Lineweb Commerce automatic placement is enabled.
	 *
	 * @param bool   $enabled   Whether the placement is enabled.
	 * @param string $placement Placement identifier.
	 */
	return (bool) apply_filters( 'lineweb_commerce_auto_placement_enabled', $enabled, $placement );
}

/**
 * Add the Lineweb Commerce section to WooCommerce product settings.
 *
 * @param array<string, string> $sections Existing product sections.
 * @return array<string, string>
 */
function lineweb_commerce_product_settings_section( $sections ) {
	$sections['lineweb-commerce'] = __( 'Lineweb Commerce', 'lineweb-commerce' );

	return $sections;
}
add_filter( 'woocommerce_get_sections_products', 'lineweb_commerce_product_settings_section' );

/**
 * Define the automatic-placement controls inside WooCommerce settings.
 *
 * @param array<int, array<string, mixed>> $settings        Existing settings.
 * @param string                          $current_section Current product section.
 * @return array<int, array<string, mixed>>
 */
function lineweb_commerce_product_settings( $settings, $current_section ) {
	if ( 'lineweb-commerce' !== $current_section ) {
		return $settings;
	}

	return array(
		array(
			'title' => __( 'Global delivery profile', 'lineweb-commerce' ),
			'desc'  => __( 'Used by automatic Delivery Estimate placements and by manual blocks set to Global profile. Review every value before confirming it.', 'lineweb-commerce' ),
			'type'  => 'title',
			'id'    => 'lineweb_commerce_delivery_profile',
		),
		array(
			'title'             => __( 'Delivery range', 'lineweb-commerce' ),
			'desc'              => __( 'Minimum business days', 'lineweb-commerce' ),
			'id'                => 'lineweb_commerce_delivery_min_days',
			'default'           => 1,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 0, 'max' => 30, 'step' => 1 ),
		),
		array(
			'title'             => __( 'Maximum business days', 'lineweb-commerce' ),
			'id'                => 'lineweb_commerce_delivery_max_days',
			'default'           => 3,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 0, 'max' => 60, 'step' => 1 ),
		),
		array(
			'title'             => __( 'Daily cutoff hour', 'lineweb-commerce' ),
			'desc'              => __( 'Store-local hour from 0 to 23.', 'lineweb-commerce' ),
			'id'                => 'lineweb_commerce_delivery_cutoff_hour',
			'default'           => 14,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 0, 'max' => 23, 'step' => 1 ),
		),
		array(
			'title'   => __( 'Working days', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_delivery_working_days',
			'default' => array( '1', '2', '3', '4', '5' ),
			'type'    => 'multiselect',
			'class'   => 'wc-enhanced-select',
			'options' => array(
				'1' => __( 'Monday', 'lineweb-commerce' ),
				'2' => __( 'Tuesday', 'lineweb-commerce' ),
				'3' => __( 'Wednesday', 'lineweb-commerce' ),
				'4' => __( 'Thursday', 'lineweb-commerce' ),
				'5' => __( 'Friday', 'lineweb-commerce' ),
				'6' => __( 'Saturday', 'lineweb-commerce' ),
				'7' => __( 'Sunday', 'lineweb-commerce' ),
			),
		),
		array(
			'title'       => __( 'Store holidays', 'lineweb-commerce' ),
			'desc'        => __( 'Comma-separated YYYY-MM-DD dates. Invalid values are ignored.', 'lineweb-commerce' ),
			'id'          => 'lineweb_commerce_delivery_holidays',
			'default'     => '',
			'type'        => 'textarea',
			'css'         => 'min-width: 360px; min-height: 90px;',
		),
		array(
			'title'   => __( 'Backorders', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_delivery_backorder_mode',
			'default' => 'message',
			'type'    => 'select',
			'options' => array(
				'message' => __( 'Show the backorder message', 'lineweb-commerce' ),
				'window'  => __( 'Show a delayed delivery window', 'lineweb-commerce' ),
				'hide'    => __( 'Hide the estimate', 'lineweb-commerce' ),
			),
		),
		array(
			'title'             => __( 'Backorder extra days', 'lineweb-commerce' ),
			'id'                => 'lineweb_commerce_delivery_backorder_extra_days',
			'default'           => 3,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 0, 'max' => 30, 'step' => 1 ),
		),
		array(
			'title'   => __( 'Out-of-stock products', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_delivery_out_of_stock_mode',
			'default' => 'message',
			'type'    => 'select',
			'options' => array(
				'message' => __( 'Show the out-of-stock message', 'lineweb-commerce' ),
				'hide'    => __( 'Hide the estimate', 'lineweb-commerce' ),
			),
		),
		array(
			'title'   => __( 'Confirm profile', 'lineweb-commerce' ),
			'desc'    => __( 'I have checked these delivery rules and approve their storefront use.', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_delivery_profile_confirmed',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lineweb_commerce_delivery_profile',
		),
		array(
			'title' => __( 'Automatic placements', 'lineweb-commerce' ),
			'desc'  => __( 'Useful blocks appear at WooCommerce conversion points automatically. Disable a placement here when a theme or custom template already provides it.', 'lineweb-commerce' ),
			'type'  => 'title',
			'id'    => 'lineweb_commerce_auto_placements',
		),
		array(
			'title'   => __( 'Product delivery estimate', 'lineweb-commerce' ),
			'desc'    => __( 'Show Delivery Estimate after the add-to-cart area on physical product pages.', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_auto_product_delivery',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Cart free-shipping progress', 'lineweb-commerce' ),
			'desc'    => __( 'Show Free Shipping Progress before checkout in the official Cart block or in the classic cart.', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_auto_cart_progress',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Mini-Cart free-shipping progress', 'lineweb-commerce' ),
			'desc'    => __( 'Show Free Shipping Progress between the Mini-Cart items and its action buttons.', 'lineweb-commerce' ),
			'id'      => 'lineweb_commerce_auto_mini_cart_progress',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lineweb_commerce_auto_placements',
		),
	);
}
add_filter( 'woocommerce_get_settings_products', 'lineweb_commerce_product_settings', 10, 2 );

/**
 * Add an internal class to a parsed block without discarding other classes.
 *
 * @param array  $parsed_block Parsed block.
 * @param string $placement    Placement identifier.
 * @param string $style_class  Optional registered block style class.
 * @return array
 */
function lineweb_commerce_mark_auto_block( $parsed_block, $placement, $style_class = '' ) {
	$classes = trim(
		implode(
			' ',
			array_filter(
				array(
					$parsed_block['attrs']['className'] ?? '',
					'lwc-auto-placement',
					'lwc-auto-placement--' . sanitize_html_class( $placement ),
					$style_class,
				)
			)
		)
	);

	$parsed_block['attrs']['className'] = $classes;

	return $parsed_block;
}

/**
 * Render a registered dynamic block for an automatic WooCommerce placement.
 *
 * @param string $block_name Block type name.
 * @param array  $attributes Block attributes.
 * @param string $placement  Placement identifier.
 * @param string $style      Optional registered block style class.
 * @return string
 */
function lineweb_commerce_render_auto_block( $block_name, $attributes, $placement, $style = '' ) {
	$registry = WP_Block_Type_Registry::get_instance();

	if ( ! $registry->is_registered( $block_name ) ) {
		return '';
	}

	$parsed_block = lineweb_commerce_mark_auto_block(
		array(
			'blockName'    => $block_name,
			'attrs'        => $attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		$placement,
		$style
	);

	return render_block( $parsed_block );
}

/**
 * Check whether content already includes a block the merchant placed.
 *
 * @param string $block_name Block type name.
 * @param int    $post_id    Post ID.
 * @return bool
 */
function lineweb_commerce_post_has_block( $block_name, $post_id ) {
	$post = get_post( $post_id );

	return $post instanceof WP_Post && has_block( $block_name, $post->post_content );
}

/**
 * Check whether a block-template context already contains a block.
 *
 * @param mixed  $context    Block Hooks API context.
 * @param string $block_name Block type name.
 * @return bool
 */
function lineweb_commerce_template_context_has_block( $context, $block_name ) {
	return is_object( $context ) && isset( $context->content ) && is_string( $context->content ) && has_block( $block_name, $context->content );
}

/**
 * Register automatic blocks at stable WooCommerce block-template anchors.
 *
 * Block Hooks keep the injected block visible and removable in the Site
 * Editor. Classic WooCommerce templates are covered by actions below.
 *
 * @param string[] $hooked_block_types Existing hooked block types.
 * @param string   $relative_position  Relative Block Hooks position.
 * @param string   $anchor_block_type  Anchor block name.
 * @param mixed    $context            Template, template part, or pattern.
 * @return string[]
 */
function lineweb_commerce_hooked_block_types( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
	if ( 'after' !== $relative_position ) {
		return $hooked_block_types;
	}

	$hooked_block_type = '';

	if (
		'woocommerce/add-to-cart-form' === $anchor_block_type &&
		lineweb_commerce_auto_placement_enabled( 'product_delivery' ) &&
		! lineweb_commerce_template_context_has_block( $context, 'lineweb-commerce/delivery-estimate' )
	) {
		$hooked_block_type = 'lineweb-commerce/delivery-estimate';
	}

	if (
		'woocommerce/mini-cart-items-block' === $anchor_block_type &&
		lineweb_commerce_auto_placement_enabled( 'mini_cart_progress' ) &&
		! lineweb_commerce_template_context_has_block( $context, 'lineweb-commerce/free-shipping-progress' )
	) {
		$hooked_block_type = 'lineweb-commerce/free-shipping-progress';
	}

	if ( '' !== $hooked_block_type && ! in_array( $hooked_block_type, $hooked_block_types, true ) ) {
		$hooked_block_types[] = $hooked_block_type;
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'lineweb_commerce_hooked_block_types', 10, 4 );

/**
 * Add automatic-placement attributes to blocks inserted by Block Hooks.
 *
 * @param array|null $parsed_hooked_block Parsed hooked block or null.
 * @param string     $hooked_block_type   Hooked block type.
 * @param string     $relative_position   Relative position.
 * @param array      $parsed_anchor_block Anchor block.
 * @return array|null
 */
function lineweb_commerce_prepare_hooked_block( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block ) {
	if ( ! is_array( $parsed_hooked_block ) || 'after' !== $relative_position ) {
		return $parsed_hooked_block;
	}

	$anchor_block_type = $parsed_anchor_block['blockName'] ?? '';

	if ( 'lineweb-commerce/delivery-estimate' === $hooked_block_type && 'woocommerce/add-to-cart-form' === $anchor_block_type ) {
		return lineweb_commerce_mark_auto_block( $parsed_hooked_block, 'product-delivery', 'is-style-inline' );
	}

	if ( 'lineweb-commerce/free-shipping-progress' === $hooked_block_type && 'woocommerce/mini-cart-items-block' === $anchor_block_type ) {
		$parsed_hooked_block['attrs']['threshold']     = 0;
		$parsed_hooked_block['attrs']['hideWhenEmpty'] = true;

		return lineweb_commerce_mark_auto_block( $parsed_hooked_block, 'mini-cart-progress', 'is-style-minimal' );
	}

	return $parsed_hooked_block;
}
add_filter( 'hooked_block', 'lineweb_commerce_prepare_hooked_block', 10, 4 );

/**
 * Avoid automatic output in admin and JSON block previews.
 *
 * @return bool
 */
function lineweb_commerce_is_storefront_request() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}

	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	return true;
}

/**
 * Build Free Shipping Progress for the official Cart block.
 *
 * @return string
 */
function lineweb_commerce_cart_progress_markup() {
	if ( ! lineweb_commerce_is_storefront_request() || ! lineweb_commerce_auto_placement_enabled( 'cart_progress' ) ) {
		return '';
	}

	$post_id = get_the_ID();

	if ( $post_id && lineweb_commerce_post_has_block( 'lineweb-commerce/free-shipping-progress', $post_id ) ) {
		return '';
	}

	return lineweb_commerce_render_auto_block(
		'lineweb-commerce/free-shipping-progress',
		array(
			'threshold'     => 0,
			'hideWhenEmpty' => true,
		),
		'cart-progress'
	);
}

/**
 * Place Free Shipping Progress immediately before the Cart checkout action.
 *
 * @param string $block_content Rendered checkout-action block.
 * @return string
 */
function lineweb_commerce_prepend_cart_progress( $block_content ) {
	if ( ! empty( $GLOBALS['lineweb_commerce_cart_progress_rendered'] ) ) {
		return $block_content;
	}

	$progress = lineweb_commerce_cart_progress_markup();

	if ( '' === $progress ) {
		return $block_content;
	}

	$GLOBALS['lineweb_commerce_cart_progress_rendered'] = true;

	return $progress . $block_content;
}
add_filter( 'render_block_woocommerce/proceed-to-checkout-block', 'lineweb_commerce_prepend_cart_progress' );

/**
 * Fall back after the Cart block when a custom template omits checkout.
 *
 * @param string $block_content Rendered Cart block.
 * @return string
 */
function lineweb_commerce_append_cart_progress( $block_content ) {
	if ( ! empty( $GLOBALS['lineweb_commerce_cart_progress_rendered'] ) ) {
		return $block_content;
	}

	$progress = lineweb_commerce_cart_progress_markup();

	if ( '' === $progress ) {
		return $block_content;
	}

	$GLOBALS['lineweb_commerce_cart_progress_rendered'] = true;

	return $block_content . $progress;
}
add_filter( 'render_block_woocommerce/cart', 'lineweb_commerce_append_cart_progress' );

/**
 * Check the resolved single-product block template for a manual block.
 *
 * @param string $block_name Block type name.
 * @return bool
 */
function lineweb_commerce_single_product_template_has_block( $block_name ) {
	if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() || ! function_exists( 'get_block_template' ) ) {
		return false;
	}

	$template = get_block_template( get_stylesheet() . '//single-product', 'wp_template' );

	return $template instanceof WP_Block_Template && has_block( $block_name, $template->content );
}

/**
 * Show Delivery Estimate in the classic WooCommerce single-product summary.
 */
function lineweb_commerce_classic_product_delivery() {
	if (
		! lineweb_commerce_is_storefront_request() ||
		! lineweb_commerce_auto_placement_enabled( 'product_delivery' ) ||
		! function_exists( 'is_product' ) ||
		! is_product()
	) {
		return;
	}

	$product_id = get_queried_object_id();

	if (
		lineweb_commerce_post_has_block( 'lineweb-commerce/delivery-estimate', $product_id ) ||
		lineweb_commerce_single_product_template_has_block( 'lineweb-commerce/delivery-estimate' )
	) {
		return;
	}

	echo lineweb_commerce_render_auto_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'lineweb-commerce/delivery-estimate',
		array( 'productId' => $product_id ),
		'product-delivery',
		'is-style-inline'
	);
}
add_action( 'woocommerce_single_product_summary', 'lineweb_commerce_classic_product_delivery', 35 );

/**
 * Show Free Shipping Progress in the classic cart before cart totals.
 */
function lineweb_commerce_classic_cart_progress() {
	if ( ! lineweb_commerce_is_storefront_request() || ! lineweb_commerce_auto_placement_enabled( 'cart_progress' ) ) {
		return;
	}

	$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;

	if ( $cart_page_id > 0 && lineweb_commerce_post_has_block( 'lineweb-commerce/free-shipping-progress', $cart_page_id ) ) {
		return;
	}

	echo lineweb_commerce_render_auto_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'lineweb-commerce/free-shipping-progress',
		array(
			'threshold'     => 0,
			'hideWhenEmpty' => true,
		),
		'cart-progress'
	);
}
add_action( 'woocommerce_before_cart_collaterals', 'lineweb_commerce_classic_cart_progress' );

/**
 * Show Free Shipping Progress in the classic Mini-Cart widget.
 */
function lineweb_commerce_classic_mini_cart_progress() {
	if ( ! lineweb_commerce_is_storefront_request() || ! lineweb_commerce_auto_placement_enabled( 'mini_cart_progress' ) ) {
		return;
	}

	echo lineweb_commerce_render_auto_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'lineweb-commerce/free-shipping-progress',
		array(
			'threshold'     => 0,
			'hideWhenEmpty' => true,
		),
		'mini-cart-progress',
		'is-style-minimal'
	);
}
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'lineweb_commerce_classic_mini_cart_progress' );
