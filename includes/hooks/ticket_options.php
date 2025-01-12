<?php
add_action( 'wp_ajax_add_table', 'ticket_booking_add_table' );

function ticket_booking_add_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_details';

	// Verify nonce for security
	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	// Sanitize and validate inputs
	$table_number = intval( $_POST['table_number'] );
	$table_status = sanitize_text_field( $_POST['table_status'] );
	$table_type   = sanitize_text_field( $_POST['table_type'] );

	// Validate required fields
	if ( empty( $table_number ) || empty( $table_status ) || empty( $table_type ) ) {
		wp_send_json_error( array( 'message' => 'Missing required fields. Please check your input.' ) );
	}

	// Check if table number is unique
	$existing_table = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM $table_name WHERE table_number = %d",
		$table_number
	) );

	if ( $existing_table > 0 ) {
		wp_send_json_error( array( 'message' => 'Table number must be unique. This number is already in use.' ) );
	}

	// Insert into the database
	$inserted = $wpdb->insert( $table_name, array(
		'table_number' => $table_number,
		'table_status' => $table_status,
		'table_type'   => $table_type,
		'sell_seats'   => 0,
	) );

	if ( $inserted === false ) {
		wp_send_json_error( array( 'message' => 'Failed to add the table. Database error: ' . $wpdb->last_error ) );
	}

	// Respond with success
	wp_send_json_success( array(
		'message' => 'Table added successfully!',
		'id'      => $wpdb->insert_id, // Optionally return the new table ID
	) );
}

// Handle getting the table data for editing
add_action( 'wp_ajax_get_table_data', 'ticket_booking_get_table_data' );
function ticket_booking_get_table_data() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_details';

	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$table_id = intval( $_POST['table_id'] );
	$table    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $table_id ) );

	if ( ! $table ) {
		wp_send_json_error( array( 'message' => 'Table not found.' ) );
	}

	wp_send_json_success( array( 'data' => $table ) );
}

// Handle editing the table
add_action( 'wp_ajax_edit_table', 'ticket_booking_edit_table' );
function ticket_booking_edit_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_details';

	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$table_id     = intval( $_POST['table_id'] );
	// $table_number = intval( $_POST['table_number'] );
	$table_status = sanitize_text_field( $_POST['table_status'] );
	$table_type   = sanitize_text_field( $_POST['table_type'] );

	// Validate required fields
	if ( empty( $table_status ) || empty( $table_type ) ) {
		wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
	}

	// Update the table data
	$updated = $wpdb->update( $table_name,
		array(
			'table_status' => $table_status,
			'table_type'   => $table_type
		),
		array( 'id' => $table_id )
	);

	if ( $updated === false ) {
		wp_send_json_error( array( 'message' => 'Failed to update table.' ) );
	}

	wp_send_json_success( array( 'message' => 'Table updated successfully!' ) );
}

// Handle deleting the table
add_action( 'wp_ajax_delete_table', 'ticket_booking_delete_table' );
function ticket_booking_delete_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_details';

	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$table_id = intval( $_POST['table_id'] );

	$deleted = $wpdb->delete( $table_name, array( 'id' => $table_id ) );

	if ( $deleted === false ) {
		wp_send_json_error( array( 'message' => 'Failed to delete the table.' ) );
	}

	wp_send_json_success( array( 'message' => 'Table deleted successfully!' ) );
}