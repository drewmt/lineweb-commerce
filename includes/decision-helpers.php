<?php
/**
 * Bounded validation and deterministic Decision Room calculations.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize a scalar text attribute and cap its length.
 *
 * @param mixed $value      Raw value.
 * @param int   $max_length Maximum characters.
 * @return string
 */
function lineweb_commerce_text_attribute( $value, $max_length = 120 ) {
	$value = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, $max_length );
	}

	return substr( $value, 0, $max_length );
}

/**
 * Clamp a numeric attribute.
 *
 * @param mixed $value    Raw value.
 * @param float $minimum  Minimum.
 * @param float $maximum  Maximum.
 * @param float $fallback Fallback.
 * @return float
 */
function lineweb_commerce_number_attribute( $value, $minimum, $maximum, $fallback = 0.0 ) {
	if ( ! is_numeric( $value ) ) {
		return $fallback;
	}

	return min( $maximum, max( $minimum, (float) $value ) );
}

/**
 * Keep a public HTTP(S) evidence URL without fetching it.
 *
 * @param mixed $value Raw URL.
 * @return string
 */
function lineweb_commerce_evidence_url( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}

	return esc_url_raw( (string) $value, array( 'http', 'https' ) );
}

/**
 * Normalize at most three unique product IDs.
 *
 * @param mixed $raw_ids Raw ID array.
 * @return array<int, int>
 */
function lineweb_commerce_product_ids( $raw_ids ) {
	if ( ! is_array( $raw_ids ) ) {
		return array();
	}

	$ids = array();

	foreach ( array_slice( $raw_ids, 0, 3 ) as $raw_id ) {
		$id = absint( $raw_id );

		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Normalize the editorial decision criteria.
 *
 * @param mixed $raw_criteria Raw criteria array.
 * @return array<int, array<string, mixed>>
 */
function lineweb_commerce_normalize_criteria( $raw_criteria ) {
	if ( ! is_array( $raw_criteria ) ) {
		return array();
	}

	$criteria = array();

	foreach ( array_slice( $raw_criteria, 0, 6 ) as $index => $raw_criterion ) {
		if ( ! is_array( $raw_criterion ) ) {
			continue;
		}

		$label = lineweb_commerce_text_attribute( $raw_criterion['label'] ?? '', 80 );

		if ( '' === $label ) {
			continue;
		}

		$raw_scores = isset( $raw_criterion['scores'] ) && is_array( $raw_criterion['scores'] ) ? $raw_criterion['scores'] : array();
		$scores     = array();

		for ( $score_index = 0; $score_index < 3; $score_index++ ) {
			$scores[] = lineweb_commerce_number_attribute( $raw_scores[ $score_index ] ?? 0, 0, 100, 0 );
		}

		$criteria[] = array(
			'id'            => sanitize_key( $raw_criterion['id'] ?? 'criterion-' . ( $index + 1 ) ),
			'label'         => $label,
			'description'   => lineweb_commerce_text_attribute( $raw_criterion['description'] ?? '', 240 ),
			'weight'        => lineweb_commerce_number_attribute( $raw_criterion['weight'] ?? 5, 0, 10, 5 ),
			'scores'        => $scores,
			'evidenceLabel' => lineweb_commerce_text_attribute( $raw_criterion['evidenceLabel'] ?? '', 140 ),
			'evidenceUrl'   => lineweb_commerce_evidence_url( $raw_criterion['evidenceUrl'] ?? '' ),
		);
	}

	return $criteria;
}

/**
 * Normalize three named priority scenarios against the current criteria.
 *
 * @param mixed $raw_scenarios  Raw scenario array.
 * @param int   $criterion_count Number of criteria.
 * @return array<int, array<string, mixed>>
 */
function lineweb_commerce_normalize_scenarios( $raw_scenarios, $criterion_count ) {
	if ( ! is_array( $raw_scenarios ) || $criterion_count < 1 ) {
		return array();
	}

	$scenarios = array();

	foreach ( array_slice( $raw_scenarios, 0, 3 ) as $raw_scenario ) {
		if ( ! is_array( $raw_scenario ) ) {
			continue;
		}

		$label = lineweb_commerce_text_attribute( $raw_scenario['label'] ?? '', 60 );

		if ( '' === $label ) {
			continue;
		}

		$raw_weights = isset( $raw_scenario['weights'] ) && is_array( $raw_scenario['weights'] ) ? $raw_scenario['weights'] : array();
		$weights     = array();

		for ( $index = 0; $index < $criterion_count; $index++ ) {
			$weights[] = lineweb_commerce_number_attribute( $raw_weights[ $index ] ?? 5, 0, 10, 5 );
		}

		$scenarios[] = array(
			'label'   => $label,
			'weights' => $weights,
		);
	}

	return $scenarios;
}

/**
 * Calculate the weighted score for one product.
 *
 * @param array $scores  Product scores.
 * @param array $weights Shopper weights.
 * @return float
 */
function lineweb_commerce_calculate_score( $scores, $weights ) {
	$weighted_total = 0.0;
	$weight_total   = 0.0;
	$count          = min( 6, count( $scores ), count( $weights ) );

	for ( $index = 0; $index < $count; $index++ ) {
		$score  = lineweb_commerce_number_attribute( $scores[ $index ], 0, 100, 0 );
		$weight = lineweb_commerce_number_attribute( $weights[ $index ], 0, 10, 0 );

		$weighted_total += $score * $weight;
		$weight_total   += $weight;
	}

	return $weight_total > 0 ? $weighted_total / $weight_total : 0.0;
}

/**
 * Calculate all product scores and the first highest recommendation.
 *
 * @param array $products Product context records containing scores.
 * @param array $weights  Shopper weights.
 * @return array{scores: array<int, float>, recommendedIndex: int}
 */
function lineweb_commerce_calculate_results( $products, $weights ) {
	$scores            = array();
	$recommended_index = 0;

	foreach ( array_slice( $products, 0, 3 ) as $index => $product ) {
		$scores[] = lineweb_commerce_calculate_score( $product['scores'] ?? array(), $weights );

		if ( $scores[ $index ] > $scores[ $recommended_index ] ) {
			$recommended_index = $index;
		}
	}

	return array(
		'scores'            => $scores,
		'recommendedIndex' => $recommended_index,
	);
}

/**
 * Find one strongest criterion and one honest trade-off.
 *
 * @param array $scores   Product scores.
 * @param array $criteria Normalized criteria.
 * @return array{strongest: string, tradeoff: string}
 */
function lineweb_commerce_decision_reasons( $scores, $criteria ) {
	$count = min( count( $scores ), count( $criteria ) );

	if ( $count < 1 ) {
		return array(
			'strongest' => __( 'Not enough reviewed criteria', 'lineweb-commerce' ),
			'tradeoff'  => __( 'Not enough reviewed criteria', 'lineweb-commerce' ),
		);
	}

	$strongest_index = 0;
	$tradeoff_index  = 0;

	for ( $index = 1; $index < $count; $index++ ) {
		if ( $scores[ $index ] > $scores[ $strongest_index ] ) {
			$strongest_index = $index;
		}

		if ( $scores[ $index ] < $scores[ $tradeoff_index ] ) {
			$tradeoff_index = $index;
		}
	}

	return array(
		'strongest' => $criteria[ $strongest_index ]['label'],
		'tradeoff'  => $criteria[ $tradeoff_index ]['label'],
	);
}
