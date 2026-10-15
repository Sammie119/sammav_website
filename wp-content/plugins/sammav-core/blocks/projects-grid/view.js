/* Projects grid: category filter + load more, no page reload. */
document.querySelectorAll( '.sv-projects' ).forEach( function ( root ) {
	const perPage = parseInt( root.dataset.perPage, 10 ) || 9;
	const cards = Array.from( root.querySelectorAll( '.sv-project-card' ) );
	const filters = root.querySelectorAll( '.sv-filter' );
	const more = root.querySelector( '.sv-load-more' );
	const empty = root.querySelector( '.sv-projects__empty' );
	let filter = '*';
	let shown = perPage;

	function render() {
		const matches = cards.filter( function ( card ) {
			return filter === '*' || ( ' ' + card.dataset.categories + ' ' ).indexOf( ' ' + filter + ' ' ) !== -1;
		} );
		cards.forEach( function ( card ) {
			card.hidden = true;
		} );
		matches.slice( 0, shown ).forEach( function ( card ) {
			card.hidden = false;
		} );
		if ( more ) {
			more.hidden = matches.length <= shown;
		}
		if ( empty ) {
			empty.hidden = matches.length > 0;
		}
	}

	filters.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			filter = button.dataset.filter;
			shown = perPage;
			filters.forEach( function ( b ) {
				const active = b === button;
				b.classList.toggle( 'is-active', active );
				b.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
			render();
		} );
	} );

	if ( more ) {
		more.addEventListener( 'click', function () {
			const firstNew = shown;
			shown += perPage;
			render();
			const visible = cards.filter( ( c ) => ! c.hidden );
			const target = visible[ firstNew ] && visible[ firstNew ].querySelector( '.sv-card__title a' );
			if ( target ) {
				target.focus();
			}
		} );
	}

	render();
} );
