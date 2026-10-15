/* Testimonials: previous/next buttons scroll the snap track by one card. */
document.querySelectorAll( '.sv-testimonials' ).forEach( function ( root ) {
	const track = root.querySelector( '.sv-testimonials__track' );
	const nav = root.querySelector( '.sv-testimonials__nav' );
	if ( ! track || ! nav || track.children.length < 2 ) {
		return;
	}
	nav.hidden = false;
	function step( dir ) {
		const card = track.children[ 0 ];
		const gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;
		const width = card.getBoundingClientRect().width + gap;
		const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		if ( dir > 0 && atEnd ) {
			track.scrollTo( { left: 0, behavior: 'smooth' } );
		} else {
			track.scrollBy( { left: dir * width, behavior: 'smooth' } );
		}
	}
	root.querySelector( '.sv-testimonials__prev' ).addEventListener( 'click', () => step( -1 ) );
	root.querySelector( '.sv-testimonials__next' ).addEventListener( 'click', () => step( 1 ) );
} );
