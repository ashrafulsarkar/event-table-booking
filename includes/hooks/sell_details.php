<?php
add_action( 'wp_ajax_get_booking', 'get_booking' );

function get_booking() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_bookings';

	if ( ! check_ajax_referer( 'get_booking', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$order_id = sanitize_text_field( $_POST['order_id'] );
	$table    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE `order_id` = %s", $order_id ) );

	if ( ! $table ) {
		wp_send_json_error( array( 'message' => 'Data not found.' ) );
	}

	wp_send_json_success( array( 'data' => $table ) );
}

add_action( 'wp_ajax_edit_booking', 'edit_booking' );

function edit_booking() {
	check_ajax_referer( 'edit_booking', 'security' );
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_bookings';

	$order_id        = sanitize_text_field( $_POST['order_id'] );
	$table_number    = sanitize_text_field( $_POST['table_number'] );
	$number_of_seats = sanitize_text_field( $_POST['number_of_seats'] );
	$payment_status  = sanitize_text_field( $_POST['payment_status'] );

	$updated = $wpdb->update( $table_name,
		[ 'payment_status' => $payment_status ],
		[ 'order_id' => $order_id ]
	);

	if ( $updated === false ) {
		wp_send_json_error( [ 'message' => 'Failed to update booking.' ] );
	}

	if ( $payment_status == 'Refund' || $payment_status == 'Canceled' ) {
		$table_details = $wpdb->prefix . 'ticket_details';

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_details WHERE `table_number` = %d", $table_number ) );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => 'Table not found.' ) );
		}

		$sell_seats = $result->sell_seats - $number_of_seats;

		$wpdb->update( $table_details,
			array(
				'table_status' => 'Unsold',
				'sell_seats'   => $sell_seats
			),
			array( 'table_number' => $table_number )
		);
	}

	if ( $payment_status == 'Refund' ) {
		do_action( 'ticket_booking_order_refund', $order_id );
	} elseif ( $payment_status == 'Canceled' ) {
		do_action( 'ticket_booking_order_canceled', $order_id );
	} else {
		do_action( 'ticket_booking_order_complete', $order_id );
	}
	wp_send_json_success( [ 'message' => 'Booking updated successfully!' ] );
}