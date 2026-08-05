<?php
/**
 * Idempotently create the local practical-block showcase page.
 *
 * Run only in wp-env after seed-demo.php.
 */

defined( 'ABSPATH' ) || exit;

if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'The demo page creator is restricted to local/development WordPress environments.' );
}

$skus        = array( 'LWC-DEMO-START', 'LWC-DEMO-GROW', 'LWC-DEMO-SCALE' );
$product_ids = array_map( 'wc_get_product_id_by_sku', $skus );

if ( in_array( 0, $product_ids, true ) ) {
	WP_CLI::error( 'Run the practical-block product seeder first.' );
}

$content = serialize_blocks(
	array(
		array(
			'blockName'    => 'core/heading',
			'attrs'        => array( 'level' => 1 ),
			'innerBlocks'  => array(),
			'innerHTML'    => '<h1 class="wp-block-heading">Useful WooCommerce blocks</h1>',
			'innerContent' => array( '<h1 class="wp-block-heading">Useful WooCommerce blocks</h1>' ),
		),
		array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '<p>Real product data, a practical delivery window, and a live free-shipping goal.</p>',
			'innerContent' => array( '<p>Real product data, a practical delivery window, and a live free-shipping goal.</p>' ),
		),
		array(
			'blockName'    => 'lineweb-commerce/product-specifications',
			'attrs'        => array(
				'align'     => 'wide',
				'productId' => $product_ids[0],
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'lineweb-commerce/delivery-estimate',
			'attrs'        => array(
				'align'     => 'wide',
				'productId' => $product_ids[0],
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'core/heading',
			'attrs'        => array( 'level' => 2 ),
			'innerBlocks'  => array(),
			'innerHTML'    => '<h2 class="wp-block-heading">Free shipping progress</h2>',
			'innerContent' => array( '<h2 class="wp-block-heading">Free shipping progress</h2>' ),
		),
		array(
			'blockName'    => 'lineweb-commerce/free-shipping-progress',
			'attrs'        => array(
				'align'         => 'wide',
				'threshold'     => 200,
				'hideWhenEmpty' => false,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	)
);
$existing = get_page_by_path( 'lineweb-commerce-practical-blocks-demo', OBJECT, 'page' );
$post     = array(
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'post_title'     => 'Lineweb Commerce — Practical Blocks',
	'post_name'      => 'lineweb-commerce-practical-blocks-demo',
	'post_content'   => $content,
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
);

if ( $existing ) {
	$post['ID'] = $existing->ID;
}

$post_id = wp_insert_post( $post, true );

if ( is_wp_error( $post_id ) ) {
	WP_CLI::error( $post_id->get_error_message() );
}

update_post_meta( $post_id, '_wp_page_template', 'page-no-title' );

WP_CLI::success( 'Practical blocks demo: ' . get_permalink( $post_id ) );
