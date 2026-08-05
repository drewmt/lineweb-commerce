<?php
/**
 * Read-only merchant diagnostics for Lineweb Commerce Suite.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether any enabled WooCommerce zone provides free shipping.
 *
 * @return bool
 */
function lineweb_commerce_has_free_shipping_method() {
	$zones   = WC_Shipping_Zones::get_zones();
	$zones[] = array( 'shipping_methods' => WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods( true ) );

	foreach ( $zones as $zone ) {
		foreach ( $zone['shipping_methods'] ?? array() as $method ) {
			if ( $method instanceof WC_Shipping_Free_Shipping && 'yes' === $method->enabled ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Return diagnostics without changing products, templates, or settings.
 *
 * @return array<int, array{status: string, title: string, message: string}>
 */
function lineweb_commerce_diagnostics() {
	$profile           = lineweb_commerce_delivery_profile();
	$has_free_shipping = lineweb_commerce_has_free_shipping_method();
	$diagnostics = array(
		array(
			'status'  => $profile['confirmed'] ? 'good' : 'warning',
			'title'   => __( 'Global delivery profile', 'lineweb-commerce' ),
			'message' => $profile['confirmed']
				? __( 'Confirmed. Automatic estimates may use the merchant-approved rules.', 'lineweb-commerce' )
				: __( 'Not confirmed. Automatic delivery estimates are safely paused.', 'lineweb-commerce' ),
		),
		array(
			'status'  => $has_free_shipping ? 'good' : 'warning',
			'title'   => __( 'Free-shipping rule', 'lineweb-commerce' ),
			'message' => $has_free_shipping
				? __( 'At least one enabled WooCommerce free-shipping method is available.', 'lineweb-commerce' )
				: __( 'No enabled free-shipping method was found; automatic progress bars remain empty.', 'lineweb-commerce' ),
		),
	);

	$cart_page_id    = wc_get_page_id( 'cart' );
	$cart_manual     = $cart_page_id > 0 && lineweb_commerce_post_has_block( 'lineweb-commerce/free-shipping-progress', $cart_page_id );
	$delivery_manual = lineweb_commerce_single_product_template_has_block( 'lineweb-commerce/delivery-estimate' );
	$duplicates      = ( $cart_manual && lineweb_commerce_admin_placement_enabled( 'lineweb_commerce_auto_cart_progress' ) ) || ( $delivery_manual && lineweb_commerce_admin_placement_enabled( 'lineweb_commerce_auto_product_delivery' ) );
	$diagnostics[] = array(
		'status'  => $duplicates ? 'info' : 'good',
		'title'   => __( 'Duplicate protection', 'lineweb-commerce' ),
		'message' => $duplicates
			? __( 'A manual block overlaps an enabled placement. Runtime duplicate protection will keep only one output.', 'lineweb-commerce' )
			: __( 'No overlapping manual and automatic placement was detected.', 'lineweb-commerce' ),
	);

	$unsupported = wc_get_products(
		array(
			'limit'  => 1,
			'return' => 'ids',
			'status' => 'publish',
			'type'   => array( 'external', 'grouped' ),
		)
	);
	$diagnostics[] = array(
		'status'  => empty( $unsupported ) ? 'good' : 'info',
		'title'   => __( 'Product type coverage', 'lineweb-commerce' ),
		'message' => empty( $unsupported )
			? __( 'No published grouped or external products were detected.', 'lineweb-commerce' )
			: __( 'Grouped or external products exist. Delivery placement supports physical purchasable products; comparison still shows their catalog facts.', 'lineweb-commerce' ),
	);

	$diagnostics[] = array(
		'status'  => 'info',
		'title'   => __( 'Actual placement locations', 'lineweb-commerce' ),
		'message' => __( 'Delivery: product add-to-cart area. Progress: Cart checkout action, Cart fallback, Mini-Cart block, and classic Mini-Cart widget.', 'lineweb-commerce' ),
	);

	return $diagnostics;
}
