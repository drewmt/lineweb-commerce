<?php
// This file is generated. Do not modify it manually.
return array(
	'decision-room' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'lineweb-commerce/decision-room',
		'version' => '0.1.0',
		'title' => 'Decision Room',
		'category' => 'lineweb-commerce',
		'icon' => 'chart-area',
		'description' => 'Explain which live WooCommerce product fits best as shoppers change their priorities.',
		'keywords' => array(
			'woocommerce',
			'recommendation',
			'comparison'
		),
		'textdomain' => 'lineweb-commerce',
		'attributes' => array(
			'eyebrow' => array(
				'type' => 'string',
				'default' => 'DECIDE WITH CONTEXT'
			),
			'heading' => array(
				'type' => 'string',
				'default' => 'Find the product that fits your priorities'
			),
			'description' => array(
				'type' => 'string',
				'default' => 'Change what matters most. The recommendation updates instantly and shows the reasoning behind it.'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'productIds' => array(
				'type' => 'array',
				'default' => array(

				)
			),
			'criteria' => array(
				'type' => 'array',
				'default' => array(
					array(
						'id' => 'value',
						'label' => 'Long-term value',
						'description' => 'Balance the current price against the useful value delivered over time.',
						'weight' => 8,
						'scores' => array(
							76,
							92,
							84
						),
						'evidenceLabel' => 'Merchant assessment — replace before publishing',
						'evidenceUrl' => ''
					),
					array(
						'id' => 'simplicity',
						'label' => 'Ease of use',
						'description' => 'How quickly a buyer can start and keep using the product confidently.',
						'weight' => 7,
						'scores' => array(
							94,
							82,
							66
						),
						'evidenceLabel' => 'Merchant assessment — replace before publishing',
						'evidenceUrl' => ''
					),
					array(
						'id' => 'capability',
						'label' => 'Capability',
						'description' => 'The depth of features available for demanding or future use.',
						'weight' => 9,
						'scores' => array(
							62,
							86,
							97
						),
						'evidenceLabel' => 'Merchant assessment — replace before publishing',
						'evidenceUrl' => ''
					)
				)
			),
			'scenarios' => array(
				'type' => 'array',
				'default' => array(
					array(
						'label' => 'Balanced',
						'weights' => array(
							8,
							7,
							9
						)
					),
					array(
						'label' => 'Simplest start',
						'weights' => array(
							1,
							10,
							0
						)
					),
					array(
						'label' => 'Maximum capability',
						'weights' => array(
							1,
							0,
							10
						)
					)
				)
			),
			'disclaimer' => array(
				'type' => 'string',
				'default' => 'Fit scores are an editorial decision aid, not an objective product certification. Review the criteria, scores, and evidence before publishing.'
			)
		),
		'supports' => array(
			'inserter' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'styles' => array(
			array(
				'name' => 'laboratory',
				'label' => 'Laboratory',
				'isDefault' => true
			),
			array(
				'name' => 'paper',
				'label' => 'Paper'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'delivery-estimate' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'lineweb-commerce/delivery-estimate',
		'version' => '0.6.0',
		'title' => 'Delivery Estimate',
		'category' => 'lineweb-commerce',
		'icon' => 'calendar-alt',
		'description' => 'Show a clear delivery window from stock status, business days, cutoff, and holidays.',
		'keywords' => array(
			'woocommerce',
			'delivery',
			'shipping'
		),
		'textdomain' => 'lineweb-commerce',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'profileSource' => array(
				'type' => 'string',
				'default' => 'local'
			),
			'eyebrow' => array(
				'type' => 'string',
				'default' => 'Delivery estimate'
			),
			'heading' => array(
				'type' => 'string',
				'default' => 'When will it arrive?'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'minimumBusinessDays' => array(
				'type' => 'number',
				'default' => 1
			),
			'maximumBusinessDays' => array(
				'type' => 'number',
				'default' => 3
			),
			'cutoffHour' => array(
				'type' => 'number',
				'default' => 14
			),
			'skipWeekends' => array(
				'type' => 'boolean',
				'default' => true
			),
			'holidayDates' => array(
				'type' => 'string',
				'default' => ''
			),
			'outOfStockMessage' => array(
				'type' => 'string',
				'default' => 'Contact us for availability.'
			),
			'backorderMessage' => array(
				'type' => 'string',
				'default' => 'Available on backorder. Delivery may take longer.'
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'styles' => array(
			array(
				'name' => 'card',
				'label' => 'Card',
				'isDefault' => true
			),
			array(
				'name' => 'inline',
				'label' => 'Inline'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'free-shipping-progress' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'lineweb-commerce/free-shipping-progress',
		'version' => '0.3.0',
		'title' => 'Free Shipping Progress',
		'category' => 'lineweb-commerce',
		'icon' => 'cart',
		'description' => 'Show how much remains until free shipping, using the live WooCommerce cart.',
		'keywords' => array(
			'woocommerce',
			'free shipping',
			'cart'
		),
		'textdomain' => 'lineweb-commerce',
		'attributes' => array(
			'threshold' => array(
				'type' => 'number',
				'default' => 0
			),
			'message' => array(
				'type' => 'string',
				'default' => 'Add {amount} more for free shipping'
			),
			'successMessage' => array(
				'type' => 'string',
				'default' => 'Free shipping unlocked'
			),
			'emptyMessage' => array(
				'type' => 'string',
				'default' => 'Add a product to start tracking free shipping'
			),
			'hideWhenEmpty' => array(
				'type' => 'boolean',
				'default' => true
			),
			'previewCartTotal' => array(
				'type' => 'number',
				'default' => 35
			)
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'styles' => array(
			array(
				'name' => 'bar',
				'label' => 'Bar',
				'isDefault' => true
			),
			array(
				'name' => 'minimal',
				'label' => 'Minimal'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'product-comparison' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'lineweb-commerce/product-comparison',
		'version' => '0.6.0',
		'title' => 'Product Comparison',
		'category' => 'lineweb-commerce',
		'icon' => 'columns',
		'description' => 'Compare two to four published WooCommerce products with live catalog data.',
		'keywords' => array(
			'woocommerce',
			'products',
			'comparison'
		),
		'textdomain' => 'lineweb-commerce',
		'attributes' => array(
			'productIds' => array(
				'type' => 'array',
				'default' => array(

				)
			),
			'eyebrow' => array(
				'type' => 'string',
				'default' => 'Compare products'
			),
			'heading' => array(
				'type' => 'string',
				'default' => 'Choose the right option'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'showAvailability' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showDimensions' => array(
				'type' => 'boolean',
				'default' => true
			),
			'selectedAttributes' => array(
				'type' => 'array',
				'default' => array(

				)
			),
			'differencesLabel' => array(
				'type' => 'string',
				'default' => 'Show differences only'
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'styles' => array(
			array(
				'name' => 'table',
				'label' => 'Table',
				'isDefault' => true
			),
			array(
				'name' => 'compact',
				'label' => 'Compact'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'product-specifications' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'lineweb-commerce/product-specifications',
		'version' => '0.6.0',
		'title' => 'Product Specifications',
		'category' => 'lineweb-commerce',
		'icon' => 'editor-table',
		'description' => 'Show trustworthy WooCommerce product details in a clear specification table.',
		'keywords' => array(
			'woocommerce',
			'specifications',
			'attributes'
		),
		'textdomain' => 'lineweb-commerce',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'eyebrow' => array(
				'type' => 'string',
				'default' => 'Product information'
			),
			'heading' => array(
				'type' => 'string',
				'default' => 'Technical specifications'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'showSku' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showStock' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showWeight' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showDimensions' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'styles' => array(
			array(
				'name' => 'clean',
				'label' => 'Clean',
				'isDefault' => true
			),
			array(
				'name' => 'compact',
				'label' => 'Compact'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
