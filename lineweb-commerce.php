<?php
/**
 * Plugin Name:       Lineweb Commerce Suite
 * Description:       Practical product, delivery, and cart blocks for WooCommerce.
 * Version:           0.6.0
 * Requires at least: 6.9
 * Requires PHP:      8.3
 * Requires Plugins:  woocommerce
 * WC requires at least: 10.8
 * WC tested up to:   10.9
 * Author:            Lineweb
 * Author URI:        https://lineweb.gr/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lineweb-commerce
 *
 * @package           Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

define( 'LINEWEB_COMMERCE_VERSION', '0.6.0' );
define( 'LINEWEB_COMMERCE_FILE', __FILE__ );
define( 'LINEWEB_COMMERCE_DIR', __DIR__ );
define( 'LINEWEB_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/decision-helpers.php';
require_once __DIR__ . '/includes/commerce-helpers.php';
require_once __DIR__ . '/includes/auto-placements.php';
require_once __DIR__ . '/includes/diagnostics.php';
require_once __DIR__ . '/includes/admin-page.php';

/** Load bundled translations for direct ZIP installations. */
function lineweb_commerce_load_textdomain() {
	load_plugin_textdomain( 'lineweb-commerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'lineweb_commerce_load_textdomain', 1 );

/**
 * Declare compatibility only for WooCommerce storage/features this release
 * does not bypass or replace.
 */
function lineweb_commerce_declare_compatibility() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'lineweb_commerce_declare_compatibility' );

/**
 * Add the dedicated commerce category when WooCommerce is available.
 *
 * @param array $categories Existing block categories.
 * @return array
 */
function lineweb_commerce_block_category( $categories ) {
	foreach ( $categories as $category ) {
		if ( isset( $category['slug'] ) && 'lineweb-commerce' === $category['slug'] ) {
			return $categories;
		}
	}

	array_unshift(
		$categories,
		array(
			'slug'  => 'lineweb-commerce',
			'title' => __( 'Lineweb Commerce', 'lineweb-commerce' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'lineweb_commerce_block_category' );

/**
 * Expose the matching automatic free-shipping rule on the existing Cart API.
 */
function lineweb_commerce_register_cart_extension() {
	if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema' ) ) {
		return;
	}

	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
			'namespace'       => 'lineweb-commerce',
			'data_callback'   => 'lineweb_commerce_cart_extension_data',
			'schema_callback' => 'lineweb_commerce_cart_extension_schema',
			'schema_type'     => ARRAY_A,
		)
	);
}
add_action( 'woocommerce_blocks_loaded', 'lineweb_commerce_register_cart_extension' );

/**
 * Register compiled blocks after WooCommerce has registered its product model.
 */
function lineweb_commerce_init() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'lineweb_commerce_init', 20 );

/**
 * Expose store-owned formatting and delivery settings to block previews.
 *
 * Data stays local to wp-admin and is attached only to Commerce editor assets.
 */
function lineweb_commerce_editor_settings() {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) {
		return;
	}

	$settings = array(
		'currencyCode'      => get_woocommerce_currency(),
		'currencyDecimals'  => wc_get_price_decimals(),
		'deliveryProfile'   => lineweb_commerce_delivery_profile(),
		'deliverySettingsUrl' => admin_url( 'admin.php?page=wc-settings&tab=products&section=lineweb-commerce' ),
	);
	$script   = 'window.linewebCommerceEditor = ' . wp_json_encode( $settings ) . ';';

	foreach ( array( 'lineweb-commerce-delivery-estimate-editor-script', 'lineweb-commerce-free-shipping-progress-editor-script' ) as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_add_inline_script( $handle, $script, 'before' );
		}
	}
}
add_action( 'enqueue_block_editor_assets', 'lineweb_commerce_editor_settings', 20 );
