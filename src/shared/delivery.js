export function parseHolidayDates( value ) {
	return new Set(
		String( value || '' )
			.split( /[\s,]+/ )
			.filter( ( date ) => /^\d{4}-\d{2}-\d{2}$/.test( date ) )
			.slice( 0, 40 )
	);
}

export function toDateKey( date ) {
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	return `${ year }-${ month }-${ day }`;
}

export function isBusinessDay(
	date,
	skipWeekends,
	holidays,
	workingDays = null
) {
	const day = date.getDay();
	const isoDay = day === 0 ? 7 : day;
	const allowed = Array.isArray( workingDays )
		? workingDays.includes( isoDay )
		: ! skipWeekends || ( day !== 0 && day !== 6 );
	return allowed && ! holidays.has( toDateKey( date ) );
}

export function addBusinessDays(
	input,
	amount,
	skipWeekends,
	holidays,
	workingDays = null
) {
	const date = new Date( input );
	let remaining = Math.max( 0, Math.min( 60, Number( amount ) || 0 ) );

	if ( remaining === 0 ) {
		while ( ! isBusinessDay( date, skipWeekends, holidays, workingDays ) ) {
			date.setDate( date.getDate() + 1 );
		}
		return date;
	}

	while ( remaining > 0 ) {
		date.setDate( date.getDate() + 1 );
		if ( isBusinessDay( date, skipWeekends, holidays, workingDays ) ) {
			remaining -= 1;
		}
	}

	return date;
}

export function calculateDeliveryWindow( {
	minimumDays = 1,
	maximumDays = 3,
	minimumBusinessDays,
	maximumBusinessDays,
	cutoffHour = 14,
	skipWeekends = true,
	workingDays,
	holidayDates = '',
	now = new Date(),
} = {} ) {
	const current = new Date( now );
	const startOfDay = new Date( current );
	startOfDay.setHours( 0, 0, 0, 0 );
	const holidays = parseHolidayDates( holidayDates );
	const resolvedWorkingDays = Array.isArray( workingDays )
		? [
				...new Set(
					workingDays
						.map( Number )
						.filter( ( day ) => day >= 1 && day <= 7 )
				),
		  ]
		: null;
	const resolvedMinimum = minimumBusinessDays ?? minimumDays;
	const resolvedMaximum = maximumBusinessDays ?? maximumDays;
	const minimum = Math.max(
		0,
		Math.min( 30, Number( resolvedMinimum ) || 0 )
	);
	const maximum = Math.max(
		minimum,
		Math.min( 60, Number( resolvedMaximum ) || minimum )
	);
	const cutoff = Math.max( 0, Math.min( 23, Number( cutoffHour ) || 0 ) );
	const afterCutoff =
		isBusinessDay(
			startOfDay,
			skipWeekends,
			holidays,
			resolvedWorkingDays
		) && current.getHours() >= cutoff;
	const extraDay = afterCutoff ? 1 : 0;

	return {
		start: addBusinessDays(
			startOfDay,
			minimum + extraDay,
			skipWeekends,
			holidays,
			resolvedWorkingDays
		),
		end: addBusinessDays(
			startOfDay,
			maximum + extraDay,
			skipWeekends,
			holidays,
			resolvedWorkingDays
		),
	};
}
