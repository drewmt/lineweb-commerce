import {
	calculateDeliveryWindow,
	parseHolidayDates,
	toDateKey,
} from './delivery';

describe( 'delivery window', () => {
	it( 'skips a weekend after the Friday cutoff', () => {
		const result = calculateDeliveryWindow( {
			minimumDays: 1,
			maximumDays: 3,
			cutoffHour: 14,
			now: new Date( 2026, 7, 7, 15, 0, 0 ),
		} );

		expect( toDateKey( result.start ) ).toBe( '2026-08-11' );
		expect( toDateKey( result.end ) ).toBe( '2026-08-13' );
	} );

	it( 'skips configured holiday dates', () => {
		const result = calculateDeliveryWindow( {
			minimumDays: 1,
			maximumDays: 1,
			holidayDates: '2026-08-06',
			now: new Date( 2026, 7, 5, 10, 0, 0 ),
		} );

		expect( toDateKey( result.start ) ).toBe( '2026-08-07' );
	} );

	it( 'accepts only bounded ISO holiday values', () => {
		expect( [
			...parseHolidayDates( '2026-08-15, no, 12/2/2026' ),
		] ).toEqual( [ '2026-08-15' ] );
	} );

	it( 'uses the merchant ISO working-day profile', () => {
		const result = calculateDeliveryWindow( {
			minimumDays: 1,
			maximumDays: 1,
			workingDays: [ 2, 3, 4, 5, 6 ],
			now: new Date( 2026, 7, 7, 10, 0, 0 ),
		} );

		expect( toDateKey( result.start ) ).toBe( '2026-08-08' );
	} );
} );
