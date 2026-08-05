import apiFetch from '@wordpress/api-fetch';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	ComboboxControl,
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import {
	calculateDecisionResults,
	findDecisionReasons,
} from '../shared/decision';

function formatStorePrice( product ) {
	const prices = product?.prices;

	if ( ! prices ) {
		return '';
	}

	const minorUnit = Number( prices.currency_minor_unit ) || 0;
	const amount = Number( prices.price ) / 10 ** minorUnit;

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: prices.currency_code,
			minimumFractionDigits: minorUnit,
			maximumFractionDigits: minorUnit,
		} ).format( amount );
	} catch {
		return `${ prices.currency_prefix || '' }${ amount.toFixed(
			minorUnit
		) }${ prices.currency_suffix || '' }`;
	}
}

function moveItem( items, from, to ) {
	if ( to < 0 || to >= items.length ) {
		return items;
	}

	const next = [ ...items ];
	const [ item ] = next.splice( from, 1 );
	next.splice( to, 0, item );
	return next;
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		eyebrow,
		heading,
		description,
		headingLevel,
		productIds,
		criteria,
		scenarios,
		disclaimer,
	} = attributes;
	const blockProps = useBlockProps( { className: 'lwc-decision-room' } );
	const [ search, setSearch ] = useState( '' );
	const [ productCache, setProductCache ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ productError, setProductError ] = useState( '' );

	useEffect( () => {
		let active = true;
		const timer = window.setTimeout( async () => {
			setIsLoading( true );
			setProductError( '' );

			try {
				const query = new URLSearchParams( {
					per_page: '30',
					orderby: 'title',
					order: 'asc',
				} );

				if ( search.trim().length > 0 ) {
					query.set( 'search', search.trim() );
				}

				const products = await apiFetch( {
					path: `/wc/store/v1/products?${ query.toString() }`,
				} );

				if ( active ) {
					setProductCache( ( current ) => {
						const next = { ...current };
						products.forEach( ( product ) => {
							next[ product.id ] = product;
						} );
						return next;
					} );
				}
			} catch ( error ) {
				if ( active ) {
					setProductError(
						error?.message ||
							__(
								'WooCommerce products could not be loaded.',
								'lineweb-commerce'
							)
					);
				}
			} finally {
				if ( active ) {
					setIsLoading( false );
				}
			}
		}, 220 );

		return () => {
			active = false;
			window.clearTimeout( timer );
		};
	}, [ search ] );

	useEffect( () => {
		const missingIds = productIds.filter(
			( productId ) => productId && ! productCache[ productId ]
		);

		if ( missingIds.length < 1 ) {
			return undefined;
		}

		let active = true;
		apiFetch( {
			path: `/wc/store/v1/products?include=${ missingIds.join( ',' ) }`,
		} )
			.then( ( products ) => {
				if ( active ) {
					setProductCache( ( current ) => {
						const next = { ...current };
						products.forEach( ( product ) => {
							next[ product.id ] = product;
						} );
						return next;
					} );
				}
			} )
			.catch( () => {} );

		return () => {
			active = false;
		};
	}, [ productIds, productCache ] );

	const productOptions = useMemo(
		() =>
			Object.values( productCache )
				.sort( ( first, second ) =>
					first.name.localeCompare( second.name )
				)
				.map( ( product ) => ( {
					label: product.name,
					value: String( product.id ),
				} ) ),
		[ productCache ]
	);
	const selectedProducts = productIds
		.map( ( productId ) => productCache[ productId ] )
		.filter( Boolean );
	const initialWeights =
		scenarios[ 0 ]?.weights?.slice( 0, criteria.length ) ||
		criteria.map( ( criterion ) => criterion.weight );
	const previewProducts = selectedProducts.map(
		( product, productIndex ) => ( {
			name: product.name,
			scores: criteria.map(
				( criterion ) => criterion.scores?.[ productIndex ] || 0
			),
		} )
	);
	const previewResults = calculateDecisionResults(
		previewProducts,
		initialWeights
	);

	const setProductAt = ( index, value ) => {
		const nextId = Number( value ) || 0;
		const nextIds = [ ...productIds ];
		nextIds[ index ] = nextId;
		setAttributes( {
			productIds: nextIds.filter(
				( productId, productIndex, allIds ) =>
					productId > 0 &&
					allIds.indexOf( productId ) === productIndex
			),
		} );
	};

	const updateCriterion = ( index, patch ) => {
		const next = criteria.map( ( criterion, criterionIndex ) =>
			criterionIndex === index ? { ...criterion, ...patch } : criterion
		);
		setAttributes( { criteria: next } );
	};

	const updateScore = ( criterionIndex, productIndex, value ) => {
		const scores = [ ...( criteria[ criterionIndex ].scores || [] ) ];
		scores[ productIndex ] = value;
		updateCriterion( criterionIndex, { scores } );
	};

	const addCriterion = () => {
		if ( criteria.length >= 6 ) {
			return;
		}

		const nextCriteria = [
			...criteria,
			{
				id: `criterion-${ Date.now() }`,
				label: __( 'New criterion', 'lineweb-commerce' ),
				description: __(
					'Explain exactly what this criterion measures.',
					'lineweb-commerce'
				),
				weight: 5,
				scores: [ 70, 70, 70 ],
				evidenceLabel: __(
					'Add the assessment basis',
					'lineweb-commerce'
				),
				evidenceUrl: '',
			},
		];
		const nextScenarios = scenarios.map( ( scenario ) => ( {
			...scenario,
			weights: [ ...( scenario.weights || [] ), 5 ],
		} ) );
		setAttributes( {
			criteria: nextCriteria,
			scenarios: nextScenarios,
		} );
	};

	const removeCriterion = ( index ) => {
		if ( criteria.length <= 1 ) {
			return;
		}

		setAttributes( {
			criteria: criteria.filter(
				( criterion, criterionIndex ) => criterionIndex !== index
			),
			scenarios: scenarios.map( ( scenario ) => ( {
				...scenario,
				weights: ( scenario.weights || [] ).filter(
					( weight, weightIndex ) => weightIndex !== index
				),
			} ) ),
		} );
	};

	const moveCriterion = ( from, to ) => {
		setAttributes( {
			criteria: moveItem( criteria, from, to ),
			scenarios: scenarios.map( ( scenario ) => ( {
				...scenario,
				weights: moveItem( scenario.weights || [], from, to ),
			} ) ),
		} );
	};

	const updateScenario = ( scenarioIndex, patch ) => {
		setAttributes( {
			scenarios: scenarios.map( ( scenario, index ) =>
				index === scenarioIndex ? { ...scenario, ...patch } : scenario
			),
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Live WooCommerce products',
						'lineweb-commerce'
					) }
					initialOpen
				>
					<p>
						{ __(
							'Choose two or three published products. Price and stock always come from WooCommerce.',
							'lineweb-commerce'
						) }
					</p>
					{ [ 0, 1, 2 ].map( ( productIndex ) => (
						<ComboboxControl
							key={ productIndex }
							__next40pxDefaultSize
							label={ sprintf(
								/* translators: %d: candidate number. */
								__( 'Candidate %d', 'lineweb-commerce' ),
								productIndex + 1
							) }
							value={ String( productIds[ productIndex ] || '' ) }
							options={ productOptions }
							onChange={ ( value ) =>
								setProductAt( productIndex, value )
							}
							onFilterValueChange={ setSearch }
						/>
					) ) }
					{ isLoading && <Spinner /> }
					{ productError && (
						<Notice status="error" isDismissible={ false }>
							{ productError }
						</Notice>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Priority scenarios', 'lineweb-commerce' ) }
					initialOpen={ false }
				>
					{ scenarios.map( ( scenario, scenarioIndex ) => (
						<div
							className="lwc-editor-scenario"
							key={ `scenario-${ scenarioIndex }` }
						>
							<TextControl
								__next40pxDefaultSize
								label={ sprintf(
									/* translators: %d: scenario number. */
									__(
										'Scenario %d label',
										'lineweb-commerce'
									),
									scenarioIndex + 1
								) }
								value={ scenario.label }
								onChange={ ( label ) =>
									updateScenario( scenarioIndex, { label } )
								}
							/>
							{ criteria.map( ( criterion, criterionIndex ) => (
								<RangeControl
									__next40pxDefaultSize
									key={ criterion.id }
									label={ criterion.label }
									min={ 0 }
									max={ 10 }
									value={
										scenario.weights?.[ criterionIndex ] ||
										0
									}
									onChange={ ( value ) => {
										const weights = [
											...( scenario.weights || [] ),
										];
										weights[ criterionIndex ] = value;
										updateScenario( scenarioIndex, {
											weights,
										} );
									} }
								/>
							) ) }
						</div>
					) ) }
				</PanelBody>

				<PanelBody
					title={ __( 'Document structure', 'lineweb-commerce' ) }
					initialOpen={ false }
				>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Heading level', 'lineweb-commerce' ) }
						value={ String( headingLevel ) }
						options={ [ 2, 3, 4 ].map( ( level ) => ( {
							label: `H${ level }`,
							value: String( level ),
						} ) ) }
						onChange={ ( value ) =>
							setAttributes( { headingLevel: Number( value ) } )
						}
					/>
					<TextareaControl
						__next40pxDefaultSize
						label={ __( 'Method disclaimer', 'lineweb-commerce' ) }
						value={ disclaimer }
						onChange={ ( value ) =>
							setAttributes( { disclaimer: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="lwc-decision__header">
					<div>
						<RichText
							tagName="p"
							className="lwc-decision__eyebrow"
							value={ eyebrow }
							onChange={ ( value ) =>
								setAttributes( { eyebrow: value } )
							}
							placeholder={ __( 'Eyebrow', 'lineweb-commerce' ) }
						/>
						<RichText
							tagName={ `h${ headingLevel }` }
							className="lwc-decision__heading"
							value={ heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
							placeholder={ __(
								'Decision heading',
								'lineweb-commerce'
							) }
						/>
					</div>
					<RichText
						tagName="p"
						className="lwc-decision__description"
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
						placeholder={ __(
							'Explain how it works.',
							'lineweb-commerce'
						) }
					/>
				</div>

				<Notice status="warning" isDismissible={ false }>
					{ __(
						'The starter scores are instructional. Review every score and evidence label before publishing.',
						'lineweb-commerce'
					) }
				</Notice>

				<div className="lwc-editor-model">
					<div className="lwc-decision__section-heading">
						<span>01</span>
						<div>
							<strong>
								{ __(
									'Build the reasoning model',
									'lineweb-commerce'
								) }
							</strong>
							<p>
								{ __(
									'Scores stay visible and bounded from 0 to 100.',
									'lineweb-commerce'
								) }
							</p>
						</div>
					</div>

					{ criteria.map( ( criterion, criterionIndex ) => (
						<div
							className="lwc-editor-criterion"
							key={ criterion.id }
						>
							<div className="lwc-editor-criterion__toolbar">
								<strong>
									{ sprintf(
										/* translators: %d: criterion number. */
										__(
											'Criterion %d',
											'lineweb-commerce'
										),
										criterionIndex + 1
									) }
								</strong>
								<div>
									<Button
										label={ __(
											'Move criterion up',
											'lineweb-commerce'
										) }
										icon="arrow-up-alt2"
										disabled={ criterionIndex === 0 }
										onClick={ () =>
											moveCriterion(
												criterionIndex,
												criterionIndex - 1
											)
										}
									/>
									<Button
										label={ __(
											'Move criterion down',
											'lineweb-commerce'
										) }
										icon="arrow-down-alt2"
										disabled={
											criterionIndex ===
											criteria.length - 1
										}
										onClick={ () =>
											moveCriterion(
												criterionIndex,
												criterionIndex + 1
											)
										}
									/>
									<Button
										label={ __(
											'Remove criterion',
											'lineweb-commerce'
										) }
										icon="trash"
										isDestructive
										disabled={ criteria.length <= 1 }
										onClick={ () =>
											removeCriterion( criterionIndex )
										}
									/>
								</div>
							</div>
							<div className="lwc-editor-criterion__copy">
								<TextControl
									__next40pxDefaultSize
									label={ __(
										'Criterion label',
										'lineweb-commerce'
									) }
									value={ criterion.label }
									onChange={ ( label ) =>
										updateCriterion( criterionIndex, {
											label,
										} )
									}
								/>
								<TextareaControl
									__next40pxDefaultSize
									label={ __(
										'What it measures',
										'lineweb-commerce'
									) }
									value={ criterion.description }
									onChange={ ( criterionDescription ) =>
										updateCriterion( criterionIndex, {
											description: criterionDescription,
										} )
									}
								/>
								<RangeControl
									__next40pxDefaultSize
									label={ __(
										'Default importance',
										'lineweb-commerce'
									) }
									min={ 0 }
									max={ 10 }
									value={ criterion.weight }
									onChange={ ( weight ) =>
										updateCriterion( criterionIndex, {
											weight,
										} )
									}
								/>
							</div>

							<div className="lwc-editor-criterion__scores">
								{ [ 0, 1, 2 ].map( ( productIndex ) => (
									<RangeControl
										__next40pxDefaultSize
										key={ productIndex }
										label={
											selectedProducts[ productIndex ]
												?.name ||
											sprintf(
												/* translators: %d: candidate number. */
												__(
													'Candidate %d',
													'lineweb-commerce'
												),
												productIndex + 1
											)
										}
										min={ 0 }
										max={ 100 }
										value={
											criterion.scores?.[
												productIndex
											] || 0
										}
										onChange={ ( value ) =>
											updateScore(
												criterionIndex,
												productIndex,
												value
											)
										}
									/>
								) ) }
							</div>

							<div className="lwc-editor-criterion__evidence">
								<TextControl
									__next40pxDefaultSize
									label={ __(
										'Evidence or assessment basis',
										'lineweb-commerce'
									) }
									value={ criterion.evidenceLabel }
									onChange={ ( evidenceLabel ) =>
										updateCriterion( criterionIndex, {
											evidenceLabel,
										} )
									}
								/>
								<TextControl
									__next40pxDefaultSize
									label={ __(
										'Evidence URL',
										'lineweb-commerce'
									) }
									type="url"
									value={ criterion.evidenceUrl }
									onChange={ ( evidenceUrl ) =>
										updateCriterion( criterionIndex, {
											evidenceUrl,
										} )
									}
								/>
							</div>
						</div>
					) ) }

					<Button
						variant="secondary"
						disabled={ criteria.length >= 6 }
						onClick={ addCriterion }
					>
						{ __( 'Add criterion', 'lineweb-commerce' ) }
					</Button>
				</div>

				<div className="lwc-editor-preview">
					<div className="lwc-decision__section-heading">
						<span>02</span>
						<div>
							<strong>
								{ __( 'Live Woo preview', 'lineweb-commerce' ) }
							</strong>
							<p>
								{ __(
									'Price and availability come from the current catalog.',
									'lineweb-commerce'
								) }
							</p>
						</div>
					</div>

					{ selectedProducts.length < 2 ? (
						<div className="lwc-decision__empty">
							<strong>
								{ __(
									'Choose at least two products.',
									'lineweb-commerce'
								) }
							</strong>
							<p>
								{ __(
									'Use the Product settings in the sidebar.',
									'lineweb-commerce'
								) }
							</p>
						</div>
					) : (
						<div className="lwc-decision__products">
							{ selectedProducts.map(
								( product, productIndex ) => {
									const productScores = criteria.map(
										( criterion ) =>
											criterion.scores?.[
												productIndex
											] || 0
									);
									const reasons = findDecisionReasons(
										productScores,
										criteria
									);
									const isRecommended =
										previewResults.recommendedIndex ===
										productIndex;

									return (
										<article
											className={ `lwc-decision__product${
												isRecommended
													? ' is-recommended'
													: ''
											}` }
											key={ product.id }
										>
											<div className="lwc-decision__product-image">
												{ product.images?.[ 0 ] && (
													<img
														src={
															product.images[ 0 ]
																.thumbnail ||
															product.images[ 0 ]
																.src
														}
														alt={
															product.images[ 0 ]
																.alt || ''
														}
													/>
												) }
												{ isRecommended && (
													<span>
														{ __(
															'Top fit',
															'lineweb-commerce'
														) }
													</span>
												) }
											</div>
											<div className="lwc-decision__product-body">
												<div className="lwc-decision__score">
													<strong>
														{ Math.round(
															previewResults
																.scores[
																productIndex
															] || 0
														) }
														%
													</strong>
													<span>
														{ __(
															'priority fit',
															'lineweb-commerce'
														) }
													</span>
												</div>
												<h3>{ product.name }</h3>
												<p className="lwc-decision__price">
													{ formatStorePrice(
														product
													) }
												</p>
												<p
													className={ `lwc-decision__stock ${
														product.is_in_stock
															? 'is-in-stock'
															: 'is-out-of-stock'
													}` }
												>
													{ product.is_in_stock
														? __(
																'Available',
																'lineweb-commerce'
														  )
														: __(
																'Currently unavailable',
																'lineweb-commerce'
														  ) }
												</p>
												<dl>
													<div>
														<dt>
															{ __(
																'Strongest fit',
																'lineweb-commerce'
															) }
														</dt>
														<dd>
															{
																reasons.strongest
															}
														</dd>
													</div>
													<div>
														<dt>
															{ __(
																'Main trade-off',
																'lineweb-commerce'
															) }
														</dt>
														<dd>
															{ reasons.tradeoff }
														</dd>
													</div>
												</dl>
											</div>
										</article>
									);
								}
							) }
						</div>
					) }
				</div>
			</section>
		</>
	);
}
