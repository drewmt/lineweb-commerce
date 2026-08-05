import { resetDelivery, updateDelivery } from '../delivery-estimate/view';
import {
	resetSpecifications,
	updateSpecifications,
} from '../product-specifications/view';

describe( 'variation-aware product blocks', () => {
	it( 'updates and resets specification values', () => {
		document.body.innerHTML = `
			<section data-lwc-specifications data-lwc-empty-value="Not available">
				<div data-lwc-spec-key="sku"><dd>PARENT-SKU</dd></div>
				<div data-lwc-spec-key="availability"><dd>In stock</dd></div>
				<div data-lwc-spec-key="weight"><dd>2 kg</dd></div>
				<div data-lwc-spec-key="dimensions"><dd>20 × 10 × 5 cm</dd></div>
			</section>`;
		const root = document.querySelector( '[data-lwc-specifications]' );

		updateSpecifications( root, {
			sku: 'VARIATION-SKU',
			availability_html: '<p>Available on backorder</p>',
			weight_html: '<span>2 kg</span>',
			dimensions_html: '<span>30 × 20 × 10 cm</span>',
			lineweb_commerce: {
				is_virtual: true,
			},
		} );

		expect(
			root.querySelector( '[data-lwc-spec-key="sku"] dd' ).textContent
		).toContain( 'VARIATION-SKU' );
		expect(
			root.querySelector( '[data-lwc-spec-key="availability"] dd' )
				.textContent
		).toContain( 'Available on backorder' );
		expect(
			root.querySelector( '[data-lwc-spec-key="weight"] dd' ).textContent
		).toContain( 'Not available' );
		expect(
			root.querySelector( '[data-lwc-spec-key="dimensions"] dd' )
				.textContent
		).toContain( 'Not available' );
		resetSpecifications( root );
		expect(
			root.querySelector( '[data-lwc-spec-key="sku"] dd' ).textContent
		).toContain( 'PARENT-SKU' );
		expect(
			root.querySelector( '[data-lwc-spec-key="weight"] dd' ).textContent
		).toContain( '2 kg' );
	} );

	it( 'handles physical, backorder, out-of-stock, and virtual delivery states', () => {
		document.body.innerHTML = `
			<section data-lwc-delivery data-lwc-backorder="Ships later" data-lwc-out-of-stock="Unavailable">
				<p class="lwc-delivery__window"><time>Original window</time></p>
			</section>`;
		const root = document.querySelector( '[data-lwc-delivery]' );
		const message = root.querySelector( '.lwc-delivery__window' );

		updateDelivery( root, { is_virtual: false, is_in_stock: true } );
		expect( message.textContent ).toContain( 'Original window' );
		updateDelivery( root, {
			is_virtual: false,
			is_in_stock: true,
			lineweb_commerce: {
				is_virtual: false,
				is_in_stock: true,
				is_on_backorder: true,
			},
		} );
		expect( message.textContent ).toContain( 'Ships later' );
		updateDelivery( root, { is_virtual: false, is_in_stock: false } );
		expect( message.textContent ).toContain( 'Unavailable' );
		updateDelivery( root, { is_virtual: true } );
		expect( root.hidden ).toBe( true );
		resetDelivery( root );
		expect( root.hidden ).toBe( false );
		expect( message.textContent ).toContain( 'Original window' );
	} );
} );
