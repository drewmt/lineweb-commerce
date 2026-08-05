<?php
/**
 * Shared WooCommerce product, delivery, and shipping helpers.
 *
 * @package Lineweb_Commerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the stock state WooCommerce omits from the classic variation payload.
 *
 * The values are derived from the live variation object and remain namespaced
 * so other plugins can continue using the official payload unchanged.
 *
 * @param array                $data      Variation payload.
 * @param WC_Product_Variable  $product   Parent product.
 * @param WC_Product_Variation $variation Selected variation.
 * @return array
 */
function lineweb_commerce_available_variation_state( $data, $product, $variation ) {
	if ( ! $variation instanceof WC_Product_Variation ) {
		return $data;
	}

	$data['lineweb_commerce'] = array(
		'is_virtual'      => $variation->is_virtual(),
		'is_in_stock'     => $variation->is_in_stock(),
		'is_on_backorder' => $variation->is_on_backorder( 1 ),
	);

	return $data;
}
add_filter( 'woocommerce_available_variation', 'lineweb_commerce_available_variation_state', 10, 3 );

/**
 * Resolve an explicit or contextual WooCommerce product without direct queries.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Current block instance.
 * @return WC_Product|false
 */
function lineweb_commerce_resolve_product( $attributes, $block = null ) {
	$product_id = absint( $attributes['productId'] ?? 0 );

	if ( 0 === $product_id && $block instanceof WP_Block ) {
		$product_id = absint( $block->context['postId'] ?? 0 );
	}

	if ( 0 === $product_id && 'product' === get_post_type( get_the_ID() ) ) {
		$product_id = absint( get_the_ID() );
	}

	$product = $product_id > 0 ? wc_get_product( $product_id ) : false;

	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
		return false;
	}

	return $product;
}

/**
 * Convert a safe numeric heading level to a tag name.
 *
 * @param mixed $level Raw heading level.
 * @return string
 */
function lineweb_commerce_heading_tag( $level ) {
	return 'h' . (int) lineweb_commerce_number_attribute( $level, 2, 4, 2 );
}

/**
 * Resolve visible attribute values for several products with one term query.
 *
 * @param WC_Product[] $products Product objects.
 * @param string[]     $selected_tokens Optional sanitized attribute tokens.
 * @return array{labels: array<string, string>, values: array<int, array<string, string>>}
 */
function lineweb_commerce_batch_visible_attributes( $products, $selected_tokens = array() ) {
	$labels         = array();
	$values         = array();
	$taxonomy_items = array();
	$taxonomies     = array();
	$term_ids       = array();
	$selected_tokens = array_values( array_unique( array_map( 'sanitize_title', $selected_tokens ) ) );

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$product_id            = $product->get_id();
		$values[ $product_id ] = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			$name  = $attribute->get_name();
			$token = sanitize_title( $name );
			if ( ! empty( $selected_tokens ) && ! in_array( $token, $selected_tokens, true ) ) {
				continue;
			}
			$labels[ $token ] = wc_attribute_label( $name, $product );
			if ( ! $attribute->is_taxonomy() ) {
				$values[ $product_id ][ $token ] = implode( ', ', $attribute->get_options() );
				continue;
			}
			$ids = array_values( array_filter( array_map( 'absint', $attribute->get_options() ) ) );
			$taxonomy_items[ $product_id ][ $token ] = array( 'taxonomy' => $name, 'term_ids' => $ids );
			$taxonomies[ $name ] = true;
			$term_ids            = array_merge( $term_ids, $ids );
		}
	}

	$term_names = array();
	if ( ! empty( $term_ids ) && ! empty( $taxonomies ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => array_keys( $taxonomies ),
				'include'    => array_values( array_unique( $term_ids ) ),
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_names[ $term->taxonomy . ':' . $term->term_id ] = $term->name;
			}
		}
	}

	foreach ( $taxonomy_items as $product_id => $items ) {
		foreach ( $items as $token => $item ) {
			$names = array();
			foreach ( $item['term_ids'] as $term_id ) {
				$key = $item['taxonomy'] . ':' . $term_id;
				if ( isset( $term_names[ $key ] ) ) {
					$names[] = $term_names[ $key ];
				}
			}
			$values[ $product_id ][ $token ] = implode( ', ', $names );
		}
	}

	return array( 'labels' => $labels, 'values' => $values );
}

/**
 * Build product specification rows from WooCommerce-owned product data.
 *
 * @param WC_Product $product Product object.
 * @param array      $options Display options.
 * @return array<int, array{key: string, label: string, value: string}>
 */
function lineweb_commerce_product_specification_rows( $product, $options ) {
	$rows      = array();
	$used_keys = array();
	$add_row   = static function ( $key, $label, $value ) use ( &$rows, &$used_keys ) {
		$key   = sanitize_key( $key );
		$label = lineweb_commerce_text_attribute( $label, 100 );
		$value = lineweb_commerce_text_attribute( $value, 300 );

		if ( '' === $key || '' === $label || '' === $value || isset( $used_keys[ $key ] ) ) {
			return;
		}

		$used_keys[ $key ] = true;
		$rows[]             = array(
			'key'   => $key,
			'label' => $label,
			'value' => $value,
		);
	};

	if ( ! empty( $options['showSku'] ) && $product->get_sku() ) {
		$add_row( 'sku', __( 'SKU', 'lineweb-commerce' ), $product->get_sku() );
	}

	if ( ! empty( $options['showStock'] ) ) {
		$stock_label = $product->is_in_stock() ? __( 'In stock', 'lineweb-commerce' ) : __( 'Out of stock', 'lineweb-commerce' );

		if ( $product->managing_stock() && null !== $product->get_stock_quantity() ) {
			$stock_label = sprintf(
				/* translators: %1$s: stock status, %2$d: available quantity. */
				__( '%1$s · %2$d available', 'lineweb-commerce' ),
				$stock_label,
				(int) $product->get_stock_quantity()
			);
		}

		$add_row( 'availability', __( 'Availability', 'lineweb-commerce' ), $stock_label );
	}

	if ( ! empty( $options['showWeight'] ) && $product->has_weight() ) {
		$add_row( 'weight', __( 'Weight', 'lineweb-commerce' ), wc_format_weight( $product->get_weight() ) );
	}

	if ( ! empty( $options['showDimensions'] ) && $product->has_dimensions() ) {
		$add_row( 'dimensions', __( 'Dimensions', 'lineweb-commerce' ), wc_format_dimensions( $product->get_dimensions( false ) ) );
	}

	$attribute_data = lineweb_commerce_batch_visible_attributes( array( $product ) );
	foreach ( $attribute_data['values'][ $product->get_id() ] ?? array() as $token => $value ) {
		$add_row( 'attribute-' . $token, $attribute_data['labels'][ $token ] ?? $token, $value );
	}

	return $rows;
}

/**
 * Normalize a comma-separated list of ISO holiday dates.
 *
 * @param mixed $raw_dates Raw date string.
 * @return array<string, bool>
 */
function lineweb_commerce_holiday_dates( $raw_dates ) {
	if ( ! is_scalar( $raw_dates ) ) {
		return array();
	}

	$dates = array();

	foreach ( array_slice( preg_split( '/[\s,]+/', (string) $raw_dates ), 0, 40 ) as $date ) {
		$date_object = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );

		if ( $date_object && $date_object->format( 'Y-m-d' ) === $date ) {
			$dates[ $date ] = true;
		}
	}

	return $dates;
}

/**
 * Check whether a store-local date can count as a delivery business day.
 *
 * @param DateTimeImmutable   $date          Date to check.
 * @param bool                $skip_weekends Whether weekends are excluded.
 * @param array<string, bool> $holidays      Holiday date set.
 * @return bool
 */
function lineweb_commerce_is_business_day( $date, $skip_weekends, $holidays ) {
	if ( $skip_weekends && (int) $date->format( 'N' ) >= 6 ) {
		return false;
	}

	return ! isset( $holidays[ $date->format( 'Y-m-d' ) ] );
}

/**
 * Add bounded business days to a store-local date.
 *
 * @param DateTimeImmutable   $date          Starting date.
 * @param int                 $days          Business days to add.
 * @param bool                $skip_weekends Whether weekends are excluded.
 * @param array<string, bool> $holidays      Holiday date set.
 * @return DateTimeImmutable
 */
function lineweb_commerce_add_business_days( $date, $days, $skip_weekends, $holidays ) {
	$days = (int) lineweb_commerce_number_attribute( $days, 0, 60, 0 );

	if ( 0 === $days ) {
		while ( ! lineweb_commerce_is_business_day( $date, $skip_weekends, $holidays ) ) {
			$date = $date->modify( '+1 day' );
		}

		return $date;
	}

	while ( $days > 0 ) {
		$date = $date->modify( '+1 day' );

		if ( lineweb_commerce_is_business_day( $date, $skip_weekends, $holidays ) ) {
			--$days;
		}
	}

	return $date;
}

/**
 * Calculate a delivery window in the WordPress store timezone.
 *
 * @param int                    $minimum_days Minimum business days.
 * @param int                    $maximum_days Maximum business days.
 * @param int                    $cutoff_hour  Store-local cutoff hour.
 * @param bool                   $skip_weekends Whether weekends are excluded.
 * @param array<string, bool>    $holidays     Holiday date set.
 * @param DateTimeImmutable|null $now          Optional deterministic clock.
 * @return array{start: DateTimeImmutable, end: DateTimeImmutable}
 */
function lineweb_commerce_delivery_window( $minimum_days, $maximum_days, $cutoff_hour, $skip_weekends, $holidays, $now = null ) {
	$now          = $now instanceof DateTimeImmutable ? $now->setTimezone( wp_timezone() ) : new DateTimeImmutable( 'now', wp_timezone() );
	$minimum_days = (int) lineweb_commerce_number_attribute( $minimum_days, 0, 30, 1 );
	$maximum_days = (int) lineweb_commerce_number_attribute( $maximum_days, $minimum_days, 60, max( 3, $minimum_days ) );
	$cutoff_hour  = (int) lineweb_commerce_number_attribute( $cutoff_hour, 0, 23, 14 );
	$start_date   = $now->setTime( 0, 0 );
	$after_cutoff = lineweb_commerce_is_business_day( $start_date, $skip_weekends, $holidays ) && (int) $now->format( 'G' ) >= $cutoff_hour;
	$extra_day    = $after_cutoff ? 1 : 0;

	return array(
		'start' => lineweb_commerce_add_business_days( $start_date, $minimum_days + $extra_day, $skip_weekends, $holidays ),
		'end'   => lineweb_commerce_add_business_days( $start_date, $maximum_days + $extra_day, $skip_weekends, $holidays ),
	);
}

/**
 * Normalize the merchant's delivery working-day selection.
 *
 * ISO-8601 weekday numbers are used: Monday is 1 and Sunday is 7.
 *
 * @param mixed $raw_days Stored WooCommerce setting.
 * @return int[]
 */
function lineweb_commerce_working_days( $raw_days ) {
	$days = is_array( $raw_days ) ? $raw_days : preg_split( '/[\s,]+/', (string) $raw_days );
	$days = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $days ),
				static function ( $day ) {
					return $day >= 1 && $day <= 7;
				}
			)
		)
	);

	return empty( $days ) ? array( 1, 2, 3, 4, 5 ) : $days;
}

/**
 * Return the bounded global delivery profile.
 *
 * The profile is inert until a merchant explicitly confirms it. Defaults are
 * editor guidance only and never become an automatic storefront promise.
 *
 * @return array<string, mixed>
 */
function lineweb_commerce_delivery_profile() {
	$minimum = (int) lineweb_commerce_number_attribute( get_option( 'lineweb_commerce_delivery_min_days', 1 ), 0, 30, 1 );

	return array(
		'confirmed'         => 'yes' === get_option( 'lineweb_commerce_delivery_profile_confirmed', 'no' ),
		'minimum_days'      => $minimum,
		'maximum_days'      => (int) lineweb_commerce_number_attribute( get_option( 'lineweb_commerce_delivery_max_days', 3 ), $minimum, 60, max( 3, $minimum ) ),
		'cutoff_hour'       => (int) lineweb_commerce_number_attribute( get_option( 'lineweb_commerce_delivery_cutoff_hour', 14 ), 0, 23, 14 ),
		'working_days'      => lineweb_commerce_working_days( get_option( 'lineweb_commerce_delivery_working_days', array( 1, 2, 3, 4, 5 ) ) ),
		'holidays'          => lineweb_commerce_holiday_dates( get_option( 'lineweb_commerce_delivery_holidays', '' ) ),
		'backorder_mode'    => in_array( get_option( 'lineweb_commerce_delivery_backorder_mode', 'message' ), array( 'message', 'window', 'hide' ), true ) ? get_option( 'lineweb_commerce_delivery_backorder_mode', 'message' ) : 'message',
		'backorder_extra'   => (int) lineweb_commerce_number_attribute( get_option( 'lineweb_commerce_delivery_backorder_extra_days', 3 ), 0, 30, 3 ),
		'out_of_stock_mode' => 'hide' === get_option( 'lineweb_commerce_delivery_out_of_stock_mode', 'message' ) ? 'hide' : 'message',
		'virtual_mode'      => 'hide',
	);
}

/**
 * Calculate a delivery window against explicit ISO working days.
 *
 * @param array<string, mixed> $profile Merchant delivery profile.
 * @param DateTimeImmutable|null $now Optional deterministic clock.
 * @return array{start: DateTimeImmutable, end: DateTimeImmutable}
 */
function lineweb_commerce_profile_delivery_window( $profile, $now = null ) {
	$now          = $now instanceof DateTimeImmutable ? $now->setTimezone( wp_timezone() ) : new DateTimeImmutable( 'now', wp_timezone() );
	$working_days = lineweb_commerce_working_days( $profile['working_days'] ?? array() );
	$holidays     = is_array( $profile['holidays'] ?? null ) ? $profile['holidays'] : lineweb_commerce_holiday_dates( $profile['holidays'] ?? '' );
	$minimum      = (int) lineweb_commerce_number_attribute( $profile['minimum_days'] ?? 1, 0, 30, 1 );
	$maximum      = (int) lineweb_commerce_number_attribute( $profile['maximum_days'] ?? 3, $minimum, 60, max( 3, $minimum ) );
	$cutoff       = (int) lineweb_commerce_number_attribute( $profile['cutoff_hour'] ?? 14, 0, 23, 14 );
	$start_date   = $now->setTime( 0, 0 );
	$is_workday   = static function ( $date ) use ( $working_days, $holidays ) {
		return in_array( (int) $date->format( 'N' ), $working_days, true ) && ! isset( $holidays[ $date->format( 'Y-m-d' ) ] );
	};
	$add_days     = static function ( $date, $days ) use ( $is_workday ) {
		$guard = 0;
		while ( $days > 0 && $guard < 400 ) {
			$date = $date->modify( '+1 day' );
			if ( $is_workday( $date ) ) {
				--$days;
			}
			++$guard;
		}
		while ( ! $is_workday( $date ) && $guard < 400 ) {
			$date = $date->modify( '+1 day' );
			++$guard;
		}
		return $date;
	};
	$extra_day    = $is_workday( $start_date ) && (int) $now->format( 'G' ) >= $cutoff ? 1 : 0;

	return array(
		'start' => $add_days( $start_date, $minimum + $extra_day ),
		'end'   => $add_days( $start_date, $maximum + $extra_day ),
	);
}

/**
 * Resolve either the confirmed global profile or a manual block override.
 *
 * @param array $attributes Block attributes.
 * @param bool  $automatic Whether the block was automatically placed.
 * @return array<string, mixed>|false
 */
function lineweb_commerce_resolve_delivery_profile( $attributes, $automatic = false ) {
	$source = $automatic ? 'global' : ( $attributes['profileSource'] ?? 'local' );

	if ( 'global' === $source ) {
		$profile = lineweb_commerce_delivery_profile();
		return $profile['confirmed'] ? $profile : false;
	}

	return array(
		'confirmed'         => true,
		'minimum_days'      => $attributes['minimumBusinessDays'] ?? 1,
		'maximum_days'      => $attributes['maximumBusinessDays'] ?? 3,
		'cutoff_hour'       => $attributes['cutoffHour'] ?? 14,
		'working_days'      => ! empty( $attributes['skipWeekends'] ) ? array( 1, 2, 3, 4, 5 ) : array( 1, 2, 3, 4, 5, 6, 7 ),
		'holidays'          => lineweb_commerce_holiday_dates( $attributes['holidayDates'] ?? '' ),
		'backorder_mode'    => 'message',
		'backorder_extra'   => 0,
		'out_of_stock_mode' => 'message',
		'virtual_mode'      => 'hide',
	);
}

/**
 * Check whether the current cart has a valid free-shipping coupon.
 *
 * @return bool
 */
function lineweb_commerce_has_free_shipping_coupon() {
	if ( ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_coupons() as $coupon ) {
		if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
			return true;
		}
	}

	return false;
}

/**
 * Find the numeric free-shipping goal matching the current shipping packages.
 *
 * @param float $explicit_threshold Optional merchant-entered threshold.
 * @return array{threshold: float, ignore_discounts: bool, source: string}|false
 */
function lineweb_commerce_free_shipping_config( $explicit_threshold = 0.0 ) {
	$explicit_threshold = (float) lineweb_commerce_number_attribute( $explicit_threshold, 0, 1000000, 0 );

	if ( $explicit_threshold > 0 ) {
		return array(
			'threshold'        => $explicit_threshold,
			'ignore_discounts' => false,
			'source'           => 'manual',
		);
	}

	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return false;
	}

	$has_coupon    = lineweb_commerce_has_free_shipping_coupon();
	$package_goals = array();

	foreach ( WC()->cart->get_shipping_packages() as $package ) {
		$zone       = WC_Shipping_Zones::get_zone_matching_package( $package );
		$candidates = array();

		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! $method instanceof WC_Shipping_Free_Shipping ) {
				continue;
			}

			$requires         = (string) $method->get_option( 'requires', '' );
			$minimum_amount   = (float) $method->get_option( 'min_amount', 0 );
			$ignore_discounts = 'yes' === $method->get_option( 'ignore_discounts', 'no' );

			if ( '' === $requires || ( 'coupon' === $requires && $has_coupon ) || ( 'either' === $requires && $has_coupon ) ) {
				$candidates[] = array(
					'threshold'        => 0.0,
					'ignore_discounts' => $ignore_discounts,
				);
				continue;
			}

			if ( 'both' === $requires && ! $has_coupon ) {
				continue;
			}

			if ( in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) && $minimum_amount > 0 ) {
				$candidates[] = array(
					'threshold'        => $minimum_amount,
					'ignore_discounts' => $ignore_discounts,
				);
			}
		}

		if ( empty( $candidates ) ) {
			return false;
		}

		usort(
			$candidates,
			static function ( $first, $second ) {
				return $first['threshold'] <=> $second['threshold'];
			}
		);
		$package_goals[] = $candidates[0];
	}

	if ( empty( $package_goals ) ) {
		return false;
	}

	$discount_policies = array_unique(
		array_map(
			static function ( $goal ) {
				return $goal['ignore_discounts'] ? 'ignore' : 'apply';
			},
			array_filter(
				$package_goals,
				static function ( $goal ) {
					return $goal['threshold'] > 0;
				}
			)
		)
	);

	// A single progress bar cannot truthfully represent mixed package policies.
	if ( count( $discount_policies ) > 1 ) {
		return false;
	}

	usort(
		$package_goals,
		static function ( $first, $second ) {
			return $second['threshold'] <=> $first['threshold'];
		}
	);

	return array(
		'threshold'        => (float) $package_goals[0]['threshold'],
		'ignore_discounts' => (bool) $package_goals[0]['ignore_discounts'],
		'source'           => 'woocommerce',
	);
}

/**
 * Match WooCommerce core's displayed subtotal calculation for free shipping.
 *
 * @param bool $ignore_discounts Whether discounts should be ignored.
 * @return float
 */
function lineweb_commerce_cart_shipping_subtotal( $ignore_discounts ) {
	if ( ! WC()->cart ) {
		return 0.0;
	}

	$total = (float) WC()->cart->get_displayed_subtotal();

	if ( ! $ignore_discounts ) {
		$total -= (float) WC()->cart->get_discount_total();

		if ( WC()->cart->display_prices_including_tax() ) {
			$total -= (float) WC()->cart->get_discount_tax();
		}
	}

	return max( 0.0, round( $total, wc_get_price_decimals() ) );
}

/**
 * Add the current automatic free-shipping rule to the Cart Store API response.
 *
 * @return array{available: bool, threshold_minor: int, ignore_discounts: bool, include_tax: bool}
 */
function lineweb_commerce_cart_extension_data() {
	$config = lineweb_commerce_free_shipping_config();

	if ( false === $config ) {
		return array(
			'available'        => false,
			'threshold_minor'  => 0,
			'ignore_discounts' => false,
			'include_tax'      => WC()->cart && WC()->cart->display_prices_including_tax(),
		);
	}

	return array(
		'available'        => true,
		'threshold_minor'  => (int) round( $config['threshold'] * ( 10 ** wc_get_price_decimals() ) ),
		'ignore_discounts' => (bool) $config['ignore_discounts'],
		'include_tax'      => WC()->cart && WC()->cart->display_prices_including_tax(),
	);
}

/**
 * Define the public Cart Store API extension schema.
 *
 * @return array<string, array<string, mixed>>
 */
function lineweb_commerce_cart_extension_schema() {
	return array(
		'available'        => array(
			'description' => __( 'Whether one free-shipping goal can be represented for the current cart.', 'lineweb-commerce' ),
			'type'        => 'boolean',
			'readonly'    => true,
		),
		'threshold_minor'  => array(
			'description' => __( 'The matching free-shipping threshold in the store currency minor unit.', 'lineweb-commerce' ),
			'type'        => 'integer',
			'readonly'    => true,
		),
		'ignore_discounts' => array(
			'description' => __( 'Whether the free-shipping method evaluates the subtotal before discounts.', 'lineweb-commerce' ),
			'type'        => 'boolean',
			'readonly'    => true,
		),
		'include_tax'      => array(
			'description' => __( 'Whether displayed product subtotals include tax.', 'lineweb-commerce' ),
			'type'        => 'boolean',
			'readonly'    => true,
		),
	);
}
