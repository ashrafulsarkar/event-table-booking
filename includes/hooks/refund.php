<?php

// Handle getting the table data for editing
add_action( 'wp_ajax_search_order', 'ticket_booking_search_order' );
function ticket_booking_search_order() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_bookings';

	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$order_id = sanitize_text_field( $_POST['order_id'] );
	$table    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE `order_id` = %s", $order_id ) );

	if ( ! $table ) {
		wp_send_json_error( array( 'message' => 'Data not found.' ) );
	}

	wp_send_json_success( array( 'data' => $table ) );
}

// Handle refunding the order
add_action( 'wp_ajax_refund_order', 'ticket_booking_refund_order' );
function ticket_booking_refund_order() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ticket_bookings';

	if ( ! check_ajax_referer( 'ticket_booking_nonce', 'security', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
	}

	$table_number    = sanitize_text_field( $_POST['table_number'] );
	$payment_id      = sanitize_text_field( $_POST['payment_id'] );
	$number_of_seats = intval( $_POST['number_of_seats'] );
	$order_id        = sanitize_text_field( $_POST['order_id'] );

	// Stripe refund
	try {
		$stripe_secret_key = get_option( 'stripe_client_secret', '' );
		if ( empty( $stripe_secret_key ) ) {
			wp_send_json_error( 'Stripe secret key not configured' );
		}

		$stripe = new \Stripe\StripeClient( $stripe_secret_key );

		$payment = $stripe->refunds->create(
			[ 'payment_intent' => $payment_id ]
		);

		if ( $payment->status !== 'succeeded' ) {
			wp_send_json_error( array( 'message' => 'Failed to refund payment.' ) );
		}

		// Refund the order table
		$refunded = $wpdb->update( $table_name,
			array( 'payment_status' => 'Refund' ),
			array( 'payment_id' => $payment_id )
		);

		if ( $refunded === false ) {
			wp_send_json_error( array( 'message' => 'Failed to refund order.' ) );
		} else {
			//change ticket_details table status and sell_seats
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

		do_action( 'ticket_booking_order_refund', $order_id );

		wp_send_json_success( array( 'message' => 'Order refunded successfully!' ) );

	} catch ( Exception $e ) {
		wp_send_json_error( array( 'message' => $e->getMessage() ) );
	}
}