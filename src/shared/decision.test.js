import {
	calculateDecisionResults,
	calculateDecisionScore,
	clampDecisionNumber,
	findDecisionReasons,
} from './decision';

describe( 'decision scoring', () => {
	test( 'clamps numeric input and rejects invalid values', () => {
		expect( clampDecisionNumber( 120, 0, 100 ) ).toBe( 100 );
		expect( clampDecisionNumber( -4, 0, 10 ) ).toBe( 0 );
		expect( clampDecisionNumber( 'bad', 0, 10, 4 ) ).toBe( 4 );
	} );

	test( 'calculates a transparent weighted score', () => {
		expect( calculateDecisionScore( [ 100, 50 ], [ 8, 2 ] ) ).toBe( 90 );
	} );

	test( 'returns zero when every priority is disabled', () => {
		expect( calculateDecisionScore( [ 100, 80 ], [ 0, 0 ] ) ).toBe( 0 );
	} );

	test( 'recommends the highest scoring product', () => {
		const results = calculateDecisionResults(
			[
				{ scores: [ 90, 40 ] },
				{ scores: [ 70, 80 ] },
				{ scores: [ 60, 60 ] },
			],
			[ 4, 8 ]
		);

		expect( results.recommendedIndex ).toBe( 1 );
		expect( results.scores[ 1 ] ).toBeGreaterThan( results.scores[ 0 ] );
	} );

	test( 'keeps the first product when scores tie', () => {
		const results = calculateDecisionResults(
			[ { scores: [ 80 ] }, { scores: [ 80 ] } ],
			[ 10 ]
		);

		expect( results.recommendedIndex ).toBe( 0 );
	} );

	test( 'identifies the strongest reason and visible trade-off', () => {
		expect(
			findDecisionReasons(
				[ 55, 98, 72 ],
				[
					{ label: 'Value' },
					{ label: 'Ease' },
					{ label: 'Capability' },
				]
			)
		).toEqual( { strongest: 'Ease', tradeoff: 'Value' } );
	} );

	test( 'caps products and criteria to the public contract', () => {
		const products = Array.from( { length: 5 }, () => ( {
			scores: Array.from( { length: 8 }, () => 100 ),
		} ) );
		const results = calculateDecisionResults(
			products,
			Array.from( { length: 8 }, () => 10 )
		);

		expect( results.scores ).toHaveLength( 3 );
		expect( results.scores[ 0 ] ).toBe( 100 );
	} );
} );
