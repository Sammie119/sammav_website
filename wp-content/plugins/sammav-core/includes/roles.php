<?php
/**
 * Roles (FR-M4). All Sammav content types use the standard "post" capabilities,
 * so Editors can manage projects, services, testimonials, team and enquiries
 * out of the box. Editors also get menu editing; Administrators keep plugins,
 * themes, users and settings.
 */

defined( 'ABSPATH' ) || exit;

function sammav_grant_editor_caps(): void {
	$editor = get_role( 'editor' );
	if ( $editor && ! $editor->has_cap( 'edit_theme_options' ) ) {
		$editor->add_cap( 'edit_theme_options' );
	}
}

/**
 * edit_theme_options also opens Customizer/Site Editor menus; hide the parts Editors
 * should not change, keeping Appearance → Menus / Navigation.
 */
add_action(
	'admin_menu',
	static function () {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'themes.php', 'site-editor.php?path=/wp_global_styles' );
	},
	999
);
