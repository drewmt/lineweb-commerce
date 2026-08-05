import { expect, test } from '@wordpress/e2e-test-utils-playwright';

test( 'provides a useful responsive Commerce Suite administration home', async ( {
	page,
} ) => {
	await page.goto( '/wp-admin/admin.php?page=lineweb-commerce' );

	await expect(
		page.getByRole( 'heading', {
			name: 'Useful WooCommerce clarity, placed where it matters.',
		} )
	).toBeVisible();
	await expect(
		page.locator( '.lineweb-suite-admin__feature-card' )
	).toHaveCount( 4 );
	const brandLogo = page.getByRole( 'img', {
		name: 'Lineweb — Creative Digital Agency',
	} );
	await expect( brandLogo ).toBeVisible();
	await expect( brandLogo ).toHaveAttribute( 'src', /lineweb-logo\.png/ );
	const heroCenterDelta = await page
		.locator( '.lineweb-suite-admin__brand-card' )
		.evaluate( ( card ) => {
			const logo = card.querySelector(
				'.lineweb-suite-admin__brand-mark'
			);
			if ( ! logo ) {
				return Number.POSITIVE_INFINITY;
			}
			const cardBox = card.getBoundingClientRect();
			const logoBox = logo.getBoundingClientRect();
			return Math.abs(
				cardBox.left +
					cardBox.width / 2 -
					( logoBox.left + logoBox.width / 2 )
			);
		} );
	expect( heroCenterDelta ).toBeLessThanOrEqual( 1 );
	const footerCenterDelta = await page
		.locator( '.lineweb-suite-admin__support' )
		.evaluate( ( support ) => {
			const logo = support.querySelector( 'img' );
			if ( ! logo ) {
				return Number.POSITIVE_INFINITY;
			}
			const supportBox = support.getBoundingClientRect();
			const logoBox = logo.getBoundingClientRect();
			return Math.abs(
				supportBox.left +
					supportBox.width / 2 -
					( logoBox.left + logoBox.width / 2 )
			);
		} );
	expect( footerCenterDelta ).toBeLessThanOrEqual( 1 );
	await expect(
		page.getByRole( 'link', { name: 'Placement settings' } )
	).toHaveAttribute(
		'href',
		/admin\.php\?page=wc-settings&tab=products&section=lineweb-commerce/
	);
	await expect(
		page
			.locator( '#automatic-placements ' )
			.locator(
				'.lineweb-suite-admin__placement .lineweb-suite-admin__badge'
			)
	).toHaveText( [ 'Enabled', 'Enabled', 'Enabled' ] );

	await page.screenshot( {
		path: 'artifacts-commerce/commerce-admin-home.png',
		fullPage: true,
	} );

	await page.setViewportSize( { width: 375, height: 812 } );
	const hasHorizontalOverflow = await page.evaluate(
		() => document.documentElement.scrollWidth > window.innerWidth
	);
	expect( hasHorizontalOverflow ).toBe( false );

	await page.goto( '/wp-admin/plugins.php' );
	const pluginRow = page.getByRole( 'row', {
		name: /Lineweb Commerce Suite.*Explore suite/,
	} );
	await expect(
		pluginRow.getByRole( 'link', { name: 'Explore suite' } )
	).toHaveAttribute( 'href', /page=lineweb-commerce/ );
	await expect(
		pluginRow.getByRole( 'link', { name: 'Settings' } )
	).toHaveAttribute( 'href', /section=lineweb-commerce/ );
} );
