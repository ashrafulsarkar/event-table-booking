<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ticket_Booking_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_admin_menu() {
		// Main menu: Dashboard
		add_menu_page(
			'Ticket Booking Dashboard',
			'Ticket Booking',
			'manage_options',
			'ticket-booking-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-tickets',
			20
		);

		add_submenu_page(
			'ticket-booking-dashboard',
			'Ticket Booking Dashboard',
			'Dashboard',
			'manage_options',
			'ticket-booking-dashboard',
		);

		// Submenu: Sell Details
		add_submenu_page(
			'ticket-booking-dashboard',
			'Sell Details',
			'Sell Details',
			'manage_options',
			'ticket-booking-sell-details',
			array( $this, 'render_sell_details_page' )
		);

		// Submenu: Ticket Options
		add_submenu_page(
			'ticket-booking-dashboard',
			'Ticket Options',
			'Ticket Options',
			'manage_options',
			'ticket-booking-ticket-options',
			array( $this, 'render_ticket_options_page' )
		);

		// Submenu: Ticket Options
		add_submenu_page(
			'ticket-booking-dashboard',
			'Ticket Refund',
			'Refund',
			'manage_options',
			'ticket-booking-ticket-refund',
			array( $this, 'render_ticket_refund_page' )
		);

		// Submenu: Settings
		add_submenu_page(
			'ticket-booking-dashboard',
			'Settings',
			'Settings',
			'manage_options',
			'ticket-booking-settings',
			array( $this, 'render_settings_page' )
		);

	}

	public function enqueue_admin_scripts( $hook ) {
		// Load scripts only for Ticket Booking admin pages
		if ( strpos( $hook, 'ticket-booking' ) === false ) {
			return;
		}

		wp_enqueue_style( 'ticket-booking-admin', WP_TICKET_BOOKING_URL . 'assets/admin.css' );
		wp_enqueue_script( 'ticket-booking-admin', WP_TICKET_BOOKING_URL . 'assets/admin.js', array( 'jquery' ), '1.0', true );

		// Localize script for AJAX
		wp_localize_script( 'ticket-booking-admin', 'ticketBookingAjax', array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'ticket_booking_nonce' ),
		) );
	}

	public function render_dashboard_page() {
		require_once WP_TICKET_BOOKING_PATH . 'includes/views/dashboard.php';
	}

	public function render_sell_details_page() {
		require_once WP_TICKET_BOOKING_PATH . 'includes/views/sell_details.php';

	}

	public function render_ticket_options_page() {
		require_once WP_TICKET_BOOKING_PATH . 'includes/views/ticket_options.php';
	}

	public function render_ticket_refund_page() {
		require_once WP_TICKET_BOOKING_PATH . 'includes/views/refund.php';
	}

	public function render_settings_page() {
		// Save settings if form is submitted
        if ( isset( $_POST['save_prices'] ) ) {
            check_admin_referer( 'ticket_booking_save_prices' ); // Security check
    
            $full_table_price = sanitize_text_field( $_POST['full_table_price'] );
            $half_table_price = sanitize_text_field( $_POST['half_table_price'] );
            $individual_price = sanitize_text_field( $_POST['individual_price'] );
            $vat_percentage = sanitize_text_field( $_POST['vat_percentage'] );
            $stripe_public_key = sanitize_text_field( $_POST['stripe_public_key'] );
            $stripe_client_secret = sanitize_text_field( $_POST['stripe_client_secret'] );
    
            update_option( 'ticket_booking_full_table_price', $full_table_price );
            update_option( 'ticket_booking_half_table_price', $half_table_price );
            update_option( 'ticket_booking_individual_price', $individual_price );
            update_option( 'ticket_booking_vat_percentage', $vat_percentage );
            update_option( 'stripe_public_key', $stripe_public_key );
            update_option( 'stripe_client_secret', $stripe_client_secret );
    
            echo '<div class="notice notice-success"><p>Settings updated successfully!</p></div>';
        }
    
        // Fetch current settings
        $full_table_price = get_option( 'ticket_booking_full_table_price', '0' );
        $half_table_price = get_option( 'ticket_booking_half_table_price', '0' );
        $individual_price = get_option( 'ticket_booking_individual_price', '0' );
        $vat_percentage = get_option( 'ticket_booking_vat_percentage', '0' );
        $stripe_public_key = get_option( 'stripe_public_key', '' );
        $stripe_client_secret = get_option( 'stripe_client_secret', '' );

		// Reset database
		// my database is 'ticket_details' and 'ticket_bookings'
		if ( isset( $_POST['reset_database'] ) ) {
			if ( $_POST['reset_text'] !== 'reset' ) {
				echo '<div class="notice notice-error"><p>Invalid confirmation text!</p></div>';
				return;
			}
			
			check_admin_referer( 'ticket_booking_reset_database' );

			global $wpdb;
			$table_name = $wpdb->prefix . 'ticket_details';
			$wpdb->query( "TRUNCATE TABLE $table_name" );

			$table_name = $wpdb->prefix . 'ticket_bookings';
			$wpdb->query( "TRUNCATE TABLE $table_name" );

			echo '<div class="notice notice-warning"><p>Database reset successfully!</p></div>';
		}
		require_once WP_TICKET_BOOKING_PATH . 'includes/views/setting.php';
	}
}