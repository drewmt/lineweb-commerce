<?php
/**
 * Branded administration home for Lineweb Commerce Suite.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the WooCommerce settings URL for automatic placements.
 *
 * @return string
 */
function lineweb_commerce_admin_settings_url() {
	return admin_url( 'admin.php?page=wc-settings&tab=products&section=lineweb-commerce' );
}

/**
 * Register the suite home inside the WooCommerce menu.
 */
function lineweb_commerce_register_admin_page() {
	add_submenu_page(
		'woocommerce',
		__( 'Lineweb Commerce Suite', 'lineweb-commerce' ),
		__( 'Lineweb Commerce', 'lineweb-commerce' ),
		'manage_woocommerce',
		'lineweb-commerce',
		'lineweb_commerce_render_admin_page',
		8
	);
}
add_action( 'admin_menu', 'lineweb_commerce_register_admin_page', 30 );

/**
 * Load the branded stylesheet only on the suite home.
 *
 * @param string $hook_suffix Current admin screen hook.
 */
function lineweb_commerce_admin_assets( $hook_suffix ) {
	if ( 'woocommerce_page_lineweb-commerce' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style(
		'lineweb-commerce-admin',
		LINEWEB_COMMERCE_URL . 'assets/admin.css',
		array(),
		LINEWEB_COMMERCE_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'lineweb_commerce_admin_assets' );

/**
 * Remember that a newly activated plugin should show its welcome screen once.
 */
function lineweb_commerce_admin_activate() {
	update_option( 'lineweb_commerce_activation_redirect', 'yes', false );
}
register_activation_hook( LINEWEB_COMMERCE_FILE, 'lineweb_commerce_admin_activate' );

/**
 * Redirect the administrator once after an ordinary single-plugin activation.
 */
function lineweb_commerce_activation_redirect() {
	if ( 'yes' !== get_option( 'lineweb_commerce_activation_redirect' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_woocommerce' ) || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	delete_option( 'lineweb_commerce_activation_redirect' );

	if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect guard from core activation flow.
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=lineweb-commerce' ) );
	exit;
}
add_action( 'admin_init', 'lineweb_commerce_activation_redirect' );

/**
 * Add direct suite and settings links beside Deactivate on the Plugins screen.
 *
 * @param string[] $links Existing plugin action links.
 * @return string[]
 */
function lineweb_commerce_plugin_action_links( $links ) {
	$suite_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( admin_url( 'admin.php?page=lineweb-commerce' ) ),
		esc_html__( 'Explore suite', 'lineweb-commerce' )
	);
	$settings_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( lineweb_commerce_admin_settings_url() ),
		esc_html__( 'Settings', 'lineweb-commerce' )
	);

	array_unshift( $links, $settings_link );
	array_unshift( $links, $suite_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( LINEWEB_COMMERCE_FILE ), 'lineweb_commerce_plugin_action_links' );

/**
 * Return whether a stored automatic placement is enabled.
 *
 * @param string $option_name Placement option name.
 * @return bool
 */
function lineweb_commerce_admin_placement_enabled( $option_name ) {
	return 'yes' === get_option( $option_name, 'yes' );
}

/**
 * Render one linked feature summary.
 *
 * @param array<string, string> $feature Feature content.
 */
function lineweb_commerce_admin_feature_card( $feature ) {
	?>
	<a class="lineweb-suite-admin__feature-card" href="#<?php echo esc_attr( $feature['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: feature name. */ __( 'Explore %s', 'lineweb-commerce' ), $feature['title'] ) ); ?>">
		<span class="lineweb-suite-admin__icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>"></span></span>
		<span>
			<span class="lineweb-suite-admin__feature-meta"><?php echo esc_html( $feature['meta'] ); ?></span>
			<h3><?php echo esc_html( $feature['title'] ); ?></h3>
			<p><?php echo esc_html( $feature['description'] ); ?></p>
		</span>
		<span class="lineweb-suite-admin__arrow" aria-hidden="true">↘</span>
	</a>
	<?php
}

/**
 * Render one automatic-placement status row.
 *
 * @param string $title       Placement title.
 * @param string $description Placement description.
 * @param bool   $enabled     Whether the placement is enabled.
 */
function lineweb_commerce_admin_placement_row( $title, $description, $enabled ) {
	?>
	<div class="lineweb-suite-admin__placement">
		<div><strong><?php echo esc_html( $title ); ?></strong><span><?php echo esc_html( $description ); ?></span></div>
		<span class="lineweb-suite-admin__badge<?php echo $enabled ? '' : ' is-disabled'; ?>"><?php echo esc_html( $enabled ? __( 'Enabled', 'lineweb-commerce' ) : __( 'Disabled', 'lineweb-commerce' ) ); ?></span>
	</div>
	<?php
}

/**
 * Render one read-only diagnostic result.
 *
 * @param array{status: string, title: string, message: string} $diagnostic Diagnostic result.
 */
function lineweb_commerce_admin_diagnostic_row( $diagnostic ) {
	$status_labels = array(
		'good'    => __( 'Ready', 'lineweb-commerce' ),
		'warning' => __( 'Action needed', 'lineweb-commerce' ),
		'info'    => __( 'Info', 'lineweb-commerce' ),
	);
	?>
	<div class="lineweb-suite-admin__placement">
		<div><strong><?php echo esc_html( $diagnostic['title'] ); ?></strong><span><?php echo esc_html( $diagnostic['message'] ); ?></span></div>
		<span class="lineweb-suite-admin__badge<?php echo 'good' === $diagnostic['status'] ? '' : ' is-disabled'; ?>"><?php echo esc_html( $status_labels[ $diagnostic['status'] ] ?? $status_labels['info'] ); ?></span>
	</div>
	<?php
}

/**
 * Render the Commerce Suite administration home.
 */
function lineweb_commerce_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'lineweb-commerce' ) );
	}

	$product_delivery_selected = lineweb_commerce_admin_placement_enabled( 'lineweb_commerce_auto_product_delivery' );
	$product_delivery_enabled = lineweb_commerce_auto_placement_enabled( 'product_delivery' );
	$cart_progress_enabled    = lineweb_commerce_admin_placement_enabled( 'lineweb_commerce_auto_cart_progress' );
	$mini_cart_enabled        = lineweb_commerce_admin_placement_enabled( 'lineweb_commerce_auto_mini_cart_progress' );
	$enabled_count            = count( array_filter( array( $product_delivery_enabled, $cart_progress_enabled, $mini_cart_enabled ) ) );
	$diagnostics              = lineweb_commerce_diagnostics();
	$features                 = array(
		array(
			'id'          => 'product-specifications',
			'icon'        => 'dashicons-list-view',
			'meta'        => __( 'Manual block', 'lineweb-commerce' ),
			'title'       => __( 'Product Specifications', 'lineweb-commerce' ),
			'description' => __( 'Show live SKU, stock, weight, dimensions, and visible product attributes.', 'lineweb-commerce' ),
		),
		array(
			'id'          => 'delivery-estimate',
			'icon'        => 'dashicons-clock',
			'meta'        => $product_delivery_enabled ? __( 'Automatic · Enabled', 'lineweb-commerce' ) : __( 'Manual only', 'lineweb-commerce' ),
			'title'       => __( 'Delivery Estimate', 'lineweb-commerce' ),
			'description' => __( 'Give customers a stock-aware delivery window before they add a physical product to cart.', 'lineweb-commerce' ),
		),
		array(
			'id'          => 'free-shipping-progress',
			'icon'        => 'dashicons-cart',
			'meta'        => ( $cart_progress_enabled || $mini_cart_enabled ) ? __( 'Automatic · Cart', 'lineweb-commerce' ) : __( 'Manual only', 'lineweb-commerce' ),
			'title'       => __( 'Free Shipping Progress', 'lineweb-commerce' ),
			'description' => __( 'Show how much remains for free shipping in the Cart, Mini-Cart, or selected content.', 'lineweb-commerce' ),
		),
		array(
			'id'          => 'product-comparison',
			'icon'        => 'dashicons-columns',
			'meta'        => __( 'Manual block', 'lineweb-commerce' ),
			'title'       => __( 'Product Comparison', 'lineweb-commerce' ),
			'description' => __( 'Compare two to four catalog-visible products with live prices, availability, dimensions, and selected attributes.', 'lineweb-commerce' ),
		),
	);
	?>
	<div class="wrap lineweb-suite-admin">
		<section class="lineweb-suite-admin__hero" aria-labelledby="lineweb-commerce-title">
			<div>
				<p class="lineweb-suite-admin__eyebrow"><?php esc_html_e( 'Lineweb Commerce Suite', 'lineweb-commerce' ); ?></p>
				<h1 id="lineweb-commerce-title"><?php esc_html_e( 'Useful WooCommerce clarity, placed where it matters.', 'lineweb-commerce' ); ?></h1>
				<p class="lineweb-suite-admin__lede"><?php esc_html_e( 'Make product facts, delivery timing, and free-shipping progress easier to understand—with automatic placements that remain under your control.', 'lineweb-commerce' ); ?></p>
				<div class="lineweb-suite-admin__actions">
					<a class="lineweb-suite-admin__button lineweb-suite-admin__button--primary" href="<?php echo esc_url( lineweb_commerce_admin_settings_url() ); ?>">
						<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e( 'Placement settings', 'lineweb-commerce' ); ?>
					</a>
					<a class="lineweb-suite-admin__button lineweb-suite-admin__button--secondary" href="#lineweb-commerce-features"><?php esc_html_e( 'Explore features', 'lineweb-commerce' ); ?></a>
				</div>
			</div>
			<aside class="lineweb-suite-admin__brand-card" aria-label="<?php esc_attr_e( 'Plugin status', 'lineweb-commerce' ); ?>">
				<img class="lineweb-suite-admin__brand-mark" src="<?php echo esc_url( LINEWEB_COMMERCE_URL . 'assets/lineweb-logo.png' ); ?>" alt="<?php esc_attr_e( 'Lineweb — Creative Digital Agency', 'lineweb-commerce' ); ?>" />
				<div class="lineweb-suite-admin__brand-status">
					<div class="lineweb-suite-admin__status"><?php esc_html_e( 'Commerce Suite is active', 'lineweb-commerce' ); ?></div>
					<p class="lineweb-suite-admin__brand-meta"><?php echo esc_html( sprintf( /* translators: %s: plugin version. */ __( 'Version %s · WooCommerce-native', 'lineweb-commerce' ), LINEWEB_COMMERCE_VERSION ) ); ?></p>
				</div>
			</aside>
		</section>

		<div class="lineweb-suite-admin__quick-stats" aria-label="<?php esc_attr_e( 'Suite summary', 'lineweb-commerce' ); ?>">
			<div class="lineweb-suite-admin__stat"><strong><?php esc_html_e( '4 practical blocks', 'lineweb-commerce' ); ?></strong><span><?php esc_html_e( 'Product, delivery, comparison, and cart', 'lineweb-commerce' ); ?></span></div>
			<div class="lineweb-suite-admin__stat"><strong><?php echo esc_html( sprintf( /* translators: %d: enabled placement count. */ __( '%d of 3 automatic', 'lineweb-commerce' ), $enabled_count ) ); ?></strong><span><?php esc_html_e( 'Live placement status', 'lineweb-commerce' ); ?></span></div>
			<div class="lineweb-suite-admin__stat"><strong><?php esc_html_e( 'Woo-owned data', 'lineweb-commerce' ); ?></strong><span><?php esc_html_e( 'No copied catalog facts', 'lineweb-commerce' ); ?></span></div>
		</div>

		<section id="lineweb-commerce-features" class="lineweb-suite-admin__section" aria-labelledby="lineweb-commerce-features-title">
			<div class="lineweb-suite-admin__section-heading">
				<h2 id="lineweb-commerce-features-title"><?php esc_html_e( 'Features customers can actually use', 'lineweb-commerce' ); ?></h2>
				<p><?php esc_html_e( 'Open a feature to see whether it appears automatically, where it belongs, and what the merchant controls.', 'lineweb-commerce' ); ?></p>
			</div>
			<div class="lineweb-suite-admin__feature-grid">
				<?php foreach ( $features as $feature ) : ?>
					<?php lineweb_commerce_admin_feature_card( $feature ); ?>
				<?php endforeach; ?>
			</div>
		</section>

		<section id="automatic-placements" class="lineweb-suite-admin__section" aria-labelledby="lineweb-commerce-placement-title">
			<div class="lineweb-suite-admin__section-heading">
				<h2 id="lineweb-commerce-placement-title"><?php esc_html_e( 'Automatic placement status', 'lineweb-commerce' ); ?></h2>
				<p><?php esc_html_e( 'These integrations use official WooCommerce positions. Change them at any time without editing a page or template.', 'lineweb-commerce' ); ?></p>
			</div>
			<div class="lineweb-suite-admin__placement-list">
				<?php lineweb_commerce_admin_placement_row( __( 'Product delivery estimate', 'lineweb-commerce' ), $product_delivery_selected && ! $product_delivery_enabled ? __( 'Selected, but paused until the global profile is confirmed.', 'lineweb-commerce' ) : __( 'After the add-to-cart area on physical product pages.', 'lineweb-commerce' ), $product_delivery_enabled ); ?>
				<?php lineweb_commerce_admin_placement_row( __( 'Cart free-shipping progress', 'lineweb-commerce' ), __( 'Inside Cart totals, immediately before the checkout action.', 'lineweb-commerce' ), $cart_progress_enabled ); ?>
				<?php lineweb_commerce_admin_placement_row( __( 'Mini-Cart free-shipping progress', 'lineweb-commerce' ), __( 'Between Mini-Cart items and its action buttons.', 'lineweb-commerce' ), $mini_cart_enabled ); ?>
			</div>
			<div class="lineweb-suite-admin__detail-actions">
				<a class="lineweb-suite-admin__button lineweb-suite-admin__button--ink" href="<?php echo esc_url( lineweb_commerce_admin_settings_url() ); ?>"><?php esc_html_e( 'Manage placements', 'lineweb-commerce' ); ?></a>
			</div>
		</section>

		<section id="diagnostics" class="lineweb-suite-admin__section" aria-labelledby="lineweb-commerce-diagnostics-title">
			<div class="lineweb-suite-admin__section-heading">
				<h2 id="lineweb-commerce-diagnostics-title"><?php esc_html_e( 'Merchant diagnostics', 'lineweb-commerce' ); ?></h2>
				<p><?php esc_html_e( 'Read-only checks explain what is configured, paused, duplicated, or unsupported without changing store data.', 'lineweb-commerce' ); ?></p>
			</div>
			<div class="lineweb-suite-admin__placement-list">
				<?php foreach ( $diagnostics as $diagnostic ) : ?><?php lineweb_commerce_admin_diagnostic_row( $diagnostic ); ?><?php endforeach; ?>
			</div>
		</section>

		<section class="lineweb-suite-admin__section" aria-labelledby="lineweb-commerce-details-title">
			<div class="lineweb-suite-admin__section-heading">
				<h2 id="lineweb-commerce-details-title"><?php esc_html_e( 'Where each feature belongs', 'lineweb-commerce' ); ?></h2>
				<p><?php esc_html_e( 'Automatic features work immediately. Manual blocks remain available for product templates, landing pages, buying guides, posts, and pages.', 'lineweb-commerce' ); ?></p>
			</div>
			<div class="lineweb-suite-admin__detail-grid">
				<article id="product-specifications" class="lineweb-suite-admin__detail">
					<p class="lineweb-suite-admin__detail-label"><?php esc_html_e( 'Manual block', 'lineweb-commerce' ); ?></p>
					<h3><?php esc_html_e( 'Product Specifications', 'lineweb-commerce' ); ?></h3>
					<p><?php esc_html_e( 'Use it when you want a custom, compact presentation instead of duplicating WooCommerce Additional Information automatically.', 'lineweb-commerce' ); ?></p>
					<ul><li><?php esc_html_e( 'Current or selected product', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Live catalog facts through WooCommerce', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Useful in templates, guides, pages, and posts', 'lineweb-commerce' ); ?></li></ul>
				</article>
				<article id="delivery-estimate" class="lineweb-suite-admin__detail">
					<p class="lineweb-suite-admin__detail-label"><?php esc_html_e( 'Automatic and manual', 'lineweb-commerce' ); ?></p>
					<h3><?php esc_html_e( 'Delivery Estimate', 'lineweb-commerce' ); ?></h3>
					<p><?php esc_html_e( 'The automatic card appears after add to cart for physical products. Insert it manually when a landing page needs the same clarity.', 'lineweb-commerce' ); ?></p>
					<ul><li><?php esc_html_e( 'Stock and backorder aware', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Business days, cutoff time, and holidays', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Uses the WordPress timezone', 'lineweb-commerce' ); ?></li></ul>
				</article>
				<article id="free-shipping-progress" class="lineweb-suite-admin__detail">
					<p class="lineweb-suite-admin__detail-label"><?php esc_html_e( 'Automatic and manual', 'lineweb-commerce' ); ?></p>
					<h3><?php esc_html_e( 'Free Shipping Progress', 'lineweb-commerce' ); ?></h3>
					<p><?php esc_html_e( 'The Cart and Mini-Cart placements follow the live customer cart. A manual block is also available for selected content.', 'lineweb-commerce' ); ?></p>
					<ul><li><?php esc_html_e( 'Automatic shipping-zone threshold', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Live Store API updates', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Respects WooCommerce subtotal and coupon policy', 'lineweb-commerce' ); ?></li></ul>
				</article>
				<article id="product-comparison" class="lineweb-suite-admin__detail">
					<p class="lineweb-suite-admin__detail-label"><?php esc_html_e( 'Manual block', 'lineweb-commerce' ); ?></p>
					<h3><?php esc_html_e( 'Product Comparison', 'lineweb-commerce' ); ?></h3>
					<p><?php esc_html_e( 'Use it in buying guides, posts, pages, or product content when shoppers need a factual side-by-side view.', 'lineweb-commerce' ); ?></p>
					<ul><li><?php esc_html_e( 'Two to four live, catalog-visible products', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'WooCommerce prices, availability, dimensions, and visible attributes', 'lineweb-commerce' ); ?></li><li><?php esc_html_e( 'Responsive table and differences-only view', 'lineweb-commerce' ); ?></li></ul>
				</article>
			</div>
		</section>

		<section class="lineweb-suite-admin__section" aria-labelledby="lineweb-commerce-workflow-title">
			<div class="lineweb-suite-admin__section-heading">
				<h2 id="lineweb-commerce-workflow-title"><?php esc_html_e( 'Your quickest workflow', 'lineweb-commerce' ); ?></h2>
				<p><?php esc_html_e( 'Automatic placements need no page editing. Use Gutenberg only when you want a block in an additional location.', 'lineweb-commerce' ); ?></p>
			</div>
			<div class="lineweb-suite-admin__workflow">
				<div class="lineweb-suite-admin__step"><span>01</span><strong><?php esc_html_e( 'Review placements', 'lineweb-commerce' ); ?></strong><p><?php esc_html_e( 'Confirm the product, Cart, and Mini-Cart integrations.', 'lineweb-commerce' ); ?></p></div>
				<div class="lineweb-suite-admin__step"><span>02</span><strong><?php esc_html_e( 'Check store data', 'lineweb-commerce' ); ?></strong><p><?php esc_html_e( 'Make sure product dimensions and free-shipping rules are complete.', 'lineweb-commerce' ); ?></p></div>
				<div class="lineweb-suite-admin__step"><span>03</span><strong><?php esc_html_e( 'Add manual blocks', 'lineweb-commerce' ); ?></strong><p><?php esc_html_e( 'Use the editor for landing pages, buying guides, or custom product content.', 'lineweb-commerce' ); ?></p></div>
			</div>
			<div class="lineweb-suite-admin__detail-actions">
				<a class="lineweb-suite-admin__button lineweb-suite-admin__button--ink" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'View products', 'lineweb-commerce' ); ?></a>
				<a class="lineweb-suite-admin__button lineweb-suite-admin__button--ink" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Create a landing page', 'lineweb-commerce' ); ?></a>
				<a class="lineweb-suite-admin__button lineweb-suite-admin__button--ink" href="<?php echo esc_url( wc_get_cart_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview Cart', 'lineweb-commerce' ); ?> <span aria-hidden="true">↗</span></a>
			</div>
		</section>

		<footer class="lineweb-suite-admin__section lineweb-suite-admin__support">
			<div class="lineweb-suite-admin__support-brand">
				<img src="<?php echo esc_url( LINEWEB_COMMERCE_URL . 'assets/lineweb-logo.png' ); ?>" alt="" />
			</div>
			<div class="lineweb-suite-admin__support-copy"><h2><?php esc_html_e( 'Built by Lineweb', 'lineweb-commerce' ); ?></h2><p><?php esc_html_e( 'Custom websites, software, and practical automation · Thessaloniki, Greece', 'lineweb-commerce' ); ?></p></div>
			<div class="lineweb-suite-admin__support-links">
				<a href="https://lineweb.gr/" target="_blank" rel="noopener noreferrer">lineweb.gr <span aria-hidden="true">↗</span></a>
				<a href="mailto:info@lineweb.gr">info@lineweb.gr</a>
			</div>
		</footer>
	</div>
	<?php
}
