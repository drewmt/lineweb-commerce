<?php
/**
 * Server-rendered live WooCommerce product comparison.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

( static function ( $attributes ) {
	$product_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $attributes['productIds'] ?? array() ) ) ) ) );
	$product_ids = array_slice( $product_ids, 0, 4 );

	if ( count( $product_ids ) < 2 ) {
		return;
	}

	$fetched = wc_get_products(
		array(
			'include'    => $product_ids,
			'limit'      => 4,
			'status'     => 'publish',
			'visibility' => 'visible',
		)
	);
	$by_id   = array();
	foreach ( $fetched as $product ) {
		if ( $product instanceof WC_Product && $product->is_visible() ) {
			$by_id[ $product->get_id() ] = $product;
		}
	}
	$products = array_values(
		array_filter(
			array_map(
				static function ( $product_id ) use ( $by_id ) {
					return $by_id[ $product_id ] ?? null;
				},
				$product_ids
			)
		)
	);

	if ( count( $products ) < 2 ) {
		return;
	}

	$selected_attributes = array_slice( array_values( array_unique( array_map( 'sanitize_title', (array) ( $attributes['selectedAttributes'] ?? array() ) ) ) ), 0, 12 );
	$attribute_data       = lineweb_commerce_batch_visible_attributes( $products, $selected_attributes );
	$product_attributes   = $attribute_data['values'];
	$attribute_labels     = $attribute_data['labels'];

	$rows = array(
		array(
			'label'  => __( 'Price', 'lineweb-commerce' ),
			'values' => array_map(
				static function ( $product ) {
					return $product->get_price_html();
				},
				$products
			),
		),
	);
	if ( ! empty( $attributes['showAvailability'] ) ) {
		$rows[] = array(
			'label'  => __( 'Availability', 'lineweb-commerce' ),
			'values' => array_map(
				static function ( $product ) {
					return esc_html( $product->is_in_stock() ? __( 'In stock', 'lineweb-commerce' ) : __( 'Out of stock', 'lineweb-commerce' ) );
				},
				$products
			),
		);
	}
	if ( ! empty( $attributes['showDimensions'] ) ) {
		$rows[] = array(
			'label'  => __( 'Dimensions', 'lineweb-commerce' ),
			'values' => array_map(
				static function ( $product ) {
					return $product->has_dimensions() ? esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ) : '';
				},
				$products
			),
		);
	}
	foreach ( $selected_attributes as $token ) {
		$rows[] = array(
			'label'  => $attribute_labels[ $token ] ?? $token,
			'values' => array_map(
				static function ( $product ) use ( $product_attributes, $token ) {
					return esc_html( $product_attributes[ $product->get_id() ][ $token ] ?? '' );
				},
				$products
			),
		);
	}

	$eyebrow     = lineweb_commerce_text_attribute( $attributes['eyebrow'] ?? '', 80 );
	$heading     = lineweb_commerce_text_attribute( $attributes['heading'] ?? '', 180 );
	$heading_tag = lineweb_commerce_heading_tag( $attributes['headingLevel'] ?? 2 );
	$toggle      = lineweb_commerce_text_attribute( $attributes['differencesLabel'] ?? '', 100 );
	$wrapper     = get_block_wrapper_attributes( array( 'class' => 'lwc-comparison' ) );
	?>
	<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-lwc-comparison>
		<header class="lwc-comparison__header">
			<div>
				<?php if ( '' !== $eyebrow ) : ?><p class="lwc-comparison__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( '' !== $heading ) : ?><<?php echo esc_attr( $heading_tag ); ?> class="lwc-comparison__heading"><?php echo esc_html( $heading ); ?></<?php echo esc_attr( $heading_tag ); ?>><?php endif; ?>
			</div>
			<?php if ( '' !== $toggle ) : ?><button type="button" class="lwc-comparison__toggle" data-lwc-comparison-toggle aria-pressed="false"><?php echo esc_html( $toggle ); ?></button><?php endif; ?>
		</header>
		<p class="lwc-comparison__scroll-hint"><?php esc_html_e( 'Scroll sideways to compare every product.', 'lineweb-commerce' ); ?></p>
		<div class="lwc-comparison__scroller" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Product comparison table', 'lineweb-commerce' ); ?>">
			<table>
				<thead><tr><th scope="col"><?php esc_html_e( 'Feature', 'lineweb-commerce' ); ?></th>
				<?php foreach ( $products as $product ) : ?>
					<th scope="col"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ); ?><span><?php echo esc_html( $product->get_name() ); ?></span></a></th>
				<?php endforeach; ?></tr></thead>
				<tbody>
				<?php foreach ( $rows as $row ) :
					$normalized = array_map( static function ( $value ) { return trim( wp_strip_all_tags( html_entity_decode( (string) $value ) ) ); }, $row['values'] );
					$different  = count( array_unique( $normalized ) ) > 1;
					?>
					<tr data-lwc-comparison-row data-different="<?php echo $different ? '1' : '0'; ?>"><th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
					<?php foreach ( $row['values'] as $value ) : ?><td><?php echo '' !== $value ? wp_kses_post( $value ) : '<span aria-label="' . esc_attr__( 'Not available', 'lineweb-commerce' ) . '">—</span>'; ?></td><?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
	<?php
} )( $attributes );
