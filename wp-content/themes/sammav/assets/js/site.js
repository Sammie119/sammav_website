/* Cookie consent (FR-G9): Google Analytics loads only after the visitor accepts. */
( function () {
	const KEY = 'sammav_consent';
	const banner = document.querySelector( '.sv-cookie' );
	const ga4 = ( window.sammavSite && window.sammavSite.ga4 ) || '';

	function read() {
		try {
			return window.localStorage.getItem( KEY );
		} catch ( e ) {
			return null;
		}
	}

	function write( value ) {
		try {
			window.localStorage.setItem( KEY, value );
		} catch ( e ) {}
	}

	function loadAnalytics() {
		if ( ! /^G-[A-Z0-9]+$/.test( ga4 ) || window.gtag ) {
			return;
		}
		const s = document.createElement( 'script' );
		s.async = true;
		s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ga4;
		document.head.appendChild( s );
		window.dataLayer = window.dataLayer || [];
		window.gtag = function () {
			window.dataLayer.push( arguments );
		};
		window.gtag( 'js', new Date() );
		window.gtag( 'config', ga4, { anonymize_ip: true } );
		// NFR-13: track contact form submissions as a conversion.
		window.addEventListener( 'sammav:enquiry', function () {
			window.gtag( 'event', 'generate_lead', { form: 'contact' } );
		} );
	}

	const choice = read();
	if ( choice === 'yes' ) {
		loadAnalytics();
	}

	// Only ask when there is something to consent to.
	if ( ! banner || choice || ! ga4 ) {
		return;
	}
	banner.hidden = false;
	document.body.classList.add( 'sv-has-cookie' );
	banner.addEventListener( 'click', function ( e ) {
		const button = e.target.closest( '[data-consent]' );
		if ( ! button ) {
			return;
		}
		write( button.dataset.consent );
		banner.hidden = true;
		document.body.classList.remove( 'sv-has-cookie' );
		if ( button.dataset.consent === 'yes' ) {
			loadAnalytics();
		}
	} );
} )();
