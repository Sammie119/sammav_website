<?php
/**
 * Outgoing mail.
 *
 * Local: SAMMAV_SMTP_HOST / SAMMAV_SMTP_PORT (set in docker-compose) route mail to Mailpit.
 * Production: define the same constants plus SAMMAV_SMTP_USER / SAMMAV_SMTP_PASS /
 * SAMMAV_SMTP_SECURE in wp-config.php, or install WP Mail SMTP instead.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		if ( ! defined( 'SAMMAV_SMTP_HOST' ) || ! SAMMAV_SMTP_HOST ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host = SAMMAV_SMTP_HOST;
		$phpmailer->Port = defined( 'SAMMAV_SMTP_PORT' ) ? (int) SAMMAV_SMTP_PORT : 587;
		if ( defined( 'SAMMAV_SMTP_USER' ) && SAMMAV_SMTP_USER ) {
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = SAMMAV_SMTP_USER;
			$phpmailer->Password   = defined( 'SAMMAV_SMTP_PASS' ) ? SAMMAV_SMTP_PASS : '';
			$phpmailer->SMTPSecure = defined( 'SAMMAV_SMTP_SECURE' ) ? SAMMAV_SMTP_SECURE : 'tls';
		} else {
			$phpmailer->SMTPAuth    = false;
			$phpmailer->SMTPAutoTLS = false;
		}
	}
);

add_filter(
	'wp_mail_from',
	static function ( string $from ) {
		$email = sammav_setting( 'email' );
		return is_email( $email ) ? $email : $from;
	}
);

add_filter( 'wp_mail_from_name', static fn() => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
