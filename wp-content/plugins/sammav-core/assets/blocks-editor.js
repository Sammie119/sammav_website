/* Editor registration for Sammav dynamic blocks: previews come from render.php. */
( function ( wp ) {
	const { registerBlockType, getBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const ServerSideRender = wp.serverSideRender;
	const { useBlockProps } = wp.blockEditor;
	const { Disabled } = wp.components;

	( window.sammavBlocks || [] ).forEach( function ( name ) {
		const type = getBlockType( name );
		if ( type && type.edit && type.edit.sammav ) {
			return;
		}
		const edit = function ( props ) {
			return el(
				'div',
				useBlockProps(),
				el( Disabled, null, el( ServerSideRender, { block: name, attributes: props.attributes } ) )
			);
		};
		edit.sammav = true;
		registerBlockType( name, { edit, save: () => null } );
	} );
} )( window.wp );
