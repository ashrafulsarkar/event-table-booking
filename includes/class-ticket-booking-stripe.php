<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ticket_Booking_Stripe {

	public function __construct() {
		add_action( 'wp_ajax_process_payment', array( $this, 'process_payment' ) );
		add_action( 'wp_ajax_nopriv_process_payment', array( $this, 'process_payment' ) );

		add_shortcode( 'payment_return_handler', array( $this, 'payment_return_handler' ) );
	}

	public function process_payment() {
		if ( ! isset( $_POST['payment_method'] ) ) {
			wp_send_json_error( 'Payment method not provided' );
		}


		// Securely fetch all POST data
		$payment_method = sanitize_text_field( $_POST['payment_method'] );
		$amount         = intval( $_POST['amount'] ); // Amount in cents
		$table_number   = sanitize_text_field( $_POST['table_number'] );
		$seat_quantity  = intval( $_POST['seat_quantity'] );
		$table_type     = sanitize_text_field( $_POST['table_type'] );
		$fname          = sanitize_text_field( $_POST['fname'] );
		$lname          = sanitize_text_field( $_POST['lname'] );
		$email          = sanitize_email( $_POST['email'] );
		$company_name   = sanitize_text_field( $_POST['company_name'] );

		// Validate amount
		if ( $amount <= 0 ) {
			wp_send_json_error( 'Invalid payment amount' );
		}

		// Fetch Stripe secret key from options
		$stripe_secret_key = get_option( 'stripe_client_secret', '' );
		if ( empty( $stripe_secret_key ) ) {
			wp_send_json_error( 'Stripe secret key not configured' );
		}

		//update ticket details table
		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_details';

		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE table_number = %d", $table_number ) );

		$order_id = uniqid();

		if ( ! $results ) {
			wp_send_json_error( 'Table not found.' );
		}

		if ( $results->table_status === 'Sold' ) {
			wp_send_json_error( 'Table already sold.' );
		}

		// // print_r($results[0]->sell_seats);
		$sell_seats = $results->sell_seats + $seat_quantity;

		$wpdb->update(
			$table_name,
			[ 
				'sell_seats'   => $sell_seats,
				'table_status' => ( $sell_seats === 10 ) ? 'Sold' : 'Unsold',
			],
			[ 'table_number' => sanitize_text_field( $table_number ) ]
		);

		// Set Stripe API key
		\Stripe\Stripe::setApiKey( $stripe_secret_key );

		try {
			$payment_intent = \Stripe\PaymentIntent::create( [ 
				'amount'              => $amount,
				'currency'            => get_option( 'stripe_currency', 'gbp' ), // Default to GBP
				'payment_method'      => $payment_method,
				'confirmation_method' => 'manual',
				'confirm'             => true,
				'return_url'          => site_url( '/payment-return' ),
				'metadata'            => [ 
					'table_number'  => $table_number,
					'seat_quantity' => $seat_quantity,
					'table_type'    => $table_type,
					'first_name'    => $fname,
					'last_name'     => $lname,
					'email'         => $email,
					'company_name'  => $company_name,
					'order_id'      => $order_id,
					'order_date'    => date( 'Y-m-d H:i:s' ),
				],
			] );



			if ( $table_type === 'full' ) {
				$table_type_text = 'Full Table';
			} elseif ( $table_type === 'half' ) {
				$table_type_text = 'Half Table';
			} else { // Individual seat
				$table_type_text = 'Individual';
			}

			$sell_table_name = $wpdb->prefix . 'ticket_bookings';

			$wpdb->insert(
				$sell_table_name,
				[ 
					'table_number'    => sanitize_text_field( $table_number ),
					'fname'           => sanitize_text_field( $fname ),
					'lname'           => sanitize_text_field( $lname ),
					'email'           => sanitize_email( $email ),
					'company_name'    => sanitize_text_field( $company_name ),
					'table_type'      => sanitize_text_field( $table_type_text ),
					'number_of_seats' => intval( $seat_quantity ),
					'payment_status'  => 'Confirmed',
					'amount'          => floatval( $amount / 100 ),
					'payment_id'      => sanitize_text_field( $payment_intent->id ),
					'order_id'        => sanitize_text_field( $order_id ),
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%s' ]
			);

			wp_send_json_success( [ 
				'payment_intent' => $payment_intent,
				'redirect_url'   => site_url( '/payment-return' ),
			] );
		} catch (\Stripe\Exception\ApiErrorException $e) {
			$results    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE table_number = %d", $table_number ) );
			$sell_seats = $results->sell_seats - $seat_quantity;
			$wpdb->update(
				$table_name,
				[ 
					'sell_seats'   => $sell_seats,
					'table_status' => ( $sell_seats < 10 ) ? 'Unsold' : 'Sold',
				],
				[ 'table_number' => sanitize_text_field( $table_number ) ]
			);

			wp_send_json_error( $e->getMessage() );
		} catch (Exception $e) {
			$results    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE table_number = %d", $table_number ) );
			$sell_seats = $results->sell_seats - $seat_quantity;
			$wpdb->update(
				$table_name,
				[ 
					'sell_seats'   => $sell_seats,
					'table_status' => ( $sell_seats < 10 ) ? 'Unsold' : 'Sold',
				],
				[ 'table_number' => sanitize_text_field( $table_number ) ]
			);

			wp_send_json_error( 'An error occurred: ' . $e->getMessage() );
		}
	}

	public function payment_return_handler() {
		if ( ! isset( $_GET['payment_intent'] ) ) {
			return 'Invalid payment response.';
		}

		// Load Stripe library
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once WP_TICKET_BOOKING_PATH . 'lib/stripe/init.php';
		}

		$payment_intent_id = sanitize_text_field( $_GET['payment_intent'] );

		// Get Stripe secret key
		$stripe_secret_key = get_option( 'stripe_client_secret', '' );
		\Stripe\Stripe::setApiKey( $stripe_secret_key );

		try {
			// Retrieve PaymentIntent
			$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );

			if ( $payment_intent->status === 'succeeded' ) {
				// print_r($payment_intent);
				return '<div class="payment-success"><i class="fa-regular fa-circle-check"></i><h2>Thank you!</h2><h4>Your booking has been confirmed.</h4><div class="payment-details"><div class="booking"><p>Booking number: </p><p><b>' . esc_html( $payment_intent->metadata['order_id'] ) . '</b></p></div><div class="order"><p>Order date: </p><p><b>' . date("M j, Y", strtotime(esc_html( $payment_intent->metadata['order_date'] ))) . '</b></p></div><p class="send_mail">We have sent detailed information about the order confirmation to your email <b>' . esc_html( $payment_intent->metadata['email'] ) . '</b></p></div></div>';
			} elseif ( $payment_intent->status === 'requires_payment_method' ) {
				return '<h2>Payment Failed</h2><p>Please try again with a different payment method.</p>';
			} else {
				return '<h2>Payment Pending</h2><p>Your payment is being processed. Please wait.</p>';
			}
		} catch (Exception $e) {
			return '<h2>Error</h2><p>There was an issue processing your payment: ' . esc_html( $e->getMessage() ) . '</p>';
		}
	}
}