<?php
//after order compleate send email to customer
// write a class method send_email_to_customer($order_id) in class-send-email.php

class Ticket_Send_Mail {
	public function __construct() {
		add_action( 'ticket_booking_order_complete', array( $this, 'send_email_to_customer' ) );
        add_action( 'ticket_booking_order_refund', array( $this, 'send_email_after_refund' ) );
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
		$amount          = $order->amount;
		$order_id        = $order->order_id;
		$order_date      = $order->order_date;

		$subject = 'Fresh Award Booking Confirmation! - ' . $order_id;
		$message = 'Hello ' . $fname . ',<br>';
		$message .= 'Your order has been confirmed.<br>';
		$message .= 'Order Details:<br>';
		$message .= 'Table Number: ' . $table_number . '<br>';
		$message .= 'Table Type: ' . $table_type . '<br>';
		$message .= 'Number of Seats: ' . $number_of_seats . '<br>';
		$message .= 'Amount: £' . $amount . '<br>';
		$message .= 'Order ID: ' . $order_id . '<br>';
		$message .= 'Order Date: ' . $order_date . '<br>';
		$message .= 'Thank you for your order.';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $email, $subject, $message, $headers );
	}

    //send email after order refund
    public function send_email_after_refund( $order_id ) {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'ticket_bookings';
        $order           = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %s", $order_id ) );
        $fname           = $order->fname;
        $email           = $order->email;
        $amount          = $order->amount;
        $order_id        = $order->order_id;
        $order_date      = $order->order_date;

        $subject = 'Fresh Award Booking Refund! - ' . $order_id;
        $message = 'Hello ' . $fname . ',<br>';
        $message .= 'Your order has been refunded.<br>';
        $message .= 'Order Details:<br>';
        $message .= 'Amount: £' . $amount . '<br>';
        $message .= 'Order ID: ' . $order_id . '<br>';
        $message .= 'Order Date: ' . $order_date . '<br>';
        $message .= 'Thank you for your order.';
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $email, $subject, $message, $headers );
    }
}