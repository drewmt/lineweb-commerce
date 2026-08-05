import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	ComboboxControl,
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { calculateDeliveryWindow } from '../shared/delivery';
import { useProductCatalog } from '../shared/product-catalog';

function formatDate( date ) {
	return new Intl.DateTimeFormat( undefined, {
		day: 'numeric',
		month: 'short',
	} ).format( date );
}

export default function Edit( { attributes, context, setAttributes } ) {
	const contextualId =
		context.postType === 'product' ? Number( context.postId ) : 0;
	const resolvedId = attributes.productId || contextualId;
	const catalog = useProductCatalog( resolvedId );
	const globalProfile = globalThis.linewebCommerceEditor?.deliveryProfile;
	const usesGlobal = attributes.profileSource === 'global';
	const rules = usesGlobal
		? {
				minimumBusinessDays: globalProfile?.minimum_days,
				maximumBusinessDays: globalProfile?.maximum_days,
				cutoffHour: globalProfile?.cutoff_hour,
				workingDays: globalProfile?.working_days,
				holidayDates: Object.keys( globalProfile?.holidays || {} ).join(
					', '
				),
		  }
		: attributes;
	const window = calculateDeliveryWindow( rules );
	const HeadingTag = `h${ attributes.headingLevel }`;
	const blockProps = useBlockProps( { className: 'lwc-delivery' } );
	let statusMessage = sprintf(
		/* translators: %1$s: first date, %2$s: last date. */
		__( '%1$s – %2$s', 'lineweb-commerce' ),
		formatDate( window.start ),
		formatDate( window.end )
	);

	if ( catalog.product && ! catalog.product.is_in_stock ) {
		statusMessage = attributes.outOfStockMessage;
	} else if ( catalog.product?.is_on_backorder ) {
		statusMessage = attributes.backorderMessage;
	}

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
				<PanelBody title={ __( 'Delivery rules', 'lineweb-commerce' ) }>
					<SelectControl
						label={ __( 'Rules source', 'lineweb-commerce' ) }
						value={ attributes.profileSource }
						options={ [
							{
								label: __(
									'Global delivery profile',
									'lineweb-commerce'
								),
								value: 'global',
							},
							{
								label: __(
									'Local block override',
									'lineweb-commerce'
								),
								value: 'local',
							},
						] }
						onChange={ ( profileSource ) =>
							setAttributes( { profileSource } )
						}
					/>
					{ usesGlobal && ! globalProfile?.confirmed && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'The global profile is not confirmed. This block will stay hidden on the storefront until a merchant approves it in WooCommerce settings.',
								'lineweb-commerce'
							) }
						</Notice>
					) }
					{ ! usesGlobal && (
						<>
							<RangeControl
								label={ __(
									'Minimum business days',
									'lineweb-commerce'
								) }
								min={ 0 }
								max={ 30 }
								value={ attributes.minimumBusinessDays }
								onChange={ ( minimumBusinessDays ) =>
									setAttributes( {
										minimumBusinessDays,
										maximumBusinessDays: Math.max(
											minimumBusinessDays,
											attributes.maximumBusinessDays
										),
									} )
								}
							/>
							<RangeControl
								label={ __(
									'Maximum business days',
									'lineweb-commerce'
								) }
								min={ attributes.minimumBusinessDays }
								max={ 60 }
								value={ attributes.maximumBusinessDays }
								onChange={ ( maximumBusinessDays ) =>
									setAttributes( { maximumBusinessDays } )
								}
							/>
							<RangeControl
								label={ __(
									'Daily cutoff hour',
									'lineweb-commerce'
								) }
								help={ __(
									'Uses the WordPress site timezone.',
									'lineweb-commerce'
								) }
								min={ 0 }
								max={ 23 }
								value={ attributes.cutoffHour }
								onChange={ ( cutoffHour ) =>
									setAttributes( { cutoffHour } )
								}
							/>
							<ToggleControl
								label={ __(
									'Skip weekends',
									'lineweb-commerce'
								) }
								checked={ attributes.skipWeekends }
								onChange={ ( skipWeekends ) =>
									setAttributes( { skipWeekends } )
								}
							/>
							<TextareaControl
								label={ __(
									'Holiday dates',
									'lineweb-commerce'
								) }
								help={ __(
									'Comma-separated YYYY-MM-DD dates.',
									'lineweb-commerce'
								) }
								value={ attributes.holidayDates }
								onChange={ ( holidayDates ) =>
									setAttributes( { holidayDates } )
								}
							/>
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Messages', 'lineweb-commerce' ) }>
					<TextControl
						label={ __(
							'Out-of-stock message',
							'lineweb-commerce'
						) }
						value={ attributes.outOfStockMessage }
						onChange={ ( outOfStockMessage ) =>
							setAttributes( { outOfStockMessage } )
						}
					/>
					<TextControl
						label={ __( 'Backorder message', 'lineweb-commerce' ) }
						value={ attributes.backorderMessage }
						onChange={ ( backorderMessage ) =>
							setAttributes( { backorderMessage } )
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
				<div className="lwc-delivery__icon" aria-hidden="true">
					24
				</div>
				<div className="lwc-delivery__content">
					<RichText
						tagName="p"
						className="lwc-delivery__eyebrow"
						value={ attributes.eyebrow }
						onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) }
					/>
					<RichText
						tagName={ HeadingTag }
						className="lwc-delivery__heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
					/>
					<p className="lwc-delivery__window">{ statusMessage }</p>
					{ ! resolvedId && (
						<p className="lwc-delivery__note">
							{ __(
								'Choose a product, or use this block in a product template.',
								'lineweb-commerce'
							) }
						</p>
					) }
				</div>
			</section>
		</>
	);
}
