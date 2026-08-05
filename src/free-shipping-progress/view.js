import {
	calculateShippingProgress,
	calculateStoreSubtotal,
	messageWithAmount,
} from '../shared/free-shipping';

function formatAmount( amount, root, totals = {} ) {
	const minorUnit = Number(
		totals.currency_minor_unit ?? root.dataset.lwcMinorUnit ?? 2
	);
	const currency = totals.currency_code || root.dataset.lwcCurrency || 'EUR';

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency,
			minimumFractionDigits: minorUnit,
			maximumFractionDigits: minorUnit,
		} ).format( amount / 10 ** minorUnit );
	} catch {
		return ( amount / 10 ** minorUnit ).toFixed( minorUnit );
	}
}

function updateRoot( root, cart = null ) {
	if ( ! cart ) {
		return;
	}

	const totals = cart?.totals || {};
	const automaticConfig = cart.extensions?.[ 'lineweb-commerce' ];
	const isAutomatic = root.dataset.lwcMode === 'automatic';

	if ( isAutomatic && ! automaticConfig?.available ) {
		root.hidden = true;
		return;
	}

	const threshold = isAutomatic
		? Number( automaticConfig.threshold_minor ) || 0
		: Number( root.dataset.lwcThreshold ) || 0;
	const ignoreDiscounts = isAutomatic
		? Boolean( automaticConfig.ignore_discounts )
		: root.dataset.lwcIgnore === '1';
	const includeTax = isAutomatic
		? Boolean( automaticConfig.include_tax )
		: root.dataset.lwcIncludeTax === '1';
	const current = calculateStoreSubtotal(
		totals,
		ignoreDiscounts,
		includeTax
	);
	const itemCount =
		Number( cart.items_count ?? cart.itemsCount ) ||
		( cart.items || [] ).reduce(
			( count, item ) => count + ( Number( item.quantity ) || 0 ),
			0
		);
	const isEmpty = itemCount === 0;
	const progress = calculateShippingProgress( current, threshold );
	const status = root.querySelector( '[data-lwc-status]' );
	const values = root.querySelector( '[data-lwc-values]' );
	const track = root.querySelector( '[role="progressbar"]' );
	const fill = track?.querySelector( 'span' );

	root.hidden = isEmpty && root.dataset.lwcHideEmpty === '1';

	if ( status ) {
		if ( isEmpty ) {
			status.textContent = root.dataset.lwcEmpty || '';
		} else if ( progress.unlocked ) {
			status.textContent = root.dataset.lwcSuccess || '';
		} else {
			status.textContent = messageWithAmount(
				root.dataset.lwcMessage,
				formatAmount( progress.remaining, root, totals )
			);
		}
	}

	if ( values ) {
		values.textContent = `${ formatAmount(
			current,
			root,
			totals
		) } / ${ formatAmount( threshold, root, totals ) }`;
	}

	if ( track ) {
		const maximum = Math.max( 1, threshold );
		track.setAttribute( 'aria-valuemax', String( maximum ) );
		track.setAttribute(
			'aria-valuenow',
			String( Math.min( maximum, current ) )
		);
	}

	if ( fill ) {
		fill.style.width = `${ progress.percentage }%`;
	}
}

function boot() {
	const roots = [
		...document.querySelectorAll(
			'.wp-block-lineweb-commerce-free-shipping-progress'
		),
	];

	if ( roots.length === 0 ) {
		return;
	}

	let refreshSequence = 0;
	const endpoint = roots[ 0 ].dataset.lwcEndpoint;

	if ( ! endpoint ) {
		return;
	}

	const refreshAll = async () => {
		const sequence = ++refreshSequence;

		try {
			const response = await window.fetch( endpoint, {
				credentials: 'same-origin',
				headers: { Accept: 'application/json' },
			} );

			if ( ! response.ok || sequence !== refreshSequence ) {
				return;
			}

			const cart = await response.json();
			if ( sequence === refreshSequence ) {
				roots.forEach( ( root ) => updateRoot( root, cart ) );
			}
		} catch {
			// Preserve the server-rendered state when the Store API is unavailable.
		}
	};

	refreshAll();

	[ 'wc-blocks_added_to_cart', 'wc-blocks_removed_from_cart' ].forEach(
		( eventName ) => document.body.addEventListener( eventName, refreshAll )
	);

	if ( window.jQuery ) {
		window
			.jQuery( document.body )
			.on(
				'added_to_cart removed_from_cart updated_cart_totals applied_coupon removed_coupon',
				refreshAll
			);
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}
