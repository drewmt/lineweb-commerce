import apiFetch from '@wordpress/api-fetch';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function useProductCatalog( productId ) {
	const selectedIds = useMemo(
		() =>
			( Array.isArray( productId ) ? productId : [ productId ] )
				.map( Number )
				.filter( Boolean )
				.slice( 0, 4 ),
		[ productId ]
	);
	const [ search, setSearch ] = useState( '' );
	const [ productCache, setProductCache ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		let active = true;
		const timer = window.setTimeout( async () => {
			setIsLoading( true );
			setError( '' );

			try {
				const query = new URLSearchParams( {
					per_page: '30',
					orderby: 'title',
					order: 'asc',
				} );

				if ( search.trim() ) {
					query.set( 'search', search.trim() );
				}

				const products = await apiFetch( {
					path: `/wc/store/v1/products?${ query.toString() }`,
				} );

				if ( active ) {
					setProductCache( ( current ) => {
						const next = { ...current };
						products.forEach( ( product ) => {
							next[ product.id ] = product;
						} );
						return next;
					} );
				}
			} catch ( requestError ) {
				if ( active ) {
					setError(
						requestError?.message ||
							__(
								'WooCommerce products could not be loaded.',
								'lineweb-commerce'
							)
					);
				}
			} finally {
				if ( active ) {
					setIsLoading( false );
				}
			}
		}, 180 );

		return () => {
			active = false;
			window.clearTimeout( timer );
		};
	}, [ search ] );

	useEffect( () => {
		const missingIds = selectedIds.filter( ( id ) => ! productCache[ id ] );
		if ( missingIds.length === 0 ) {
			return undefined;
		}

		let active = true;
		const query = new URLSearchParams( {
			include: missingIds.join( ',' ),
			per_page: String( missingIds.length ),
		} );
		apiFetch( { path: `/wc/store/v1/products?${ query.toString() }` } )
			.then( ( products ) => {
				if ( active ) {
					setProductCache( ( current ) => {
						const next = { ...current };
						products.forEach( ( product ) => {
							next[ product.id ] = product;
						} );
						return next;
					} );
				}
			} )
			.catch( () => {} );

		return () => {
			active = false;
		};
	}, [ selectedIds, productCache ] );

	const options = useMemo(
		() =>
			Object.values( productCache )
				.sort( ( first, second ) =>
					first.name.localeCompare( second.name )
				)
				.map( ( product ) => ( {
					label: product.name,
					value: String( product.id ),
				} ) ),
		[ productCache ]
	);

	return {
		error,
		isLoading,
		options,
		product: productCache[ selectedIds[ 0 ] ] || null,
		products: selectedIds
			.map( ( id ) => productCache[ id ] )
			.filter( Boolean ),
		search,
		setSearch,
	};
}
