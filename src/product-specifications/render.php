<?php
/**
 * Server-rendered WooCommerce product specifications.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

( static function ( $attributes, $block ) {
	$product = lineweb_commerce_resolve_product( $attributes, $block );

	if ( ! $product ) {
		return;
	}

	$rows = lineweb_commerce_product_specification_rows( $product, $attributes );

	if ( empty( $rows ) ) {
		return;
	}

	$eyebrow           = lineweb_commerce_text_attribute( $attributes['eyebrow'] ?? '', 80 );
	$heading           = lineweb_commerce_text_attribute( $attributes['heading'] ?? '', 180 );
	$heading_tag       = lineweb_commerce_heading_tag( $attributes['headingLevel'] ?? 2 );
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'lwc-specifications' ) );
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-lwc-specifications data-lwc-empty-value="<?php esc_attr_e( 'Not available', 'lineweb-commerce' ); ?>">
		<header class="lwc-specifications__header">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="lwc-specifications__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $heading ) : ?>
				<<?php echo esc_attr( $heading_tag ); ?> class="lwc-specifications__heading"><?php echo esc_html( $heading ); ?></<?php echo esc_attr( $heading_tag ); ?>>
			<?php endif; ?>
		</header>
		<dl class="lwc-specifications__list">
			<?php foreach ( $rows as $row ) : ?>
				<div data-lwc-spec-key="<?php echo esc_attr( $row['key'] ); ?>">
					<dt><?php echo esc_html( $row['label'] ); ?></dt>
					<dd><?php echo esc_html( $row['value'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</section>
	<?php
} )( $attributes, $block );
