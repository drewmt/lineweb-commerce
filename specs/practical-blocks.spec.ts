import type { Page } from '@playwright/test';
import { expect, test } from '@wordpress/e2e-test-utils-playwright';

function watchBrowserProblems( page: Page ) {
	const problems: string[] = [];

	page.on( 'console', ( message ) => {
		const isWordPressCoreInteractivityDeprecation = message
			.text()
			.includes( 'data-wp-init--mark-as-hydrated' );
		const isWooCoreDependencyDetectorWarning = message
			.text()
			.includes(
				'An inline or unknown script accessed wc.wcBlocksData without proper dependency declaration'
			);
		const location = message.location();
		const source = location.url
			? ` (${ location.url }:${ location.lineNumber })`
			: '';

		if (
			[ 'error', 'warning' ].includes( message.type() ) &&
			! isWordPressCoreInteractivityDeprecation &&
			! isWooCoreDependencyDetectorWarning
		) {
			problems.push(
				`${ message.type() }: ${ message.text() }${ source }`
			);
		}
	} );
	page.on( 'pageerror', ( error ) => {
		problems.push( `pageerror: ${ error.message }` );
	} );
	page.on( 'response', ( response ) => {
		if ( response.status() >= 400 ) {
			problems.push( `http ${ response.status() }: ${ response.url() }` );
		}
	} );

	return problems;
}

async function expectNoPageOverflow( page: Page ) {
	const hasHorizontalOverflow = await page.evaluate(
		() => document.documentElement.scrollWidth > window.innerWidth
	);
	expect( hasHorizontalOverflow ).toBe( false );
}

async function getDemoProductIds( page: Page ) {
	const response = await page.request.get(
		'/wp-json/wc/store/v1/products?per_page=100'
	);
	expect( response.ok() ).toBe( true );
	const products = ( await response.json() ) as Array< {
		id: number;
		sku: string;
	} >;
	const skus = [ 'LWC-DEMO-START', 'LWC-DEMO-GROW', 'LWC-DEMO-SCALE' ];
	const ids = skus.map(
		( sku ) => products.find( ( product ) => product.sku === sku )?.id || 0
	);
	expect( ids ).not.toContain( 0 );
	return ids;
}

async function addProductThroughStoreApi( page: Page, productId: number ) {
	await page.evaluate( async ( id ) => {
		const cartResponse = await window.fetch( '/wp-json/wc/store/v1/cart' );
		const nonce = cartResponse.headers.get( 'Nonce' ) || '';
		const addResponse = await window.fetch(
			'/wp-json/wc/store/v1/cart/add-item',
			{
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					Nonce: nonce,
				},
				body: JSON.stringify( { id, quantity: 1 } ),
			}
		);

		if ( ! addResponse.ok ) {
			throw new Error( `Store API add failed: ${ addResponse.status }` );
		}

		const cart = await addResponse.json();
		if ( ! cart.extensions?.[ 'lineweb-commerce' ] ) {
			throw new Error(
				'Lineweb Commerce Cart API extension is missing.'
			);
		}

		document.body.dispatchEvent(
			new CustomEvent( 'wc-blocks_added_to_cart' )
		);
	}, productId );
}

async function emptyCartThroughStoreApi( page: Page ) {
	await page.evaluate( async () => {
		let cartResponse = await window.fetch( '/wp-json/wc/store/v1/cart' );
		let cart = await cartResponse.json();
		let nonce = cartResponse.headers.get( 'Nonce' ) || '';

		for ( const item of cart.items || [] ) {
			cartResponse = await window.fetch(
				'/wp-json/wc/store/v1/cart/remove-item',
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						Nonce: nonce,
					},
					body: JSON.stringify( { key: item.key } ),
				}
			);

			if ( ! cartResponse.ok ) {
				throw new Error(
					`Store API remove failed: ${ cartResponse.status }`
				);
			}

			nonce = cartResponse.headers.get( 'Nonce' ) || nonce;
			cart = await cartResponse.json();
		}

		document.body.dispatchEvent(
			new CustomEvent( 'wc-blocks_removed_from_cart' )
		);
	} );
}

test( 'publishes useful specifications and a stock-aware delivery estimate', async ( {
	admin,
	editor,
	page,
} ) => {
	const browserProblems = watchBrowserProblems( page );
	const [ starterId ] = await getDemoProductIds( page );

	await admin.createNewPost( {
		postType: 'page',
		title: 'Product information blocks end-to-end test',
		showWelcomeGuide: false,
	} );
	await editor.insertBlock( {
		name: 'lineweb-commerce/product-specifications',
		attributes: { align: 'wide', productId: starterId },
	} );
	await editor.insertBlock( {
		name: 'lineweb-commerce/delivery-estimate',
		attributes: { align: 'wide', productId: starterId },
	} );

	const editorSpecifications = editor.canvas.locator(
		'.wp-block-lineweb-commerce-product-specifications'
	);
	const editorDelivery = editor.canvas.locator(
		'.wp-block-lineweb-commerce-delivery-estimate'
	);
	await expect( editorSpecifications ).toBeVisible();
	await expect( editorSpecifications ).toContainText( 'LWC-DEMO-START' );
	await expect( editorSpecifications ).toContainText( 'Recycled aluminium' );
	await expect( editorDelivery ).toBeVisible();
	await expect(
		editorDelivery.locator( '.lwc-delivery__window' )
	).not.toBeEmpty();

	const postId = await editor.publishPost();
	await page.goto( `/?p=${ postId }` );

	const specifications = page.locator(
		'.wp-block-lineweb-commerce-product-specifications'
	);
	const delivery = page.locator(
		'.wp-block-lineweb-commerce-delivery-estimate'
	);
	await expect( specifications ).toBeVisible();
	await expect( specifications.locator( 'dt' ) ).toContainText( [
		'SKU',
		'Availability',
		'Weight',
		'Dimensions',
		'Material',
		'Best for',
		'Setup time',
	] );
	await expect( specifications ).toContainText( 'LWC-DEMO-START' );
	await expect( specifications ).toContainText( 'Recycled aluminium' );
	await expect( delivery ).toBeVisible();
	await expect( delivery.locator( 'time' ) ).toHaveCount( 2 );
	await expect( delivery ).toContainText(
		'Estimate based on current availability'
	);

	await page.screenshot( {
		path: 'artifacts-commerce/practical-blocks-desktop.png',
		fullPage: true,
	} );
	await page.setViewportSize( { width: 375, height: 812 } );
	await expectNoPageOverflow( page );
	await page.screenshot( {
		path: 'artifacts-commerce/practical-blocks-mobile.png',
		fullPage: true,
	} );

	expect( browserProblems ).toEqual( [] );
} );

test( 'publishes a live product comparison and filters identical rows', async ( {
	admin,
	editor,
	page,
} ) => {
	const browserProblems = watchBrowserProblems( page );
	const productIds = await getDemoProductIds( page );

	await admin.createNewPost( {
		postType: 'page',
		title: 'Product comparison end-to-end test',
		showWelcomeGuide: false,
	} );
	await editor.insertBlock( {
		name: 'lineweb-commerce/product-comparison',
		attributes: {
			align: 'wide',
			productIds,
			selectedAttributes: [ 'material', 'best-for' ],
		},
	} );

	const editorComparison = editor.canvas.locator(
		'.wp-block-lineweb-commerce-product-comparison'
	);
	await expect( editorComparison ).toBeVisible();
	await expect( editorComparison.locator( 'thead th' ) ).toHaveCount( 4 );
	await expect( editorComparison.locator( 'tbody tr' ) ).toHaveCount( 5 );
	await expect( editorComparison ).toContainText( 'Lineweb Starter Kit' );
	await expect( editorComparison ).toContainText( 'Powder-coated steel' );

	const postId = await editor.publishPost();
	await page.goto( `/?p=${ postId }` );
	const comparison = page.locator(
		'.wp-block-lineweb-commerce-product-comparison'
	);
	const differencesButton = comparison.getByRole( 'button', {
		name: 'Show differences only',
	} );

	await expect( comparison ).toBeVisible();
	await expect( comparison.getByRole( 'table' ) ).toBeVisible();
	await expect( comparison.locator( 'thead th' ) ).toHaveCount( 4 );
	await expect( comparison.locator( 'tbody tr' ) ).toHaveCount( 5 );
	await expect( comparison.locator( 'thead a' ) ).toHaveCount( 3 );
	await expect( comparison.locator( '[data-different="0"]' ) ).toHaveCount(
		1
	);

	await differencesButton.click();
	await expect( differencesButton ).toHaveAttribute( 'aria-pressed', 'true' );
	await expect( comparison.locator( '[data-different="0"]' ) ).toBeHidden();

	await page.setViewportSize( { width: 375, height: 812 } );
	const toggleBox = await differencesButton.boundingBox();
	expect( toggleBox?.height ).toBeGreaterThanOrEqual( 44 );
	await expectNoPageOverflow( page );

	expect( browserProblems ).toEqual( [] );
} );

test( 'updates specifications and delivery for classic and block variation events', async ( {
	page,
} ) => {
	const browserProblems = watchBrowserProblems( page );
	await page.goto( '/product/lineweb-variable-kit/' );

	const variationSelect = page.locator( 'select[name="attribute_plan"]' );
	const specifications = page.locator(
		'.wp-block-lineweb-commerce-product-specifications'
	);
	const delivery = page.locator( '.lwc-auto-placement--product-delivery' );
	const specificationValue = ( key: string ) =>
		specifications.locator( `[data-lwc-spec-key="${ key }"] dd` );

	await expect( variationSelect ).toBeVisible();
	await expect( specifications ).toBeVisible();
	await expect( delivery ).toBeVisible();
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VARIABLE'
	);

	await variationSelect.selectOption( { label: 'Physical' } );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VAR-PHYSICAL'
	);
	await expect( specificationValue( 'weight' ) ).toContainText( '1.5' );
	await expect( delivery ).toBeVisible();
	await expect( delivery.locator( 'time' ) ).toHaveCount( 2 );

	await variationSelect.selectOption( { label: 'Backorder' } );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VAR-BACKORDER'
	);
	await expect( delivery.locator( '.lwc-delivery__window' ) ).toHaveText(
		'Available on backorder. Delivery may take longer.'
	);

	await variationSelect.selectOption( { label: 'Virtual' } );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VAR-VIRTUAL'
	);
	await expect( specificationValue( 'weight' ) ).toHaveText(
		'Not available'
	);
	await expect( delivery ).toBeHidden();

	await variationSelect.selectOption( '' );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VARIABLE'
	);
	await expect( specificationValue( 'weight' ) ).toContainText( '2' );
	await expect( delivery ).toBeVisible();

	await page.evaluate( () => {
		document.dispatchEvent(
			new CustomEvent( 'wc-blocks_product_variation_changed', {
				detail: {
					variation: {
						sku: 'BLOCK-VARIATION-SKU',
						availability_html: '<p>Available</p>',
						weight_html: '<span>3 kg</span>',
						dimensions_html: '<span>50 × 40 × 20 cm</span>',
						is_virtual: true,
						is_in_stock: true,
					},
				},
			} )
		);
	} );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'BLOCK-VARIATION-SKU'
	);
	await expect( delivery ).toBeHidden();

	await page.evaluate( () => {
		document.dispatchEvent(
			new CustomEvent( 'wc-blocks_product_variation_changed', {
				detail: { variation: null },
			} )
		);
	} );
	await expect( specificationValue( 'sku' ) ).toHaveText(
		'LWC-DEMO-VARIABLE'
	);
	await expect( delivery ).toBeVisible();

	expect( browserProblems ).toEqual( [] );
} );

test( 'updates free-shipping progress from the live WooCommerce cart without reload', async ( {
	admin,
	browser,
	editor,
	page,
} ) => {
	const [ starterId, , scaleId ] = await getDemoProductIds( page );

	await admin.createNewPost( {
		postType: 'page',
		title: 'Free shipping progress end-to-end test',
		showWelcomeGuide: false,
	} );
	await editor.insertBlock( {
		name: 'lineweb-commerce/free-shipping-progress',
		attributes: {
			align: 'wide',
			threshold: 200,
			hideWhenEmpty: false,
		},
	} );

	const editorProgress = editor.canvas.locator(
		'.wp-block-lineweb-commerce-free-shipping-progress'
	);
	await expect( editorProgress ).toBeVisible();
	await expect( editorProgress.getByRole( 'progressbar' ) ).toHaveAttribute(
		'aria-valuenow',
		'35'
	);

	const postId = await editor.publishPost();
	const storefrontContext = await browser.newContext( {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		storageState: { cookies: [], origins: [] },
		viewport: { width: 1440, height: 1000 },
	} );
	const storefront = await storefrontContext.newPage();
	const browserProblems = watchBrowserProblems( storefront );
	await storefront.goto( `/?p=${ postId }` );
	await emptyCartThroughStoreApi( storefront );

	const progress = storefront.locator(
		'.wp-block-lineweb-commerce-free-shipping-progress:not(.lwc-auto-placement)'
	);
	await expect( progress ).toBeVisible();
	await expect( progress.locator( '[data-lwc-status]' ) ).toHaveText(
		'Add a product to start tracking free shipping'
	);

	await addProductThroughStoreApi( storefront, starterId );

	await expect( progress.locator( '[data-lwc-status]' ) ).toContainText(
		'151.00'
	);
	await expect( progress.getByRole( 'progressbar' ) ).toHaveAttribute(
		'aria-valuenow',
		'4900'
	);

	await addProductThroughStoreApi( storefront, scaleId );

	await expect( progress.locator( '[data-lwc-status]' ) ).toHaveText(
		'Free shipping unlocked'
	);
	await expect( progress.getByRole( 'progressbar' ) ).toHaveAttribute(
		'aria-valuenow',
		'20000'
	);
	await expectNoPageOverflow( storefront );

	expect( browserProblems ).toEqual( [] );
	await storefrontContext.close();
} );

test( 'places useful blocks automatically on product, Mini-Cart, and Cart surfaces', async ( {
	browser,
	page,
} ) => {
	const [ starterId ] = await getDemoProductIds( page );
	const storefrontContext = await browser.newContext( {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		storageState: { cookies: [], origins: [] },
		viewport: { width: 1440, height: 1000 },
	} );
	const storefront = await storefrontContext.newPage();
	const browserProblems = watchBrowserProblems( storefront );

	await storefront.goto( '/' );
	await emptyCartThroughStoreApi( storefront );
	await addProductThroughStoreApi( storefront, starterId );

	const cartResponse = await storefront.request.get(
		'/wp-json/wc/store/v1/cart'
	);
	const cart = await cartResponse.json();
	expect( cart.extensions?.[ 'lineweb-commerce' ] ).toMatchObject( {
		available: true,
		threshold_minor: 20000,
	} );

	await storefront.goto( '/product/lineweb-starter-kit/' );

	const productDelivery = storefront.locator(
		'.lwc-auto-placement--product-delivery'
	);
	await expect( productDelivery ).toHaveCount( 1 );
	await expect( productDelivery ).toBeVisible();
	await expect( productDelivery.locator( 'time' ) ).toHaveCount( 2 );
	await expect( productDelivery ).toContainText( 'When will it arrive?' );

	await storefront
		.getByRole( 'button', { name: /Number of items in the cart:/ } )
		.click();

	const miniCartProgress = storefront.locator(
		'.lwc-auto-placement--mini-cart-progress'
	);
	await expect( miniCartProgress ).toHaveCount( 1 );
	await expect( miniCartProgress ).toBeVisible();
	await expect(
		miniCartProgress.locator( '[data-lwc-status]' )
	).toContainText( '151.00' );
	await expect( miniCartProgress.getByRole( 'progressbar' ) ).toHaveAttribute(
		'aria-valuemax',
		'20000'
	);

	await storefront.goto( '/cart/' );

	const cartProgress = storefront.locator(
		'.wp-block-woocommerce-cart-totals-block > .lwc-auto-placement--cart-progress'
	);
	await expect(
		storefront.locator( '.wc-block-cart-item__product' ).filter( {
			hasText: 'Lineweb Starter Kit',
		} )
	).toBeVisible();
	await expect( cartProgress ).toHaveCount( 1 );
	await expect( cartProgress ).toBeVisible();
	await expect( cartProgress.locator( '[data-lwc-status]' ) ).toContainText(
		'151.00'
	);
	await expect( cartProgress.getByRole( 'progressbar' ) ).toHaveAttribute(
		'aria-valuenow',
		'4900'
	);
	await expect(
		storefront.getByRole( 'link', { name: 'Proceed to Checkout' } )
	).toBeVisible();

	await storefront.screenshot( {
		path: 'artifacts-commerce/automatic-placements-cart.png',
		fullPage: true,
	} );
	await storefront.setViewportSize( { width: 375, height: 812 } );
	await expectNoPageOverflow( storefront );

	expect( browserProblems ).toEqual( [] );
	await storefrontContext.close();
} );

test( 'fails delivery placement closed until the merchant confirms the profile', async ( {
	browser,
	page,
} ) => {
	await page.goto(
		'/wp-admin/admin.php?page=wc-settings&tab=products&section=lineweb-commerce'
	);

	await expect(
		page.getByRole( 'heading', { name: 'Automatic placements' } )
	).toBeVisible();
	await expect(
		page.locator( '#lineweb_commerce_auto_product_delivery' )
	).toBeChecked();
	await expect(
		page.locator( '#lineweb_commerce_auto_cart_progress' )
	).toBeChecked();
	await expect(
		page.locator( '#lineweb_commerce_auto_mini_cart_progress' )
	).toBeChecked();

	const profileConfirmation = page.locator(
		'#lineweb_commerce_delivery_profile_confirmed'
	);
	await expect( profileConfirmation ).toBeChecked();
	await profileConfirmation.uncheck();
	await page.getByRole( 'button', { name: 'Save changes' } ).click();
	await expect( profileConfirmation ).not.toBeChecked();

	const storefrontContext = await browser.newContext( {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		storageState: { cookies: [], origins: [] },
	} );
	const storefront = await storefrontContext.newPage();
	const browserProblems = watchBrowserProblems( storefront );
	await storefront.goto( '/product/lineweb-starter-kit/' );
	await expect(
		storefront.locator( '.lwc-auto-placement--product-delivery' )
	).toHaveCount( 0 );

	await profileConfirmation.check();
	await page.getByRole( 'button', { name: 'Save changes' } ).click();
	await expect( profileConfirmation ).toBeChecked();
	await storefront.reload();
	await expect(
		storefront.locator( '.lwc-auto-placement--product-delivery' )
	).toHaveCount( 1 );

	expect( browserProblems ).toEqual( [] );
	await storefrontContext.close();
} );
