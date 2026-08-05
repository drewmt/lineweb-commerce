import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	CheckboxControl,
	ComboboxControl,
	Notice,
	PanelBody,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { useProductCatalog } from '../shared/product-catalog';

function formatPrice( product ) {
	if ( ! product?.prices ) {
		return '—';
	}
	const decimals = Number( product.prices.currency_minor_unit ) || 0;
	const value = Number( product.prices.price ) / 10 ** decimals;
	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: product.prices.currency_code,
			minimumFractionDigits: decimals,
		} ).format( value );
	} catch {
		return `${ product.prices.currency_prefix || '' }${ value.toFixed(
			decimals
		) }${ product.prices.currency_suffix || '' }`;
	}
}

function attributeToken( attribute ) {
	return String( attribute.taxonomy || attribute.name || '' )
		.toLowerCase()
		.replace( /[^a-z0-9_-]+/g, '-' )
		.replace( /^-|-$/g, '' );
}

function attributeValue( product, token ) {
	const attribute = ( product?.attributes || [] ).find(
		( item ) => attributeToken( item ) === token
	);
	return attribute
		? ( attribute.terms || [] ).map( ( term ) => term.name ).join( ', ' )
		: '';
}

function dimensionValue( product ) {
	const dimensions = product?.dimensions;
	if ( ! dimensions ) {
		return '';
	}
	const values = [ dimensions.length, dimensions.width, dimensions.height ];
	return values.some( Boolean )
		? `${ values.filter( Boolean ).join( ' × ' ) } ${
				dimensions.unit || ''
		  }`
		: '';
}

export default function Edit( { attributes, setAttributes } ) {
	const productIds = useMemo(
		() =>
			( attributes.productIds || [] )
				.map( Number )
				.filter( Boolean )
				.slice( 0, 4 ),
		[ attributes.productIds ]
	);
	const catalog = useProductCatalog( productIds );
	const products = catalog.products;
	const HeadingTag = `h${ attributes.headingLevel }`;
	const blockProps = useBlockProps( { className: 'lwc-comparison' } );
	const attributeOptions = useMemo( () => {
		const options = new Map();
		products.forEach( ( product ) => {
			( product.attributes || [] ).forEach( ( attribute ) => {
				const token = attributeToken( attribute );
				if ( token ) {
					options.set( token, attribute.name );
				}
			} );
		} );
		return [ ...options.entries() ].map( ( [ value, label ] ) => ( {
			value,
			label,
		} ) );
	}, [ products ] );
	const setProductAt = ( index, value ) => {
		const next = [ ...productIds ];
		const productId = Number( value ) || 0;
		if ( ! productId ) {
			next.splice( index, 1 );
		} else {
			const duplicate = next.indexOf( productId );
			if ( duplicate >= 0 && duplicate !== index ) {
				next.splice( duplicate, 1 );
			}
			next[ index ] = productId;
		}
		setAttributes( { productIds: next.filter( Boolean ).slice( 0, 4 ) } );
	};
	const rows = [
		{
			label: __( 'Price', 'lineweb-commerce' ),
			values: products.map( formatPrice ),
		},
	];
	if ( attributes.showAvailability ) {
		rows.push( {
			label: __( 'Availability', 'lineweb-commerce' ),
			values: products.map( ( product ) =>
				product.is_in_stock
					? __( 'In stock', 'lineweb-commerce' )
					: __( 'Out of stock', 'lineweb-commerce' )
			),
		} );
	}
	if ( attributes.showDimensions ) {
		rows.push( {
			label: __( 'Dimensions', 'lineweb-commerce' ),
			values: products.map( dimensionValue ),
		} );
	}
	( attributes.selectedAttributes || [] ).forEach( ( token ) => {
		rows.push( {
			label:
				attributeOptions.find( ( option ) => option.value === token )
					?.label || token,
			values: products.map( ( product ) =>
				attributeValue( product, token )
			),
		} );
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Products', 'lineweb-commerce' ) }
					initialOpen
				>
					<p>
						{ __(
							'Select two to four published, catalog-visible products.',
							'lineweb-commerce'
						) }
					</p>
					{ [ 0, 1, 2, 3 ].map( ( index ) => (
						<ComboboxControl
							key={ index }
							label={ sprintf(
								/* translators: %d: product position. */
								__( 'Product %d', 'lineweb-commerce' ),
								index + 1
							) }
							value={
								productIds[ index ]
									? String( productIds[ index ] )
									: ''
							}
							options={ catalog.options }
							onFilterValueChange={ catalog.setSearch }
							onChange={ ( value ) =>
								setProductAt( index, value )
							}
						/>
					) ) }
					{ catalog.isLoading && <Spinner /> }
					{ catalog.error && (
						<Notice status="error" isDismissible={ false }>
							{ catalog.error }
						</Notice>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Comparison rows', 'lineweb-commerce' ) }
				>
					<ToggleControl
						label={ __( 'Show availability', 'lineweb-commerce' ) }
						checked={ attributes.showAvailability }
						onChange={ ( showAvailability ) =>
							setAttributes( { showAvailability } )
						}
					/>
					<ToggleControl
						label={ __( 'Show dimensions', 'lineweb-commerce' ) }
						checked={ attributes.showDimensions }
						onChange={ ( showDimensions ) =>
							setAttributes( { showDimensions } )
						}
					/>
					{ attributeOptions.map( ( option ) => (
						<CheckboxControl
							key={ option.value }
							label={ option.label }
							checked={ attributes.selectedAttributes.includes(
								option.value
							) }
							onChange={ ( checked ) =>
								setAttributes( {
									selectedAttributes: checked
										? [
												...attributes.selectedAttributes,
												option.value,
										  ].slice( 0, 12 )
										: attributes.selectedAttributes.filter(
												( value ) =>
													value !== option.value
										  ),
								} )
							}
						/>
					) ) }
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
					<TextControl
						label={ __(
							'Differences button label',
							'lineweb-commerce'
						) }
						value={ attributes.differencesLabel }
						onChange={ ( differencesLabel ) =>
							setAttributes( { differencesLabel } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<header className="lwc-comparison__header">
					<RichText
						tagName="p"
						className="lwc-comparison__eyebrow"
						value={ attributes.eyebrow }
						onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) }
					/>
					<RichText
						tagName={ HeadingTag }
						className="lwc-comparison__heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
					/>
				</header>
				{ productIds.length < 2 && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Select at least two products to build the live comparison.',
							'lineweb-commerce'
						) }
					</Notice>
				) }
				{ products.length >= 2 && (
					<div className="lwc-comparison__scroller">
						<table>
							<thead>
								<tr>
									<th scope="col">
										{ __( 'Feature', 'lineweb-commerce' ) }
									</th>
									{ products.map( ( product ) => (
										<th key={ product.id } scope="col">
											{ product.images?.[ 0 ] && (
												<img
													src={
														product.images[ 0 ]
															.thumbnail
													}
													alt=""
												/>
											) }
											<span>{ product.name }</span>
										</th>
									) ) }
								</tr>
							</thead>
							<tbody>
								{ rows.map( ( row ) => (
									<tr key={ row.label }>
										<th scope="row">{ row.label }</th>
										{ row.values.map( ( value, index ) => (
											<td key={ products[ index ].id }>
												{ value || '—' }
											</td>
										) ) }
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				) }
			</section>
		</>
	);
}
