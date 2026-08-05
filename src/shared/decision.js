export function clampDecisionNumber( value, minimum, maximum, fallback = 0 ) {
	const parsed = Number( value );

	if ( ! Number.isFinite( parsed ) ) {
		return fallback;
	}

	return Math.min( maximum, Math.max( minimum, parsed ) );
}

export function calculateDecisionScore( scores, weights ) {
	const usableScores = Array.isArray( scores ) ? scores.slice( 0, 6 ) : [];
	const usableWeights = Array.isArray( weights )
		? weights.slice( 0, usableScores.length )
		: [];
	let weightedTotal = 0;
	let weightTotal = 0;

	usableScores.forEach( ( rawScore, index ) => {
		const score = clampDecisionNumber( rawScore, 0, 100, 0 );
		const weight = clampDecisionNumber( usableWeights[ index ], 0, 10, 0 );

		weightedTotal += score * weight;
		weightTotal += weight;
	} );

	return weightTotal > 0 ? weightedTotal / weightTotal : 0;
}

export function calculateDecisionResults( products, weights ) {
	const usableProducts = Array.isArray( products )
		? products.slice( 0, 3 )
		: [];
	const scores = usableProducts.map( ( product ) =>
		calculateDecisionScore( product.scores, weights )
	);
	let recommendedIndex = 0;

	scores.forEach( ( score, index ) => {
		if ( score > scores[ recommendedIndex ] ) {
			recommendedIndex = index;
		}
	} );

	return { scores, recommendedIndex };
}

export function findDecisionReasons( scores, criteria ) {
	const normalizedScores = Array.isArray( scores )
		? scores.slice( 0, 6 )
		: [];
	const normalizedCriteria = Array.isArray( criteria )
		? criteria.slice( 0, normalizedScores.length )
		: [];
	let strongestIndex = 0;
	let tradeoffIndex = 0;

	normalizedScores.forEach( ( rawScore, index ) => {
		const score = clampDecisionNumber( rawScore, 0, 100, 0 );

		if (
			score >
			clampDecisionNumber( normalizedScores[ strongestIndex ], 0, 100, 0 )
		) {
			strongestIndex = index;
		}

		if (
			score <
			clampDecisionNumber( normalizedScores[ tradeoffIndex ], 0, 100, 0 )
		) {
			tradeoffIndex = index;
		}
	} );

	return {
		strongest:
			normalizedCriteria[ strongestIndex ]?.label || 'Strongest fit',
		tradeoff:
			normalizedCriteria[ tradeoffIndex ]?.label || 'Main trade-off',
	};
}
