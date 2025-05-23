<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ticket_Booking_Stripe {

	public function __construct() {
		add_action( 'wp_ajax_process_payment', array( $this, 'process_payment' ) );
		add_action( 'wp_ajax_nopriv_process_payment', array( $this, 'process_payment' ) );
		add_action( 'wp_ajax_process_booking', array( $this, 'process_booking' ) );
		add_action( 'wp_ajax_nopriv_process_booking', array( $this, 'process_booking' ) );

		add_shortcode( 'payment_return_handler', array( $this, 'payment_return_handler' ) );
		add_action('wp_ajax_revert_table_status', array($this, 'revert_table_status'));
        add_action('wp_ajax_nopriv_revert_table_status', array($this, 'revert_table_status'));
	}

	private function insert_booking_record($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ticket_bookings';
        
        $table_type_text = $data['table_type'] === 'full' ? 'Full Table' : 
                          ($data['table_type'] === 'half' ? 'Half Table' : 'Individual');

        $wpdb->insert(
            $table_name,
            [
                'table_number' => $data['table_number'],
                'fname' => $data['fname'],
                'lname' => $data['lname'],
                'email' => $data['email'],
                'company_name' => isset($data['company_name']) ? $data['company_name'] : '',
                'table_type' => $table_type_text,
                'number_of_seats' => intval($data['seat_quantity']),
                'payment_method' => isset($data['payment_method']) ? $data['payment_method'] : 'Card',
                'payment_status' => isset($data['payment_status']) ? $data['payment_status'] : 'Confirmed',
                'company_location' => isset($data['company_location']) ? $data['company_location'] : '',
                'bin_number' => isset($data['bin_number']) ? $data['bin_number'] : '',
                'total_amount' => floatval($data['total_amount']),
                'total_vat' => isset($data['total_vat']) ? floatval($data['total_vat']) : 0,
                'vat_percentage' => isset($data['vat_percentage']) ? intval($data['vat_percentage']) : 0,
                'payment_id' => isset($data['payment_id']) ? $data['payment_id'] : '',
                'order_id' => $data['order_id'],
                'order_date' => isset($data['order_date']) ? $data['order_date'] : current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

	public function process_payment() {
		if ( ! isset( $_POST['payment_method'] ) ) {
			wp_send_json_error( 'Payment method not provided' );
		}

		// Securely fetch all POST data
		$payment_method = sanitize_text_field( $_POST['payment_method'] );
		$total_amount   = floatval( $_POST['total_amount'] ); // Amount in cents
		$table_number   = sanitize_text_field( $_POST['table_number'] );
		$seat_quantity  = intval( $_POST['seat_quantity'] );
		$table_type     = sanitize_text_field( $_POST['table_type'] );
		$fname          = sanitize_text_field( $_POST['fname'] );
		$lname          = sanitize_text_field( $_POST['lname'] );
		$email          = sanitize_email( $_POST['email'] );
		$company_name   = sanitize_text_field( $_POST['company_name'] );
		$pay_method     = sanitize_text_field( $_POST['payMethod'] );
		$location       = sanitize_text_field( $_POST['location'] );
		$bin_number     = sanitize_text_field( $_POST['bin_number'] );
		$total_vat      = floatval( $_POST['total_vat'] );
		$vatPercentage  = intval( $_POST['vatPercentage'] );

		// Validate amount
		if ( $total_amount <= 0 ) {
			wp_send_json_error( 'Invalid payment amount' );
		}

		// Get Stripe configuration
		$stripe_secret_key = get_option( 'stripe_client_secret', '' );

		if ( empty( $stripe_secret_key ) ) {
			wp_send_json_error( 'Stripe secret key not configured' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_details';
		
		try {
			// Start transaction
			$wpdb->query( 'START TRANSACTION' );

			// Check table availability
			$results = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table_name WHERE table_number = %d FOR UPDATE",
				$table_number
			) );

			if ( ! $results ) {
				throw new Exception( 'Table not found.' );
			}

			if ( $results->table_status === 'Sold' ) {
				throw new Exception( 'Table already sold.' );
			}

			$sell_seats = $results->sell_seats + $seat_quantity;
			if ( $sell_seats > 10 ) {
				throw new Exception( 'Not enough seats available.' );
			}

			// Generate unique order ID
			$order_id = uniqid( 'FA', false );

			// Update table status
			$wpdb->update(
				$table_name,
				[
					'sell_seats'   => $sell_seats,
					'table_status' => ( $sell_seats === 10 ) ? 'Sold' : 'Unsold',
				],
				[ 'table_number' => $table_number ]
			);

			// Set up Stripe
			\Stripe\Stripe::setApiKey( $stripe_secret_key );

			// Create payment intent with automatic confirmation
			$payment_intent = \Stripe\PaymentIntent::create([
				'amount' => $total_amount,
				'currency' => get_option('stripe_currency', 'gbp'),
				'payment_method' => $payment_method,
				'confirmation_method' => 'automatic',
				'confirm' => true,
				'return_url' => site_url('/payment-return'),
				'metadata' => [
					'order_id' => $order_id,
					'table_number' => $table_number,
					'first_name' => $fname,
					'last_name' => $lname,
					'email' => $email,
					'company_name' => $company_name,
					'company_location' => $location,
					'bin_number' => $bin_number,
					'table_type' => $table_type,
					'seat_quantity' => $seat_quantity,
					'total_vat' => $total_vat,
					'vat_percentage' => $vatPercentage,
					'order_date' => date('Y-m-d H:i:s'),
				],
			]);

			// Check payment intent status
			if ($payment_intent->status === 'requires_action') {
				// 3D Secure authentication needed
				$wpdb->query('COMMIT');
				wp_send_json_success([
					'requires_action' => true,
					'payment_intent_client_secret' => $payment_intent->client_secret,
					'order_id' => $order_id,
					'redirect_url' => site_url( '/payment-return' ),
				]);
			} else if ($payment_intent->status === 'succeeded') {
				// Payment successful, insert booking record
				$this->insert_booking_record([
					'table_number' => $table_number,
					'fname' => $fname,
					'lname' => $lname,
					'email' => $email,
					'company_name' => $company_name,
					'table_type' => $table_type,
					'seat_quantity' => $seat_quantity,
					'payment_method' => $pay_method,
					'company_location' => $location,
					'bin_number' => $bin_number,
					'total_amount' => $total_amount / 100,
					'total_vat' => $total_vat,
					'vat_percentage' => $vatPercentage,
					'payment_id' => $payment_intent->id,
					'order_id' => $order_id
				]);

				// Send confirmation email
				do_action( 'ticket_booking_order_complete', $order_id );

				$wpdb->query( 'COMMIT' );
				wp_send_json_success([
					'success' => true,
					'payment_intent' => $payment_intent,
					'redirect_url' => site_url( '/payment-return' ),
				]);
			} else {
				throw new Exception( 'Payment failed: ' . $payment_intent->status );
			}

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			
			// Revert table status if needed
			// if ( isset( $sell_seats ) ) {
			// 	$wpdb->update(
			// 		$table_name,
			// 		[
			// 			'sell_seats'   => $results->sell_seats,
			// 			'table_status' => ( $results->sell_seats === 10 ) ? 'Sold' : 'Unsold',
			// 		],
			// 		[ 'table_number' => $table_number ]
			// 	);
			// }

			// Log error for debugging
			error_log( 'Stripe Payment Error: ' . $e->getMessage() );
			
			wp_send_json_error( $e->getMessage() );
		}
	}

	//process_booking
	public function process_booking() {
		// Securely fetch all POST data
		$total_amount  = floatval( $_POST['total_amount'] ); // Amount in cents
		$table_number  = sanitize_text_field( $_POST['table_number'] );
		$seat_quantity = intval( $_POST['seat_quantity'] );
		$table_type    = sanitize_text_field( $_POST['table_type'] );
		$fname         = sanitize_text_field( $_POST['fname'] );
		$lname         = sanitize_text_field( $_POST['lname'] );
		$email         = sanitize_email( $_POST['email'] );
		$company_name  = sanitize_text_field( $_POST['company_name'] );
		$pay_method    = sanitize_text_field( $_POST['payMethod'] );
		$location      = sanitize_text_field( $_POST['location'] );
		$bin_number    = sanitize_text_field( $_POST['bin_number'] );
		$total_vat     = floatval( $_POST['total_vat'] );
		$vatPercentage = sanitize_text_field( $_POST['vatPercentage'] );

		//update ticket details table
		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_details';

		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE table_number = %d", $table_number ) );

		$order_id = uniqid( 'FA', false );

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

		$this->insert_booking_record([
			'table_number' => $table_number,
			'fname' => $fname,
			'lname' => $lname,
			'email' => $email,
			'company_name' => $company_name,
			'table_type' => $table_type,
			'seat_quantity' => $seat_quantity,
			'payment_method' => $pay_method,
			'payment_status' => 'Pending',
			'company_location' => $location,
			'bin_number' => $bin_number,
			'total_amount' => $total_amount / 100,
			'total_vat' => $total_vat,
			'vat_percentage' => $vatPercentage,
			'order_id' => $order_id
		]);

		// send email using send_mail class
		do_action( 'ticket_booking_order_complete', $order_id );

		wp_send_json_success( [ 
			'order_id'     => $order_id,
			'redirect_url' => site_url( '/payment-return' ),
		] );
	}



	public function payment_return_handler() {
        if (isset($_GET['payment_intent'])) {
            // Load Stripe library
            if (!class_exists('\Stripe\Stripe')) {
                require_once WP_TICKET_BOOKING_PATH . 'lib/stripe/init.php';
            }

            $payment_intent_id = sanitize_text_field($_GET['payment_intent']);

            // Get Stripe secret key
            $stripe_secret_key = get_option('stripe_client_secret', '');
            \Stripe\Stripe::setApiKey($stripe_secret_key);

            try {
                // Retrieve PaymentIntent
                $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

                if ($payment_intent->status === 'succeeded') {
                    // Check if booking already exists
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'ticket_bookings';
                    $existing_booking = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE payment_id = %s",
                        $payment_intent_id
                    ));

                    if (!$existing_booking) {
                        // Insert booking record
                        $this->insert_booking_record([
							'table_number' => $payment_intent->metadata['table_number'],
							'fname' => $payment_intent->metadata['first_name'],
							'lname' => $payment_intent->metadata['last_name'],
							'email' => $payment_intent->metadata['email'],
							'company_name' => $payment_intent->metadata['company_name'],
							'company_location' => $payment_intent->metadata['company_location'],
							'bin_number' => $payment_intent->metadata['bin_number'],
							'table_type' => $payment_intent->metadata['table_type'],
							'seat_quantity' => $payment_intent->metadata['seat_quantity'],
							'total_amount' => $payment_intent->amount / 100,
							'total_vat' => $payment_intent->metadata['total_vat'],
							'vat_percentage' => $payment_intent->metadata['vat_percentage'],
							'payment_id' => $payment_intent->id,
							'order_id' => $payment_intent->metadata['order_id'],
							'order_date' => $payment_intent->metadata['order_date']
						]);

                        // Send confirmation email
                        do_action('ticket_booking_order_complete', $payment_intent->metadata['order_id']);
                    }

                    return '<div class="payment-success">
                        <i class="fa-regular fa-circle-check"></i>
                        <h2>Thank you!</h2>
                        <h4>Your booking has been confirmed.</h4>
                        <div class="payment-details">
                            <div class="booking">
                                <p>Booking number: </p>
                                <p><b>' . esc_html($payment_intent->metadata['order_id']) . '</b></p>
                            </div>
                            <div class="order">
                                <p>Order date: </p>
                                <p><b>' . date("M j, Y", strtotime($payment_intent->metadata['order_date'])) . '</b></p>
                            </div>
                            <p class="send_mail">We have sent detailed information about the order confirmation to your email <b>' . esc_html($payment_intent->metadata['email']) . '</b></p>
                        </div>
                    </div>';
                } elseif ($payment_intent->status === 'requires_payment_method') {
                    return '<h2>Payment Failed</h2><p>Please try again with a different payment method.</p>';
                } else {
                    return '<h2>Payment Pending</h2><p>Your payment is being processed. Please wait.</p>';
                }
            } catch (Exception $e) {
                return '<h2>Error</h2><p>There was an issue processing your payment: ' . esc_html($e->getMessage()) . '</p>';
            }
        }
        return 'Invalid payment response.';
    }

	public function revert_table_status() {
        global $wpdb;
        
        try {
            $table_number = sanitize_text_field($_POST['table_number']);
            $seat_quantity = intval($_POST['seat_quantity']);
            
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            // Get current table status
            $table_details = $wpdb->prefix . 'ticket_details';
            $table = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_details WHERE table_number = %d FOR UPDATE",
                $table_number
            ));
            
            if (!$table) {
                throw new Exception('Table not found.');
            }
            
            // Calculate new sell_seats
            $sell_seats = $table->sell_seats - $seat_quantity;
            
            // Update table status
            $wpdb->update(
                $table_details,
                [
                    'sell_seats' => $sell_seats,
                    'table_status' => ($sell_seats === 10) ? 'Sold' : 'Unsold'
                ],
                ['table_number' => $table_number]
            );
            
            // Update booking status
            if (isset($_POST['order_id'])) {
                $booking_table = $wpdb->prefix . 'ticket_bookings';
                $wpdb->update(
                    $booking_table,
                    ['payment_status' => 'Failed'],
                    ['order_id' => sanitize_text_field($_POST['order_id'])]
                );
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success();
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Revert Table Status Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}