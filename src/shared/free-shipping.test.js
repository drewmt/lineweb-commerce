import {
	calculateShippingProgress,
	calculateStoreSubtotal,
	messageWithAmount,
} from './free-shipping';

describe( 'free shipping progress', () => {
	it( 'matches the displayed subtotal after discounts and tax', () => {
		expect(
			calculateStoreSubtotal(
				{
					total_items: '10000',
					total_items_tax: '2400',
					total_discount: '1000',
					total_discount_tax: '240',
				},
				false,
				true
			)
		).toBe( 11160 );
	} );

	it( 'can ignore discounts like the WooCommerce method setting', () => {
		expect(
			calculateStoreSubtotal(
				{
					total_items: '10000',
					total_items_tax: '2400',
					total_discount: '1000',
					total_discount_tax: '240',
				},
				true,
				true
			)
		).toBe( 12400 );
	} );

	it( 'bounds remaining amount and progress', () => {
		expect( calculateShippingProgress( 4900, 20000 ) ).toMatchObject( {
			remaining: 15100,
			percentage: 24.5,
			unlocked: false,
		} );
		expect( calculateShippingProgress( 21000, 20000 ) ).toMatchObject( {
			remaining: 0,
			percentage: 100,
			unlocked: true,
		} );
	} );

	it( 'replaces every amount placeholder without HTML', () => {
		expect(
			messageWithAmount( '{amount} left — {amount}', '$15.00' )
		).toBe( '$15.00 left — $15.00' );
	} );
} );
