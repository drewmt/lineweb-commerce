import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	ComboboxControl,
	Notice,
	PanelBody,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useProductCatalog } from '../shared/product-catalog';

function previewRows( product, attributes ) {
	if ( ! product ) {
		return [];
	}

	const rows = [];
	const add = ( label, value ) => {
		if ( value ) {
			rows.push( { label, value } );
		}
	};

	if ( attributes.showSku ) {
		add( __( 'SKU', 'lineweb-commerce' ), product.sku );
	}

	if ( attributes.showStock ) {
		add(
			__( 'Availability', 'lineweb-commerce' ),
			product.is_in_stock
				? __( 'In stock', 'lineweb-commerce' )
				: __( 'Out of stock', 'lineweb-commerce' )
		);
	}

	if ( attributes.showWeight && product.weight ) {
		const weight = product.weight.value
			? `${ product.weight.value } ${ product.weight.unit || '' }`
			: product.weight;
		add( __( 'Weight', 'lineweb-commerce' ), weight );
	}

	if ( attributes.showDimensions && product.dimensions ) {
		const { length, width, height, unit = '' } = product.dimensions;
		if ( length || width || height ) {
			add(
				__( 'Dimensions', 'lineweb-commerce' ),
				[ length, width, height ].filter( Boolean ).join( ' × ' ) +
					` ${ unit }`
			);
		}
	}

	( product.attributes || [] ).forEach( ( attribute ) => {
		add(
			attribute.name,
			( attribute.terms || [] ).map( ( term ) => term.name ).join( ', ' )
		);
	} );

	return rows;
}

export default function Edit( { attributes, context, setAttributes } ) {
	const contextualId =
		context.postType === 'product' ? Number( context.postId ) : 0;
	const resolvedId = attributes.productId || contextualId;
	const catalog = useProductCatalog( resolvedId );
	const rows = previewRows( catalog.product, attributes );
	const HeadingTag = `h${ attributes.headingLevel }`;
	const blockProps = useBlockProps( {
		className: 'lwc-specifications',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Product source', 'lineweb-commerce' ) }
					initialOpen
				>
					<ComboboxControl
						label={ __(
							'WooCommerce product',
							'lineweb-commerce'
						) }
						help={ __(
							'Leave empty on a product page to use the current product.',
							'lineweb-commerce'
						) }
						value={
							attributes.productId
								? String( attributes.productId )
								: ''
						}
						options={ catalog.options }
						onFilterValueChange={ catalog.setSearch }
						onChange={ ( value ) =>
							setAttributes( { productId: Number( value ) || 0 } )
						}
					/>
					{ catalog.isLoading && <Spinner /> }
					{ catalog.error && (
						<Notice status="error" isDismissible={ false }>
							{ catalog.error }
						</Notice>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Visible details', 'lineweb-commerce' ) }
				>
					<ToggleControl
						label={ __( 'Show SKU', 'lineweb-commerce' ) }
						checked={ attributes.showSku }
						onChange={ ( showSku ) => setAttributes( { showSku } ) }
					/>
					<ToggleControl
						label={ __( 'Show availability', 'lineweb-commerce' ) }
						checked={ attributes.showStock }
						onChange={ ( showStock ) =>
							setAttributes( { showStock } )
						}
					/>
					<ToggleControl
						label={ __( 'Show weight', 'lineweb-commerce' ) }
						checked={ attributes.showWeight }
						onChange={ ( showWeight ) =>
							setAttributes( { showWeight } )
						}
					/>
					<ToggleControl
						label={ __( 'Show dimensions', 'lineweb-commerce' ) }
						checked={ attributes.showDimensions }
						onChange={ ( showDimensions ) =>
							setAttributes( { showDimensions } )
						}
					/>
					<SelectControl
						label={ __( 'Heading level', 'lineweb-commerce' ) }
						value={ String( attributes.headingLevel ) }
						options={ [ 2, 3, 4 ].map( ( level ) => ( {
							label: `H${ level }`,
							value: String( level ),
						} ) ) }
						onChange={ ( value ) =>
							setAttributes( { headingLevel: Number( value ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<header className="lwc-specifications__header">
					<RichText
						tagName="p"
						className="lwc-specifications__eyebrow"
						value={ attributes.eyebrow }
						onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) }
						placeholder={ __(
							'Product information',
							'lineweb-commerce'
						) }
					/>
					<RichText
						tagName={ HeadingTag }
						className="lwc-specifications__heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
						placeholder={ __(
							'Technical specifications',
							'lineweb-commerce'
						) }
					/>
				</header>

				{ ! resolvedId && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Choose a product, or place this block in a product template.',
							'lineweb-commerce'
						) }
					</Notice>
				) }
				{ resolvedId && ! catalog.isLoading && rows.length === 0 && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'This product has no visible specifications yet.',
							'lineweb-commerce'
						) }
					</Notice>
				) }
				{ rows.length > 0 && (
					<dl className="lwc-specifications__list">
						{ rows.map( ( row ) => (
							<div key={ `${ row.label }-${ row.value }` }>
								<dt>{ row.label }</dt>
								<dd>{ row.value }</dd>
							</div>
						) ) }
					</dl>
				) }
			</section>
		</>
	);
}
