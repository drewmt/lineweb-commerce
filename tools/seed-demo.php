<?php
/**
 * Idempotently create the local practical-block demo catalog.
 *
 * Run only in wp-env:
 * wp eval-file wp-content/plugins/lineweb-commerce/tools/seed-demo.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'The demo seeder is restricted to local/development WordPress environments.' );
}

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
	WP_CLI::error( 'Activate WooCommerce before running the demo seeder.' );
}

// Keep the local storefront reviewable without changing production behavior.
update_option( 'woocommerce_coming_soon', 'no' );

// E2E explicitly exercises the confirmed profile, then verifies the
// storefront fails closed when this option is unchecked through the UI.
update_option( 'lineweb_commerce_delivery_profile_confirmed', 'yes' );

// Give the automatic cart placements a real WooCommerce-owned rule to read.
$demo_zone               = new WC_Shipping_Zone( 0 );
$demo_free_shipping      = null;
$demo_shipping_method_id = 0;

foreach ( $demo_zone->get_shipping_methods( false ) as $shipping_method ) {
	if (
		$shipping_method instanceof WC_Shipping_Free_Shipping &&
		'Lineweb Demo Free Shipping' === $shipping_method->get_option( 'title', '' )
	) {
		$demo_free_shipping = $shipping_method;
		break;
	}
}

if ( ! $demo_free_shipping ) {
	$demo_shipping_method_id = $demo_zone->add_shipping_method( 'free_shipping' );
	$demo_free_shipping      = $demo_shipping_method_id ? new WC_Shipping_Free_Shipping( $demo_shipping_method_id ) : null;
}

if ( $demo_free_shipping instanceof WC_Shipping_Free_Shipping ) {
	$demo_instance_settings = array_merge(
		$demo_free_shipping->instance_settings,
		array(
			'title'            => 'Lineweb Demo Free Shipping',
			'requires'         => 'min_amount',
			'min_amount'       => '200',
			'ignore_discounts' => 'no',
		)
	);

	update_option(
		$demo_free_shipping->get_instance_option_key(),
		apply_filters(
			'woocommerce_shipping_free_shipping_instance_settings_values',
			$demo_instance_settings,
			$demo_free_shipping
		),
		'yes'
	);
} else {
	WP_CLI::warning( 'Could not configure the local demo free-shipping method.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Import one local demo image once.
 *
 * @param string $filename Asset filename.
 * @param string $title    Attachment title.
 * @return int
 */
function lineweb_commerce_demo_image( $filename, $title ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_lineweb_commerce_demo_asset',
			'meta_value'     => $filename,
		)
	);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$source = __DIR__ . '/demo-assets/' . $filename;
	$temp   = wp_tempnam( $filename );

	if ( ! $temp || ! copy( $source, $temp ) ) {
		WP_CLI::warning( 'Could not prepare demo image: ' . $filename );
		return 0;
	}

	$attachment_id = media_handle_sideload(
		array(
			'name'     => $filename,
			'tmp_name' => $temp,
		),
		0,
		$title
	);

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $temp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		WP_CLI::warning( $attachment_id->get_error_message() );
		return 0;
	}

	update_post_meta( $attachment_id, '_lineweb_commerce_demo_asset', $filename );
	return (int) $attachment_id;
}

$term = term_exists( 'Lineweb Commerce Demo', 'product_cat' );

if ( ! $term ) {
	$term = wp_insert_term( 'Lineweb Commerce Demo', 'product_cat' );
}

$category_id = is_wp_error( $term ) ? 0 : (int) ( is_array( $term ) ? $term['term_id'] : $term );
$catalog     = array(
	array(
		'sku'         => 'LWC-DEMO-START',
		'name'        => 'Lineweb Starter Kit',
		'price'       => '49',
		'image'       => 'starter.png',
		'short'       => 'A focused foundation for teams that value a fast, confident first step.',
		'description' => 'A local demonstration product for the practical Lineweb Commerce blocks.',
		'weight'      => '1.2',
		'dimensions'  => array( '32', '24', '8' ),
		'attributes'  => array(
			'Material'   => array( 'Recycled aluminium' ),
			'Best for'   => array( 'Small teams' ),
			'Setup time' => array( '15 minutes' ),
		),
	),
	array(
		'sku'         => 'LWC-DEMO-GROW',
		'name'        => 'Lineweb Growth System',
		'price'       => '129',
		'image'       => 'growth.png',
		'short'       => 'A balanced system for teams that need more capability without unnecessary complexity.',
		'description' => 'A local demonstration product for the practical Lineweb Commerce blocks.',
		'weight'      => '2.4',
		'dimensions'  => array( '42', '30', '12' ),
		'attributes'  => array(
			'Material'   => array( 'Powder-coated steel' ),
			'Best for'   => array( 'Growing teams' ),
			'Setup time' => array( '30 minutes' ),
		),
	),
	array(
		'sku'         => 'LWC-DEMO-SCALE',
		'name'        => 'Lineweb Scale Engine',
		'price'       => '279',
		'image'       => 'scale.png',
		'short'       => 'The deepest capability for demanding workflows and long-term expansion.',
		'description' => 'A local demonstration product for the practical Lineweb Commerce blocks.',
		'weight'      => '4.8',
		'dimensions'  => array( '58', '40', '18' ),
		'attributes'  => array(
			'Material'   => array( 'Anodized aluminium' ),
			'Best for'   => array( 'Advanced workflows' ),
			'Setup time' => array( '45 minutes' ),
		),
	),
);

$product_ids = array();

foreach ( $catalog as $record ) {
	$product_id = wc_get_product_id_by_sku( $record['sku'] );
	$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();

	if ( ! $product instanceof WC_Product_Simple ) {
		WP_CLI::warning( 'Skipped an incompatible existing SKU: ' . $record['sku'] );
		continue;
	}

	$product->set_name( $record['name'] );
	$product->set_sku( $record['sku'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( $record['price'] );
	$product->set_price( $record['price'] );
	$product->set_manage_stock( false );
	$product->set_stock_status( 'instock' );
	$product->set_short_description( $record['short'] );
	$product->set_description( $record['description'] );
	$product->set_virtual( false );
	$product->set_weight( $record['weight'] );
	$product->set_length( $record['dimensions'][0] );
	$product->set_width( $record['dimensions'][1] );
	$product->set_height( $record['dimensions'][2] );

	$product_attributes = array();

	foreach ( $record['attributes'] as $position => $attribute_record ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( $position );
		$attribute->set_options( $attribute_record );
		$attribute->set_position( count( $product_attributes ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$product_attributes[] = $attribute;
	}

	$product->set_attributes( $product_attributes );

	if ( $category_id > 0 ) {
		$product->set_category_ids( array( $category_id ) );
	}

	$image_id = lineweb_commerce_demo_image( $record['image'], $record['name'] );
	if ( $image_id > 0 ) {
		$product->set_image_id( $image_id );
	}

	$product_ids[] = $product->save();
}

// A variable product exercises the classic variation form integration for
// Specifications and Delivery Estimate without adding production fixtures.
$variable_product_id = wc_get_product_id_by_sku( 'LWC-DEMO-VARIABLE' );
$variable_product    = $variable_product_id ? wc_get_product( $variable_product_id ) : new WC_Product_Variable();

if ( $variable_product instanceof WC_Product_Variable ) {
	$plan_attribute = new WC_Product_Attribute();
	$plan_attribute->set_id( 0 );
	$plan_attribute->set_name( 'Plan' );
	$plan_attribute->set_options( array( 'Physical', 'Backorder', 'Virtual' ) );
	$plan_attribute->set_position( 0 );
	$plan_attribute->set_visible( true );
	$plan_attribute->set_variation( true );

	$variable_product->set_name( 'Lineweb Variable Kit' );
	$variable_product->set_sku( 'LWC-DEMO-VARIABLE' );
	$variable_product->set_status( 'publish' );
	$variable_product->set_catalog_visibility( 'visible' );
	$variable_product->set_attributes( array( $plan_attribute ) );
	$variable_product->set_weight( '2.0' );
	$variable_product->set_length( '40' );
	$variable_product->set_width( '30' );
	$variable_product->set_height( '10' );
	$variable_product_id = $variable_product->save();

	$variable_product->set_description(
		serialize_blocks(
			array(
				array(
					'blockName'    => 'lineweb-commerce/product-specifications',
					'attrs'        => array( 'productId' => $variable_product_id ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			)
		)
	);
	$variable_product->save();

	$variation_records = array(
		array( 'sku' => 'LWC-DEMO-VAR-PHYSICAL', 'plan' => 'Physical', 'price' => '79', 'virtual' => false, 'stock' => 'instock', 'weight' => '1.5', 'dimensions' => array( '36', '26', '8' ) ),
		array( 'sku' => 'LWC-DEMO-VAR-BACKORDER', 'plan' => 'Backorder', 'price' => '99', 'virtual' => false, 'stock' => 'onbackorder', 'weight' => '2.5', 'dimensions' => array( '44', '34', '12' ) ),
		array( 'sku' => 'LWC-DEMO-VAR-VIRTUAL', 'plan' => 'Virtual', 'price' => '39', 'virtual' => true, 'stock' => 'instock', 'weight' => '', 'dimensions' => array( '', '', '' ) ),
	);

	foreach ( $variation_records as $record ) {
		$variation_id = wc_get_product_id_by_sku( $record['sku'] );
		$variation    = $variation_id ? wc_get_product( $variation_id ) : new WC_Product_Variation();
		if ( ! $variation instanceof WC_Product_Variation ) {
			WP_CLI::warning( 'Skipped an incompatible existing variation SKU: ' . $record['sku'] );
			continue;
		}

		$variation->set_parent_id( $variable_product_id );
		$variation->set_sku( $record['sku'] );
		$variation->set_status( 'publish' );
		$variation->set_attributes( array( 'plan' => $record['plan'] ) );
		$variation->set_regular_price( $record['price'] );
		$variation->set_price( $record['price'] );
		$variation->set_virtual( $record['virtual'] );
		$variation->set_weight( $record['weight'] );
		$variation->set_length( $record['dimensions'][0] );
		$variation->set_width( $record['dimensions'][1] );
		$variation->set_height( $record['dimensions'][2] );
		if ( 'onbackorder' === $record['stock'] ) {
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( 0 );
			$variation->set_backorders( 'yes' );
		} else {
			$variation->set_manage_stock( false );
			$variation->set_stock_status( 'instock' );
		}
		$variation->save();
	}

	WC_Product_Variable::sync( $variable_product_id );
	$product_ids[] = $variable_product_id;
} else {
	WP_CLI::warning( 'Skipped an incompatible existing variable-product SKU.' );
}

WP_CLI::success( 'Practical block demo product IDs: ' . implode( ',', $product_ids ) );
