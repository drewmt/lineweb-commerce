import { getContext, store } from '@wordpress/interactivity';

import { calculateDecisionResults } from '../shared/decision';

type DecisionProduct = {
	name: string;
	scores: number[];
};

type DecisionScenario = {
	label: string;
	weights: number[];
};

type DecisionContext = {
	weights: number[];
	products: DecisionProduct[];
	scenarios: DecisionScenario[];
	activeScenario: number;
	criterionIndex?: number;
	productIndex?: number;
	scenarioIndex?: number;
	customLabel: string;
};

const getResults = () => {
	const context = getContext< DecisionContext >();
	return calculateDecisionResults( context.products, context.weights );
};

store( 'linewebCommerceDecision', {
	state: {
		get currentWeight() {
			const context = getContext< DecisionContext >();
			return context.weights[ context.criterionIndex ?? 0 ] ?? 0;
		},
		get currentWeightText() {
			const context = getContext< DecisionContext >();
			return `${
				context.weights[ context.criterionIndex ?? 0 ] ?? 0
			}/10`;
		},
		get currentProductScore() {
			const context = getContext< DecisionContext >();
			const score = getResults().scores[ context.productIndex ?? 0 ] ?? 0;
			return `${ Math.round( score ) }%`;
		},
		get recommendedName() {
			const context = getContext< DecisionContext >();
			return (
				context.products[ getResults().recommendedIndex ]?.name ||
				context.customLabel
			);
		},
		get isRecommended() {
			const context = getContext< DecisionContext >();
			return (
				( context.productIndex ?? -1 ) === getResults().recommendedIndex
			);
		},
		get isNotRecommended() {
			const context = getContext< DecisionContext >();
			return (
				( context.productIndex ?? -1 ) !== getResults().recommendedIndex
			);
		},
		get isScenarioActive() {
			const context = getContext< DecisionContext >();
			return ( context.scenarioIndex ?? -1 ) === context.activeScenario;
		},
	},
	actions: {
		updateWeight: ( event: Event ) => {
			const context = getContext< DecisionContext >();
			const index = context.criterionIndex ?? 0;
			const weights = [ ...context.weights ];
			weights[ index ] = Math.min(
				10,
				Math.max(
					0,
					Number( ( event.target as HTMLInputElement ).value )
				)
			);
			context.weights = weights;
			context.activeScenario = -1;
		},
		applyScenario: () => {
			const context = getContext< DecisionContext >();
			const index = context.scenarioIndex ?? 0;
			const scenario = context.scenarios[ index ];

			if ( scenario ) {
				context.weights = [ ...scenario.weights ];
				context.activeScenario = index;
			}
		},
	},
} );
