<?php
/**
 * Plugin Name:       Sammav Core
 * Description:       Content model (Projects, Services, Testimonials, Team), contact form and enquiries for the Sammav IT Services website.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Sammav IT Services
 * Text Domain:       sammav-core
 */

defined( 'ABSPATH' ) || exit;

define( 'SAMMAV_CORE_VERSION', '1.0.0' );
define( 'SAMMAV_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAMMAV_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once SAMMAV_CORE_DIR . 'includes/helpers.php';
require_once SAMMAV_CORE_DIR . 'includes/post-types.php';
require_once SAMMAV_CORE_DIR . 'includes/meta.php';
require_once SAMMAV_CORE_DIR . 'includes/blocks.php';
require_once SAMMAV_CORE_DIR . 'includes/contact.php';
require_once SAMMAV_CORE_DIR . 'includes/enquiries-admin.php';
require_once SAMMAV_CORE_DIR . 'includes/settings.php';
require_once SAMMAV_CORE_DIR . 'includes/mail.php';
require_once SAMMAV_CORE_DIR . 'includes/roles.php';

register_activation_hook(
	__FILE__,
	static function () {
		sammav_register_post_types();
		sammav_grant_editor_caps();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
