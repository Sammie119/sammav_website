/* Project gallery picker in the post editor meta box. */
jQuery( function ( $ ) {
	let frame;

	$( document ).on( 'click', '.sammav-gallery-pick', function ( e ) {
		e.preventDefault();
		const $field = $( this ).closest( '.sammav-field' );
		const $input = $field.find( '.sammav-gallery-input' );
		const $preview = $field.find( '.sammav-gallery-preview' );

		frame = wp.media( {
			title: 'Project gallery',
			button: { text: 'Use these images' },
			library: { type: 'image' },
			multiple: 'add',
		} );

		frame.on( 'open', function () {
			const selection = frame.state().get( 'selection' );
			( $input.val() || '' ).split( ',' ).filter( Boolean ).forEach( function ( id ) {
				const attachment = wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		} );

		frame.on( 'select', function () {
			const items = frame.state().get( 'selection' ).toJSON();
			$input.val( items.map( ( item ) => item.id ).join( ',' ) );
			$preview.empty();
			items.forEach( function ( item ) {
				const url = ( item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail : item ).url;
				$preview.append( $( '<img>', { src: url, width: 60, height: 60, alt: '' } ) );
			} );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.sammav-gallery-clear', function ( e ) {
		e.preventDefault();
		const $field = $( this ).closest( '.sammav-field' );
		$field.find( '.sammav-gallery-input' ).val( '' );
		$field.find( '.sammav-gallery-preview' ).empty();
	} );
} );
