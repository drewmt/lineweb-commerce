export function resetDelivery( root ) {
	const message = root.querySelector( '.lwc-delivery__window' );
	root.hidden = false;
	if ( message && root.dataset.lwcOriginalWindow ) {
		message.innerHTML = root.dataset.lwcOriginalWindow;
	}
}

export function updateDelivery( root, variation ) {
	const message = root.querySelector( '.lwc-delivery__window' );
	if ( ! message || ! variation ) {
		return;
	}

	if ( ! root.dataset.lwcOriginalWindow ) {
		root.dataset.lwcOriginalWindow = message.innerHTML;
	}

	const state =
		variation.lineweb_commerce ||
		variation.extensions?.[ 'lineweb-commerce' ] ||
		{};
	const isVirtual = Boolean( state.is_virtual ?? variation.is_virtual );

	root.hidden = isVirtual;
	if ( isVirtual ) {
		return;
	}
	const isInStock = state.is_in_stock ?? variation.is_in_stock;
	const isOnBackorder = Boolean(
		state.is_on_backorder ??
			variation.is_on_backorder ??
			variation.stock_status === 'onbackorder'
	);

	if ( isInStock === false ) {
		message.textContent = root.dataset.lwcOutOfStock || '';
	} else if ( isOnBackorder ) {
		message.textContent = root.dataset.lwcBackorder || '';
	} else {
		message.innerHTML = root.dataset.lwcOriginalWindow;
	}
}

function updateAll( variation ) {
	document
		.querySelectorAll( '[data-lwc-delivery]' )
		.forEach( ( root ) => updateDelivery( root, variation ) );
}

if ( globalThis.jQuery ) {
	globalThis
		.jQuery( document )
		.on( 'found_variation', ( event, variation ) =>
			updateAll( variation )
		);
	globalThis.jQuery( document ).on( 'reset_data', () => {
		document
			.querySelectorAll( '[data-lwc-delivery]' )
			.forEach( resetDelivery );
	} );
}

document.addEventListener( 'wc-blocks_product_variation_changed', ( event ) => {
	const variation =
		event.detail &&
		typeof event.detail === 'object' &&
		'variation' in event.detail
			? event.detail.variation
			: event.detail;
	if ( variation ) {
		updateAll( variation );
	} else {
		document
			.querySelectorAll( '[data-lwc-delivery]' )
			.forEach( resetDelivery );
	}
} );
