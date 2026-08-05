import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import {
	calculateShippingProgress,
	messageWithAmount,
} from '../shared/free-shipping';

function formatPreviewAmount( amount ) {
	const currency = globalThis.linewebCommerceEditor?.currencyCode || 'USD';
	return new Intl.NumberFormat( undefined, {
		style: 'currency',
		currency,
		minimumFractionDigits:
			globalThis.linewebCommerceEditor?.currencyDecimals,
	} ).format( amount );
}

export default function Edit( { attributes, setAttributes } ) {
	const previewGoal = attributes.threshold > 0 ? attributes.threshold : 100;
	const progress = calculateShippingProgress(
		attributes.previewCartTotal,
		previewGoal
	);
	const message = progress.unlocked
		? attributes.successMessage
		: messageWithAmount(
				attributes.message,
				formatPreviewAmount( progress.remaining )
		  );
	const blockProps = useBlockProps( {
		className: 'lwc-shipping-progress',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Free shipping rule', 'lineweb-commerce' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'Threshold', 'lineweb-commerce' ) }
						help={ __(
							'Use 0 to detect the matching WooCommerce free-shipping method automatically.',
							'lineweb-commerce'
						) }
						type="number"
						min="0"
						step="0.01"
						value={ attributes.threshold }
						onChange={ ( value ) =>
							setAttributes( {
								threshold: Math.max( 0, Number( value ) || 0 ),
							} )
						}
					/>
					{ attributes.threshold === 0 && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'The editor uses a 100-unit preview in the store currency. The storefront uses the matching WooCommerce shipping zone.',
								'lineweb-commerce'
							) }
						</Notice>
					) }
					<TextControl
						label={ __(
							'Editor preview cart total',
							'lineweb-commerce'
						) }
						type="number"
						min="0"
						step="0.01"
						value={ attributes.previewCartTotal }
						onChange={ ( value ) =>
							setAttributes( {
								previewCartTotal: Math.max(
									0,
									Number( value ) || 0
								),
							} )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Messages', 'lineweb-commerce' ) }>
					<TextControl
						label={ __( 'Progress message', 'lineweb-commerce' ) }
						help={ __(
							'Use {amount} for the live remaining amount.',
							'lineweb-commerce'
						) }
						value={ attributes.message }
						onChange={ ( value ) =>
							setAttributes( { message: value } )
						}
					/>
					<TextControl
						label={ __( 'Success message', 'lineweb-commerce' ) }
						value={ attributes.successMessage }
						onChange={ ( successMessage ) =>
							setAttributes( { successMessage } )
						}
					/>
					<TextControl
						label={ __( 'Empty-cart message', 'lineweb-commerce' ) }
						value={ attributes.emptyMessage }
						onChange={ ( emptyMessage ) =>
							setAttributes( { emptyMessage } )
						}
					/>
					<ToggleControl
						label={ __(
							'Hide when cart is empty',
							'lineweb-commerce'
						) }
						checked={ attributes.hideWhenEmpty }
						onChange={ ( hideWhenEmpty ) =>
							setAttributes( { hideWhenEmpty } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="lwc-shipping-progress__copy">
					<p className="lwc-shipping-progress__message">
						{ message }
					</p>
					<p className="lwc-shipping-progress__values">
						{ formatPreviewAmount( progress.current ) } /{ ' ' }
						{ formatPreviewAmount( progress.goal ) }
					</p>
				</div>
				<div
					className="lwc-shipping-progress__track"
					role="progressbar"
					aria-label={ __(
						'Free shipping progress',
						'lineweb-commerce'
					) }
					aria-valuemin="0"
					aria-valuemax={ progress.goal }
					aria-valuenow={ Math.min(
						progress.current,
						progress.goal
					) }
				>
					<span style={ { width: `${ progress.percentage }%` } } />
				</div>
			</section>
		</>
	);
}
