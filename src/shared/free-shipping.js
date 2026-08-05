export function calculateStoreSubtotal(
	totals = {},
	ignoreDiscounts = false,
	includeTax = false
) {
	let subtotal = Number( totals.total_items ) || 0;

	if ( includeTax ) {
		subtotal += Number( totals.total_items_tax ) || 0;
	}

	if ( ! ignoreDiscounts ) {
		subtotal -= Number( totals.total_discount ) || 0;
		if ( includeTax ) {
			subtotal -= Number( totals.total_discount_tax ) || 0;
		}
	}

	return Math.max( 0, Math.round( subtotal ) );
}

export function calculateShippingProgress( subtotal, threshold ) {
	const current = Math.max( 0, Number( subtotal ) || 0 );
	const goal = Math.max( 0, Number( threshold ) || 0 );
	const unlocked = goal === 0 || current >= goal;

	return {
		current,
		goal,
		remaining: unlocked ? 0 : goal - current,
		percentage: unlocked
			? 100
			: Math.max( 0, Math.min( 100, ( current / goal ) * 100 ) ),
		unlocked,
	};
}

export function messageWithAmount( template, formattedAmount ) {
	return String( template || '' ).replaceAll( '{amount}', formattedAmount );
}
