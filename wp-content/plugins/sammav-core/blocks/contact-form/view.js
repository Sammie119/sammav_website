/* Contact form: inline validation and submission without a page reload. */
document.querySelectorAll( '.sv-contact-form' ).forEach( function ( root ) {
	const form = root.querySelector( '.sv-form' );
	const status = root.querySelector( '.sv-form-status' );
	if ( ! form || ! window.fetch ) {
		return;
	}

	const messages = {
		sv_name: 'Please enter your full name.',
		sv_email: 'Please enter a valid email address.',
		sv_phone: 'Please enter a valid phone number.',
		sv_message: 'Please tell us a little more (at least 10 characters).',
		sv_consent: 'Please agree so we can respond to your enquiry.',
	};

	function setError( name, text ) {
		const input = form.elements[ name ];
		if ( ! input ) {
			return;
		}
		const wrap = input.closest( '.sv-field' );
		const error = wrap && wrap.querySelector( '.sv-field__error' );
		if ( wrap ) {
			wrap.classList.toggle( 'has-error', !! text );
		}
		if ( error ) {
			error.textContent = text || '';
		}
		if ( text ) {
			input.setAttribute( 'aria-invalid', 'true' );
		} else {
			input.removeAttribute( 'aria-invalid' );
		}
	}

	function validate() {
		const errors = {};
		const v = ( n ) => ( form.elements[ n ].value || '' ).trim();
		if ( v( 'sv_name' ).length < 2 ) {
			errors.sv_name = messages.sv_name;
		}
		if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( v( 'sv_email' ) ) ) {
			errors.sv_email = messages.sv_email;
		}
		if ( v( 'sv_phone' ) && ! /^[0-9 +()\-]{7,20}$/.test( v( 'sv_phone' ) ) ) {
			errors.sv_phone = messages.sv_phone;
		}
		if ( v( 'sv_message' ).length < 10 ) {
			errors.sv_message = messages.sv_message;
		}
		if ( ! form.elements.sv_consent.checked ) {
			errors.sv_consent = messages.sv_consent;
		}
		return errors;
	}

	function showErrors( errors ) {
		Object.keys( messages ).forEach( ( name ) => setError( name, errors[ name ] ) );
		const first = Object.keys( messages ).find( ( name ) => errors[ name ] );
		if ( first ) {
			form.elements[ first ].focus();
		}
	}

	function setStatus( text, type ) {
		status.textContent = text;
		status.className = 'sv-form-status' + ( type ? ' is-' + type : '' );
	}

	// Clear a field's error as soon as it is fixed.
	form.addEventListener( 'input', function ( e ) {
		const name = e.target.name;
		if ( messages[ name ] && e.target.closest( '.has-error' ) && ! validate()[ name ] ) {
			setError( name, '' );
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		const errors = validate();
		if ( Object.keys( errors ).length ) {
			setStatus( 'Please correct the highlighted fields and try again.', 'error' );
			showErrors( errors );
			return;
		}

		const button = form.querySelector( '[type="submit"]' );
		button.disabled = true;
		button.dataset.label = button.textContent;
		button.textContent = 'Sending…';
		setStatus( '', '' );

		fetch( form.dataset.endpoint, { method: 'POST', body: new FormData( form ), credentials: 'same-origin' } )
			.then( ( res ) => res.json() )
			.then( function ( data ) {
				if ( data.ok ) {
					form.reset();
					form.hidden = true;
					setStatus( data.message, 'success' );
					status.focus();
					window.dispatchEvent( new CustomEvent( 'sammav:enquiry' ) );
					return;
				}
				setStatus( data.message || 'Something went wrong. Please try again.', 'error' );
				showErrors( data.errors || {} );
				if ( window.turnstile ) {
					window.turnstile.reset();
				}
			} )
			.catch( function () {
				setStatus( 'Network error. Please check your connection and try again.', 'error' );
			} )
			.finally( function () {
				button.disabled = false;
				button.textContent = button.dataset.label;
			} );
	} );
} );
