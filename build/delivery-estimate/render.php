<?php
/**
 * Server-rendered WooCommerce delivery estimate.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

( static function ( $attributes, $block ) {
	$product = lineweb_commerce_resolve_product( $attributes, $block );
	$is_auto = false !== strpos( (string) ( $attributes['className'] ?? '' ), 'lwc-auto-placement' );
	$profile = lineweb_commerce_resolve_delivery_profile( $attributes, $is_auto );

	if ( ! $product || ! $profile || $product->is_virtual() || $product->is_downloadable() ) {
		return;
	}

	$eyebrow     = lineweb_commerce_text_attribute( $attributes['eyebrow'] ?? '', 80 );
	$heading     = lineweb_commerce_text_attribute( $attributes['heading'] ?? '', 180 );
	$heading_tag = lineweb_commerce_heading_tag( $attributes['headingLevel'] ?? 2 );
	$message     = '';
	$window      = null;

	if ( ! $product->is_in_stock() && 'hide' === $profile['out_of_stock_mode'] ) {
		return;
	}

	if ( ! $product->is_in_stock() ) {
		$message = lineweb_commerce_text_attribute( $attributes['outOfStockMessage'] ?? '', 240 );
	} elseif ( $product->is_on_backorder() ) {
		if ( 'hide' === $profile['backorder_mode'] ) {
			return;
		}
		if ( 'window' === $profile['backorder_mode'] ) {
			$profile['minimum_days'] += $profile['backorder_extra'];
			$profile['maximum_days'] += $profile['backorder_extra'];
			$window = lineweb_commerce_profile_delivery_window( $profile );
		} else {
			$message = lineweb_commerce_text_attribute( $attributes['backorderMessage'] ?? '', 240 );
		}
	} else {
		$window = lineweb_commerce_profile_delivery_window( $profile );
	}

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'lwc-delivery' ) );
	$date_format        = get_option( 'date_format' );
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-lwc-delivery data-lwc-out-of-stock="<?php echo esc_attr( $attributes['outOfStockMessage'] ?? '' ); ?>" data-lwc-backorder="<?php echo esc_attr( $attributes['backorderMessage'] ?? '' ); ?>">
		<div class="lwc-delivery__icon" aria-hidden="true">24</div>
		<div class="lwc-delivery__content">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="lwc-delivery__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $heading ) : ?>
				<<?php echo esc_attr( $heading_tag ); ?> class="lwc-delivery__heading"><?php echo esc_html( $heading ); ?></<?php echo esc_attr( $heading_tag ); ?>>
			<?php endif; ?>
			<?php if ( $window ) : ?>
				<p class="lwc-delivery__window">
					<time datetime="<?php echo esc_attr( $window['start']->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( $date_format, $window['start']->getTimestamp(), wp_timezone() ) ); ?></time>
					<span aria-hidden="true">–</span>
					<span class="screen-reader-text"><?php esc_html_e( 'to', 'lineweb-commerce' ); ?></span>
					<time datetime="<?php echo esc_attr( $window['end']->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( $date_format, $window['end']->getTimestamp(), wp_timezone() ) ); ?></time>
				</p>
			<?php elseif ( '' !== $message ) : ?>
				<p class="lwc-delivery__window"><?php echo esc_html( $message ); ?></p>
			<?php endif; ?>
			<p class="lwc-delivery__note"><?php esc_html_e( 'Estimate based on current availability and store business days.', 'lineweb-commerce' ); ?></p>
		</div>
	</section>
	<?php
} )( $attributes, $block );
