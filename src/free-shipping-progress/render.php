<?php
/**
 * Server-rendered free-shipping progress with a live Store API enhancement.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

( static function ( $attributes ) {
	$explicit_threshold = (float) lineweb_commerce_number_attribute( $attributes['threshold'] ?? 0, 0, 1000000, 0 );
	$config             = lineweb_commerce_free_shipping_config( $explicit_threshold );
	$is_pending         = false === $config && $explicit_threshold <= 0;

	if ( false === $config && ! $is_pending ) {
		return;
	}

	if ( $is_pending ) {
		$config = array(
			'threshold'        => 0.0,
			'ignore_discounts' => false,
			'source'           => 'woocommerce',
		);
	}

	$threshold       = (float) $config['threshold'];
	$current         = lineweb_commerce_cart_shipping_subtotal( $config['ignore_discounts'] );
	$remaining       = max( 0.0, $threshold - $current );
	$unlocked        = $threshold <= 0 || $current >= $threshold;
	$is_empty        = ! WC()->cart || WC()->cart->is_empty();
	$hide_when_empty = ! empty( $attributes['hideWhenEmpty'] );
	$message         = lineweb_commerce_text_attribute( $attributes['message'] ?? '', 240 );
	$success_message = lineweb_commerce_text_attribute( $attributes['successMessage'] ?? '', 240 );
	$empty_message   = lineweb_commerce_text_attribute( $attributes['emptyMessage'] ?? '', 240 );
	$display_message = $unlocked ? $success_message : str_replace( '{amount}', wp_strip_all_tags( wc_price( $remaining ) ), $message );

	if ( $is_empty ) {
		$display_message = $empty_message;
	}

	$decimals          = wc_get_price_decimals();
	$factor            = 10 ** $decimals;
	$threshold_minor   = (int) round( $threshold * $factor );
	$current_minor     = (int) round( $current * $factor );
	$progress_max      = max( 1, $threshold_minor );
	$progress_now      = min( $progress_max, $current_minor );
	$progress_percent  = $unlocked ? 100 : min( 100, max( 0, ( $current / max( 0.01, $threshold ) ) * 100 ) );
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class'                 => 'lwc-shipping-progress',
			'data-lwc-threshold'    => (string) $threshold_minor,
			'data-lwc-current'      => (string) $current_minor,
			'data-lwc-ignore'       => $config['ignore_discounts'] ? '1' : '0',
			'data-lwc-include-tax'  => WC()->cart && WC()->cart->display_prices_including_tax() ? '1' : '0',
			'data-lwc-message'      => $message,
			'data-lwc-success'      => $success_message,
			'data-lwc-empty'        => $empty_message,
			'data-lwc-hide-empty'   => $hide_when_empty ? '1' : '0',
			'data-lwc-currency'     => get_woocommerce_currency(),
			'data-lwc-minor-unit'   => (string) $decimals,
			'data-lwc-mode'         => $explicit_threshold > 0 ? 'manual' : 'automatic',
			'data-lwc-endpoint'     => esc_url_raw( rest_url( 'wc/store/v1/cart' ) ),
			'data-lwc-source'       => $config['source'],
		)
	);
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $is_pending || ( $is_empty && $hide_when_empty ) ? ' hidden' : ''; ?>>
		<div class="lwc-shipping-progress__copy" aria-live="polite" aria-atomic="true">
			<p class="lwc-shipping-progress__message" data-lwc-status><?php echo esc_html( $display_message ); ?></p>
			<p class="lwc-shipping-progress__values" data-lwc-values><?php echo wp_kses_post( wc_price( $current ) ); ?> / <?php echo wp_kses_post( wc_price( $threshold ) ); ?></p>
		</div>
		<div
			class="lwc-shipping-progress__track"
			role="progressbar"
			aria-label="<?php esc_attr_e( 'Free shipping progress', 'lineweb-commerce' ); ?>"
			aria-valuemin="0"
			aria-valuemax="<?php echo esc_attr( $progress_max ); ?>"
			aria-valuenow="<?php echo esc_attr( $progress_now ); ?>"
		>
			<span style="width: <?php echo esc_attr( $progress_percent ); ?>%"></span>
		</div>
	</section>
	<?php
} )( $attributes );
