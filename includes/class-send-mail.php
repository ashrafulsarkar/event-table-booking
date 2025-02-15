<?php
//after order compleate send email to customer
// write a class method send_email_to_customer($order_id) in class-send-email.php

class Ticket_Send_Mail {
	public function __construct() {
		add_action( 'ticket_booking_order_complete', array( $this, 'send_email_to_customer' ) );
		add_action( 'ticket_booking_order_refund', array( $this, 'send_email_after_refund' ) );
		add_action( 'ticket_booking_order_canceled', array( $this, 'send_email_order_canceled' ) );
	}

	public function send_email_to_customer( $order_id ) {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ticket_bookings';
		$order           = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %s", $order_id ) );
		$table_number    = $order->table_number;
		$fname           = $order->fname;
		$email           = $order->email;
		$table_type      = $order->table_type;
		$number_of_seats = $order->number_of_seats;
		$total_amount    = $order->total_amount;
		$order_id        = $order->order_id;
		$total_vat       = $order->total_vat;
		$tax_persent     = $order->vat_percentage;
		$payment_status  = $order->payment_status;

		$subject = 'Fresh Award Booking Confirmation! - ' . $order_id;
		// replay email
		$headers = 'Reply-To: Fresh Awards <infor@freshproduce.org.uk>' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

		// Logo URL
		$logo_url = 'https://fpcfreshawards.co.uk/wp-content/uploads/2025/01/FPCFreshAwards-1.png';

		// HTML email content
		$message = '
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Booking Confirmation</title>
		</head>
		<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #fff;">
			<div style="max-width: 700px; margin: 0 auto;">
				<div style="text-align: center; margin-bottom: 40px;">
					<img src="' . $logo_url . '" alt="Fresh Awards Logo" style="max-width: 200px;">
				</div>
				<div style="background-color: #FAF7F2;padding: 40px 32px;">
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">THANK YOU ' . strtoupper( $fname ) . ',</p>
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">YOUR BOOKING HAS BEEN CONFIRMED.</p>
					<div style="margin-bottom: 32px;padding-bottom: 32px; border-bottom: 1px solid #DACFB3;">
						<p style="text-align: center;font-weight: 700;font-size: 1.25em;margin: 0;padding-top: 16px;">Order number: ' . $order_id . '</p>
					</div>
					<h2 style="margin-top: 0;font-size: 1.25em;">ORDER SUMMARY</h2>
					<table style="width: 100%;padding-bottom: 32px; border-bottom: 1px solid #DACFB3;">
						<tr>
							<td style="font-size: 1em;padding-bottom: 10px;">Table number:</td>
							<td style="text-align: right;font-size: 1em;padding-bottom: 10px;">' . $table_number . '</td>
						</tr>
						<tr>
							<td style="font-size: 1em;padding-bottom: 10px;">Table type:</td>
							<td style="text-align: right;font-size: 1em;padding-bottom: 10px;">' . $table_type . '</td>
						</tr>
						<tr>
							<td style="font-size: 1em;padding-bottom: 10px;">Number of seats:</td>
							<td style="text-align: right;font-size: 1em;padding-bottom: 10px;">' . $number_of_seats . '</td>
						</tr>
						<tr>
							<td style="font-size: 1em;padding-bottom: 10px;">Subtotal:</td>
							<td style="text-align: right;font-size: 1em;padding-bottom: 10px;">£' . number_format( ( $total_amount - $total_vat ), 2 ) . '</td>
						</tr>
						<tr>
							<td style="font-size: 1em;padding-bottom: 10px;">Tax (' . $tax_persent . '%):</td>
							<td style="text-align: right;font-size: 1em;padding-bottom: 10px;">£' . number_format( $total_vat, 2 ) . '</td>
						</tr>
						<tr>
							<td style="font-size: 1em; font-weight: 700;">Order Total:</td>
							<td style="text-align: right;font-size: 1em; font-weight: 700;">£' . number_format( $total_amount, 2 ) . '</td>
						</tr>
					</table>
					<div style="padding-bottom: 32px;">
						<p style="text-align: center;font-weight: 700;font-size: 1.25em;margin: 0;">Your payment status is: ' . $payment_status . '</p>
					</div>
				</div>
				<div style="max-width:250px;margin: 0 auto; margin-top: 20px; font-size: 0.9em; color: #000;">
					<p style="text-align: center;">If you have any questions, please email us: <a href="mailto:infor@freshproduce.org.uk"style="color: #C09F56;">infor@freshproduce.org.uk</a></p>
				</div>
			</div>
		</body>
		</html>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $email, $subject, $message, $headers );
	}

	//send email after order canceled
	public function send_email_order_canceled( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_bookings';
		$order      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %s", $order_id ) );
		$fname      = $order->fname;
		$email      = $order->email;
		$order_id   = $order->order_id;

		$subject = 'Fresh Award Booking Canceled! - ' . $order_id;

		$headers = 'Reply-To: Fresh Awards <infor@freshproduce.org.uk>' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

		// Logo URL
		$logo_url = 'https://fpcfreshawards.co.uk/wp-content/uploads/2025/01/FPCFreshAwards-1.png';

		// HTML email content
		$message = '
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Booking Refund</title>
		</head>
		<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #fff;">
			<div style="max-width: 700px; margin: 0 auto;">
				<div style="text-align: center; margin-bottom: 40px;">
					<img src="' . $logo_url . '" alt="Fresh Awards Logo" style="max-width: 200px;">
				</div>
				<div style="background-color: #FAF7F2;padding: 40px 32px;">
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">HELLO ' . strtoupper( $fname ) . ',</p>
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">YOUR BOOKING HAS BEEN CANCELED.</p>
					<div>
						<p style="text-align: center;font-weight: 700;font-size: 1.25em;margin: 0;padding-top: 16px;">Order number: ' . $order_id . '</p>
					</div>
				</div>
				<div style="max-width:250px;margin: 0 auto; margin-top: 20px; font-size: 0.9em; color: #000;">
					<p style="text-align: center;">If you have any questions, please email us: <a href="mailto:infor@freshproduce.org.uk"style="color: #C09F56;">infor@freshproduce.org.uk</a></p>
				</div>
			</div>
		</body>
		</html>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $email, $subject, $message, $headers );
	}

	public function send_email_after_refund( $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_bookings';
		$order      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %s", $order_id ) );
		$fname      = $order->fname;
		$email      = $order->email;
		$order_id   = $order->order_id;

		$subject = 'Fresh Award Booking Refund! - ' . $order_id;

		$headers = 'Reply-To: Fresh Awards <infor@freshproduce.org.uk>' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

		// Logo URL
		$logo_url = 'https://fpcfreshawards.co.uk/wp-content/uploads/2025/01/FPCFreshAwards-1.png';

		// HTML email content
		$message = '
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Booking Refund</title>
		</head>
		<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #fff;">
			<div style="max-width: 700px; margin: 0 auto;">
				<div style="text-align: center; margin-bottom: 40px;">
					<img src="' . $logo_url . '" alt="Fresh Awards Logo" style="max-width: 200px;">
				</div>
				<div style="background-color: #FAF7F2;padding: 40px 32px;">
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">HELLO ' . strtoupper( $fname ) . ',</p>
					<p style="text-align: center;font-size: 1.75em;font-size: clamp(18px, 2vw, 28px);font-weight: 700;margin: 0;">YOUR PAYMENT HAS BEEN REFUNDED.</p>
					<div>
						<p style="text-align: center;font-weight: 700;font-size: 1.25em;margin: 0;padding-top: 16px;">Order number: ' . $order_id . '</p>
					</div>
				</div>
				<div style="max-width:250px;margin: 0 auto; margin-top: 20px; font-size: 0.9em; color: #000;">
					<p style="text-align: center;">If you have any questions, please email us: <a href="mailto:infor@freshproduce.org.uk"style="color: #C09F56;">infor@freshproduce.org.uk</a></p>
				</div>
			</div>
		</body>
		</html>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $email, $subject, $message, $headers );
	}
}