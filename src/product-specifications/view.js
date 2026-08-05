function textFromHtml( html ) {
	const template = document.createElement( 'template' );
	template.innerHTML = html || '';
	return template.content.textContent.trim();
}

function rememberOriginalValue( description ) {
	if ( ! Object.hasOwn( description.dataset, 'lwcOriginalValue' ) ) {
		description.dataset.lwcOriginalValue = description.textContent;
	}
}

export function resetSpecifications( root ) {
	root.querySelectorAll( '[data-lwc-spec-key] dd' ).forEach(
		( description ) => {
			if ( Object.hasOwn( description.dataset, 'lwcOriginalValue' ) ) {
				description.textContent = description.dataset.lwcOriginalValue;
			}
		}
	);
}

export function updateSpecifications( root, variation ) {
	if ( ! variation ) {
		resetSpecifications( root );
		return;
	}

	const emptyValue = root.dataset.lwcEmptyValue || '—';
	const commerceState =
		variation.lineweb_commerce ||
		variation.extensions?.[ 'lineweb-commerce' ] ||
		{};
	const isVirtual = Boolean(
		commerceState.is_virtual ?? variation.is_virtual
	);
	const values = {
		sku: String( variation.sku || '' ),
		availability: textFromHtml( variation.availability_html ),
		weight: isVirtual ? '' : textFromHtml( variation.weight_html ),
		dimensions: isVirtual ? '' : textFromHtml( variation.dimensions_html ),
	};

	Object.entries( values ).forEach( ( [ key, value ] ) => {
		const row = root.querySelector( `[data-lwc-spec-key="${ key }"]` );
		const description = row?.querySelector( 'dd' );
		if ( description ) {
			rememberOriginalValue( description );
			description.textContent = value || emptyValue;
		}
	} );
}

function updateAll( variation ) {
	document
		.querySelectorAll( '[data-lwc-specifications]' )
		.forEach( ( root ) => updateSpecifications( root, variation ) );
}

function resetAll() {
	document
		.querySelectorAll( '[data-lwc-specifications]' )
		.forEach( resetSpecifications );
}

if ( globalThis.jQuery ) {
	globalThis
		.jQuery( document )
		.on( 'found_variation', ( event, variation ) =>
			updateAll( variation )
		);
	globalThis.jQuery( document ).on( 'reset_data', resetAll );
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
		resetAll();
	}
} );
