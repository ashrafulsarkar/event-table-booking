<?php
/**
 * Plugin Name: Event Table Booking
 * Description: A custom ticket booking system with table management and Stripe payment integration.
 * Version: 1.1.0
 * Author: Ashraful Sarkar Naiem
 * Author URI: https://www.linkedin.com/in/ashrafulsarkar/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'WP_TICKET_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_TICKET_BOOKING_URL', plugin_dir_url( __FILE__ ) );

// Include core files.
// Include activation file.
require_once WP_TICKET_BOOKING_PATH . 'activation.php';

// Register activation hook.
register_activation_hook(__FILE__, 'create_ticket_booking_tables');

require_once WP_TICKET_BOOKING_PATH . 'vendor/autoload.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/class-ticket-booking-admin.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/class-ticket-booking-frontend.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/class-ticket-booking-checkout.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/hooks/ticket_options.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/hooks/refund.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/class-ticket-booking-stripe.php';
require_once WP_TICKET_BOOKING_PATH . 'includes/class-send-mail.php';

// Initialize the plugin.
function wp_ticket_booking_init() {
    new Ticket_Booking_Admin();
    new Ticket_Booking_Frontend();
    new Ticket_Booking_Checkout();
    new Ticket_Booking_Stripe();
    new Ticket_Send_Mail();
}
add_action( 'plugins_loaded', 'wp_ticket_booking_init' );