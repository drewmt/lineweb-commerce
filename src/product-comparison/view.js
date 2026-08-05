document.querySelectorAll( '[data-lwc-comparison]' ).forEach( ( root ) => {
	const button = root.querySelector( '[data-lwc-comparison-toggle]' );
	if ( ! button ) {
		return;
	}
	button.addEventListener( 'click', () => {
		const pressed = button.getAttribute( 'aria-pressed' ) !== 'true';
		button.setAttribute( 'aria-pressed', String( pressed ) );
		root.querySelectorAll( '[data-lwc-comparison-row]' ).forEach(
			( row ) => {
				row.hidden = pressed && row.dataset.different !== '1';
			}
		);
	} );
} );
