/* Accessible gallery lightbox: Esc closes, arrows navigate, focus returns to the thumbnail. */
( function () {
	const links = Array.from( document.querySelectorAll( '.sv-lightbox-link' ) );
	if ( ! links.length ) {
		return;
	}

	const dialog = document.createElement( 'dialog' );
	dialog.className = 'sv-lightbox';
	dialog.setAttribute( 'aria-label', 'Image viewer' );
	dialog.innerHTML =
		'<figure><img alt=""><figcaption></figcaption></figure>' +
		'<button type="button" class="sv-lightbox__close" aria-label="Close">&times;</button>' +
		'<button type="button" class="sv-lightbox__prev" aria-label="Previous image">&#8249;</button>' +
		'<button type="button" class="sv-lightbox__next" aria-label="Next image">&#8250;</button>';
	document.body.appendChild( dialog );

	const img = dialog.querySelector( 'img' );
	const caption = dialog.querySelector( 'figcaption' );
	let index = 0;

	function show( i ) {
		index = ( i + links.length ) % links.length;
		const link = links[ index ];
		const thumb = link.querySelector( 'img' );
		img.src = link.href;
		img.alt = ( thumb && thumb.alt ) || link.dataset.caption || '';
		caption.textContent = link.dataset.caption || '';
	}

	links.forEach( function ( link, i ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			show( i );
			dialog.showModal();
		} );
	} );

	dialog.querySelector( '.sv-lightbox__close' ).addEventListener( 'click', () => dialog.close() );
	dialog.querySelector( '.sv-lightbox__prev' ).addEventListener( 'click', () => show( index - 1 ) );
	dialog.querySelector( '.sv-lightbox__next' ).addEventListener( 'click', () => show( index + 1 ) );
	dialog.addEventListener( 'click', function ( e ) {
		if ( e.target === dialog ) {
			dialog.close();
		}
	} );
	dialog.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'ArrowLeft' ) {
			show( index - 1 );
		} else if ( e.key === 'ArrowRight' ) {
			show( index + 1 );
		}
	} );
	dialog.addEventListener( 'close', function () {
		links[ index ].focus();
	} );
} )();
