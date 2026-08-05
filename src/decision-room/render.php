<?php
/**
 * Server-rendered explainable WooCommerce Decision Room.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

( static function ( $attributes ) {
	$eyebrow       = lineweb_commerce_text_attribute( $attributes['eyebrow'] ?? '', 80 );
	$heading       = lineweb_commerce_text_attribute( $attributes['heading'] ?? '', 180 );
	$description   = lineweb_commerce_text_attribute( $attributes['description'] ?? '', 320 );
	$disclaimer    = lineweb_commerce_text_attribute( $attributes['disclaimer'] ?? '', 360 );
	$heading_level = (int) lineweb_commerce_number_attribute( $attributes['headingLevel'] ?? 2, 2, 4, 2 );
	$heading_tag   = 'h' . $heading_level;
	$product_tag   = 'h' . ( $heading_level + 1 );
	$product_ids   = lineweb_commerce_product_ids( $attributes['productIds'] ?? array() );
	$criteria      = lineweb_commerce_normalize_criteria( $attributes['criteria'] ?? array() );
	$scenarios     = lineweb_commerce_normalize_scenarios( $attributes['scenarios'] ?? array(), count( $criteria ) );
	$products      = array();

	foreach ( $product_ids as $slot_index => $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			continue;
		}

		$product_scores = array();

		foreach ( $criteria as $criterion ) {
			$product_scores[] = $criterion['scores'][ $slot_index ] ?? 0;
		}

		$products[] = array(
			'product' => $product,
			'name'    => $product->get_name(),
			'scores'  => $product_scores,
		);
	}

	$weights = array_map(
		static function ( $criterion ) {
			return $criterion['weight'];
		},
		$criteria
	);

	if ( ! empty( $scenarios ) ) {
		$weights = $scenarios[0]['weights'];
	}

	$results            = lineweb_commerce_calculate_results( $products, $weights );
	$recommended_index  = $results['recommendedIndex'];
	$recommended_name   = $products[ $recommended_index ]['name'] ?? __( 'No recommendation yet', 'lineweb-commerce' );
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'lwc-decision-room' ) );
	$public_products    = array_map(
		static function ( $product ) {
			return array(
				'name'   => $product['name'],
				'scores' => $product['scores'],
			);
		},
		$products
	);
	?>

<section
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="linewebCommerceDecision"
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core returns an escaped data-wp-context attribute.
	echo wp_interactivity_data_wp_context(
		array(
			'weights'        => $weights,
			'products'       => $public_products,
			'scenarios'      => $scenarios,
			'activeScenario' => 0,
			'customLabel'    => __( 'Custom priorities', 'lineweb-commerce' ),
		)
	);
	?>
>
	<div class="lwc-decision__header">
		<div>
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="lwc-decision__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<<?php echo esc_attr( $heading_tag ); ?> class="lwc-decision__heading"><?php echo esc_html( $heading ); ?></<?php echo esc_attr( $heading_tag ); ?>>
		</div>
		<?php if ( '' !== $description ) : ?>
			<p class="lwc-decision__description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( count( $products ) < 2 || empty( $criteria ) ) : ?>
		<div class="lwc-decision__empty" role="status">
			<strong><?php esc_html_e( 'Decision Room needs configuration.', 'lineweb-commerce' ); ?></strong>
			<p><?php esc_html_e( 'Choose at least two visible WooCommerce products and one reviewed criterion in the block editor.', 'lineweb-commerce' ); ?></p>
		</div>
	<?php else : ?>
		<div class="lwc-decision__summary" aria-live="polite" aria-atomic="true">
			<span><?php esc_html_e( 'Current top fit', 'lineweb-commerce' ); ?></span>
			<strong data-wp-text="state.recommendedName"><?php echo esc_html( $recommended_name ); ?></strong>
			<small><?php esc_html_e( 'Based on the visible priorities below', 'lineweb-commerce' ); ?></small>
		</div>

		<?php if ( ! empty( $scenarios ) ) : ?>
			<div class="lwc-decision__scenarios" aria-label="<?php esc_attr_e( 'Priority scenarios', 'lineweb-commerce' ); ?>">
				<span><?php esc_html_e( 'Start with a scenario', 'lineweb-commerce' ); ?></span>
				<div>
					<?php foreach ( $scenarios as $scenario_index => $scenario ) : ?>
						<button
							type="button"
							aria-pressed="<?php echo 0 === $scenario_index ? 'true' : 'false'; ?>"
							data-wp-on--click="actions.applyScenario"
							data-wp-bind--aria-pressed="state.isScenarioActive"
							data-wp-class--is-active="state.isScenarioActive"
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core returns an escaped data-wp-context attribute.
							echo wp_interactivity_data_wp_context( array( 'scenarioIndex' => $scenario_index ) );
							?>
						>
							<?php echo esc_html( $scenario['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="lwc-decision__priorities">
			<div class="lwc-decision__section-heading">
				<span>01</span>
				<div>
					<strong><?php esc_html_e( 'Set your priorities', 'lineweb-commerce' ); ?></strong>
					<p><?php esc_html_e( 'Nothing is hidden: every weight changes the recommendation.', 'lineweb-commerce' ); ?></p>
				</div>
			</div>

			<div class="lwc-decision__criteria">
				<?php foreach ( $criteria as $criterion_index => $criterion ) : ?>
					<label
						class="lwc-decision__criterion"
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core returns an escaped data-wp-context attribute.
						echo wp_interactivity_data_wp_context( array( 'criterionIndex' => $criterion_index ) );
						?>
					>
						<span class="lwc-decision__criterion-copy">
							<strong><?php echo esc_html( $criterion['label'] ); ?></strong>
							<small><?php echo esc_html( $criterion['description'] ); ?></small>
						</span>
						<span class="lwc-decision__criterion-control">
							<output data-wp-text="state.currentWeightText"><?php echo esc_html( $weights[ $criterion_index ] . '/10' ); ?></output>
							<input
								type="range"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: criterion label. */ __( '%s priority weight', 'lineweb-commerce' ), $criterion['label'] ) ); ?>"
								min="0"
								max="10"
								step="1"
								value="<?php echo esc_attr( $weights[ $criterion_index ] ); ?>"
								data-wp-on--input="actions.updateWeight"
								data-wp-bind--value="state.currentWeight"
							>
						</span>
						<?php if ( '' !== $criterion['evidenceLabel'] ) : ?>
							<span class="lwc-decision__evidence">
								<?php esc_html_e( 'Basis:', 'lineweb-commerce' ); ?>
								<?php if ( '' !== $criterion['evidenceUrl'] ) : ?>
									<a href="<?php echo esc_url( $criterion['evidenceUrl'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $criterion['evidenceLabel'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $criterion['evidenceLabel'] ); ?>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="lwc-decision__results">
			<div class="lwc-decision__section-heading">
				<span>02</span>
				<div>
					<strong><?php esc_html_e( 'See the reasoning', 'lineweb-commerce' ); ?></strong>
					<p><?php esc_html_e( 'Current WooCommerce price and availability stay separate from editorial fit.', 'lineweb-commerce' ); ?></p>
				</div>
			</div>

			<div class="lwc-decision__products">
				<?php foreach ( $products as $product_index => $product_record ) : ?>
					<?php
					$product = $product_record['product'];
					$reasons = lineweb_commerce_decision_reasons( $product_record['scores'], $criteria );
					$is_recommended = $product_index === $recommended_index;
					?>
					<article
						class="lwc-decision__product<?php echo $is_recommended ? ' is-recommended' : ''; ?>"
						data-wp-class--is-recommended="state.isRecommended"
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core returns an escaped data-wp-context attribute.
						echo wp_interactivity_data_wp_context( array( 'productIndex' => $product_index ) );
						?>
					>
						<div class="lwc-decision__product-image">
							<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
							<span data-wp-bind--hidden="state.isNotRecommended" <?php if ( ! $is_recommended ) : ?>hidden<?php endif; ?>><?php esc_html_e( 'Top fit', 'lineweb-commerce' ); ?></span>
						</div>
						<div class="lwc-decision__product-body">
							<div class="lwc-decision__score">
								<strong data-wp-text="state.currentProductScore"><?php echo esc_html( round( $results['scores'][ $product_index ] ) . '%' ); ?></strong>
								<span><?php esc_html_e( 'priority fit', 'lineweb-commerce' ); ?></span>
							</div>
							<<?php echo esc_attr( $product_tag ); ?>><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></<?php echo esc_attr( $product_tag ); ?>>
							<p class="lwc-decision__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
							<p class="lwc-decision__stock <?php echo $product->is_in_stock() ? 'is-in-stock' : 'is-out-of-stock'; ?>">
								<?php echo esc_html( $product->is_in_stock() ? __( 'Available', 'lineweb-commerce' ) : __( 'Currently unavailable', 'lineweb-commerce' ) ); ?>
							</p>
							<dl>
								<div><dt><?php esc_html_e( 'Strongest fit', 'lineweb-commerce' ); ?></dt><dd><?php echo esc_html( $reasons['strongest'] ); ?></dd></div>
								<div><dt><?php esc_html_e( 'Main trade-off', 'lineweb-commerce' ); ?></dt><dd><?php echo esc_html( $reasons['tradeoff'] ); ?></dd></div>
							</dl>
							<a class="lwc-decision__product-link" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'Review product', 'lineweb-commerce' ); ?></a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $disclaimer ) : ?>
		<p class="lwc-decision__disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
	<?php endif; ?>
</section>
	<?php
} )( $attributes );
