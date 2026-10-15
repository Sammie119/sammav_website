<?php
/**
 * Enquiries in the dashboard: list columns, read-only detail view and CSV export (FR-C5).
 */

defined( 'ABSPATH' ) || exit;

function sammav_enquiry_fields(): array {
	return array(
		'name'         => __( 'Name', 'sammav-core' ),
		'email'        => __( 'Email', 'sammav-core' ),
		'phone'        => __( 'Phone', 'sammav-core' ),
		'company'      => __( 'Company', 'sammav-core' ),
		'service'      => __( 'Service', 'sammav-core' ),
		'budget_label' => __( 'Budget', 'sammav-core' ),
		'message'      => __( 'Message', 'sammav-core' ),
		'consent'      => __( 'Consent given', 'sammav-core' ),
	);
}

add_filter(
	'manage_sv_enquiry_posts_columns',
	static fn() => array(
		'cb'      => '<input type="checkbox">',
		'title'   => __( 'Enquiry', 'sammav-core' ),
		'email'   => __( 'Email', 'sammav-core' ),
		'phone'   => __( 'Phone', 'sammav-core' ),
		'service' => __( 'Service', 'sammav-core' ),
		'date'    => __( 'Received', 'sammav-core' ),
	)
);

add_action(
	'manage_sv_enquiry_posts_custom_column',
	static function ( string $column, int $post_id ) {
		$value = (string) get_post_meta( $post_id, 'sv_enquiry_' . $column, true );
		if ( 'email' === $column && $value ) {
			printf( '<a href="mailto:%1$s">%1$s</a>', esc_html( $value ) );
			return;
		}
		echo esc_html( $value );
	},
	10,
	2
);

add_filter(
	'post_row_actions',
	static function ( array $actions, WP_Post $post ) {
		if ( 'sv_enquiry' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
			if ( isset( $actions['edit'] ) ) {
				$actions['edit'] = str_replace( __( 'Edit' ), __( 'View', 'sammav-core' ), $actions['edit'] );
			}
		}
		return $actions;
	},
	10,
	2
);

add_action(
	'add_meta_boxes_sv_enquiry',
	static function () {
		remove_meta_box( 'submitdiv', 'sv_enquiry', 'side' );
		add_meta_box(
			'sammav_enquiry',
			__( 'Enquiry details', 'sammav-core' ),
			static function ( WP_Post $post ) {
				echo '<table class="form-table" role="presentation"><tbody>';
				foreach ( sammav_enquiry_fields() as $key => $label ) {
					$value = (string) get_post_meta( $post->ID, 'sv_enquiry_' . $key, true );
					echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
					if ( 'email' === $key && $value ) {
						printf( '<a href="mailto:%1$s">%1$s</a>', esc_html( $value ) );
					} else {
						echo nl2br( esc_html( '' !== $value ? $value : '—' ) );
					}
					echo '</td></tr>';
				}
				echo '<tr><th scope="row">' . esc_html__( 'Received', 'sammav-core' ) . '</th><td>' . esc_html( get_the_date( 'j F Y, H:i', $post ) ) . '</td></tr>';
				echo '</tbody></table>';
				printf(
					'<p><a class="button button-primary" href="mailto:%s">%s</a> <a class="button-link-delete" href="%s">%s</a></p>',
					esc_attr( (string) get_post_meta( $post->ID, 'sv_enquiry_email', true ) ),
					esc_html__( 'Reply by email', 'sammav-core' ),
					esc_url( get_delete_post_link( $post->ID, '', true ) ),
					esc_html__( 'Delete permanently', 'sammav-core' )
				);
			},
			'sv_enquiry',
			'normal',
			'high'
		);
	}
);

// Title is not editable for enquiries.
add_action(
	'admin_head',
	static function () {
		$screen = get_current_screen();
		if ( $screen && 'sv_enquiry' === $screen->post_type && 'post' === $screen->base ) {
			echo '<style>#titlediv input#title{pointer-events:none;background:transparent;border:0;box-shadow:none}</style>';
		}
	}
);

// Export button above the list.
add_action(
	'restrict_manage_posts',
	static function ( string $post_type ) {
		if ( 'sv_enquiry' !== $post_type ) {
			return;
		}
		printf(
			'<a class="button" href="%s">%s</a>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sammav_export_enquiries' ), 'sammav_export' ) ),
			esc_html__( 'Export CSV', 'sammav-core' )
		);
	}
);

add_action(
	'admin_post_sammav_export_enquiries',
	static function () {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'sammav_export' ) ) {
			wp_die( esc_html__( 'You are not allowed to export enquiries.', 'sammav-core' ), 403 );
		}
		$ids = get_posts(
			array(
				'post_type'      => 'sv_enquiry',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sammav-enquiries-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel reads accents correctly.
		fputcsv( $out, array_merge( array( __( 'Received', 'sammav-core' ) ), array_values( sammav_enquiry_fields() ) ) );
		foreach ( $ids as $id ) {
			$row = array( get_the_date( 'Y-m-d H:i', $id ) );
			foreach ( array_keys( sammav_enquiry_fields() ) as $key ) {
				$cell = (string) get_post_meta( $id, 'sv_enquiry_' . $key, true );
				// Neutralise spreadsheet formula injection (phone numbers like "+233 …" are left alone).
				$is_formula = preg_match( '/^[=@\t\r]/', $cell ) || ( preg_match( '/^[+\-]/', $cell ) && ! preg_match( '/^[+\-][\d\s()\-]+$/', $cell ) );
				$row[]      = $is_formula ? "'" . $cell : $cell;
			}
			fputcsv( $out, $row );
		}
		fclose( $out );
		exit;
	}
);
