<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ticket_Booking_Frontend {

	public function __construct() {
		add_shortcode( 'ticket_booking', array( $this, 'render_frontend' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'wp_ajax_load_tables', array( $this, 'load_tables' ) );
		add_action( 'wp_ajax_nopriv_load_tables', array( $this, 'load_tables' ) );
		add_action( 'wp_ajax_book_table', array( $this, 'book_table' ) );
		add_action( 'wp_ajax_nopriv_book_table', array( $this, 'book_table' ) );
	}

	public function enqueue_frontend_scripts() {
		wp_enqueue_style( 'ticket-booking-frontend', WP_TICKET_BOOKING_URL . 'assets/style.css' );
		wp_enqueue_script( 'ticket-booking-script', WP_TICKET_BOOKING_URL . 'assets/script.js', array( 'jquery' ), null, true );

		wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/' );
		wp_enqueue_script( 'ticket-booking-stripe', WP_TICKET_BOOKING_URL . 'assets/stripe-handler.js', array( 'jquery', 'stripe-js' ), null, true );

		// Localize AJAX
		wp_localize_script( 'ticket-booking-script', 'ticketBookingAjax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function render_frontend() {
		ob_start();
		?>
		<div id="ticket-booking-layout">
			<h1>Stage</h1>
			<div id="table-grid" class="table_booking_grid">
				<!-- Tables will be dynamically loaded here -->
			</div>
			<div class="table_plan_key">
				<h4>TABLE PLAN KEY</h4>
				<div class="plan_key">
					<div class="plan booked">
						<div class="plan_color"></div>
						<h5>Booked</h5>
					</div>
					<div class="plan full">
						<div class="plan_color"></div>
						<h5>table of 10 available</h5>
					</div>
					<div class="plan half">
						<div class="plan_color"></div>
						<h5>Table of 5 available</h5>
					</div>
					<div class="plan individual">
						<div class="plan_color"></div>
						<h5>individual available</h5>
					</div>
				</div>
			</div>
		</div>
		<div id="booking-popup" style="display: none;">
			<form id="booking-form" action="<?php echo site_url('/checkout');?>" method="POST">
				<button type="button" id="close-popup" class="button"><i class="fa-solid fa-xmark"></i></button>
				<h2>TABLE NO: <span id="popup-table-number"></span></h2>
				<div id="seat-quantity-wrapper" style="display: none;">
					<label for="seat-quantity">SEAT QUANTITY:</label>
					<div class="input_option">
						<button type="button" id="decrease-seat">-</button>
						<input type="number" id="seat-quantity" min="1" max="5" readonly>
						<button type="button" id="increase-seat">+</button>
					</div>
				</div>
				<p id="popup-seat-capacity" style="display: block;">SEAT CAPACITY: <span></span></p>
				<p>PRICE: £<span id="popup-price"></span> + VAT</p>

				<input type="hidden" name="table_number" id="hidden-table-number" />
				<input type="hidden" name="seat_quantity" id="hidden-seat-quantity" />
				<input type="hidden" name="table_type" id="hidden-table-type" />
				<input type="hidden" name="price" id="hidden-price" />

				<button type="submit" class="button" id="continue-to-payment">CONTINUE TO PAYMENT</button>
			</form>
		</div>

		<?php
		return ob_get_clean();
	}

	public function load_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ticket_details';

		// Fetch tables
		$tables = $wpdb->get_results( "SELECT * FROM $table_name" );

		if ( ! $tables ) {
			wp_send_json_error( 'No tables available.' );
		}

		ob_start();
		foreach ( $tables as $table ) {
			$status_class     = ( $table->table_status === 'Sold' ) ? 'table-sold' : 'table-available';
			$seat_capacity    = 5;
			$table_individual = 'false';
			$table_type = 'individual';

			if ( 'Full Table' === $table->table_type ) {
				$type_class    = ' table-full';
				$seat_capacity = 10;
				$price         = get_option( 'ticket_booking_full_table_price', '0' );
				$table_type = 'full';
			} elseif ( 'Half Table' === $table->table_type ) {
				$type_class = ' table-half';
				$price      = get_option( 'ticket_booking_half_table_price', '0' );
				$table_type = 'half';
			} else {
				$type_class       = ' table-individual';
				$price            = get_option( 'ticket_booking_individual_price', '0' );
				$table_individual = 'true';
				if ($table->sell_seats > 4) {
					$seat_capacity = 10 - $table->sell_seats;
				}
			}

			echo '<div class="table ' . esc_attr( $status_class ) . esc_attr( $type_class ) . '" data-table-number="' . esc_attr( $table->table_number ) . '" data-seat-capacity="' . esc_attr( $seat_capacity ) . '" data-price="' . esc_attr( $price ) . '" data-individual="' . esc_attr( $table_individual ) . '" data-type="' . esc_attr( $table_type ) . '">';

			if ( $table->table_status === 'Sold' ) {
				echo '<p>Sold!</p>';
			} else {
				echo '<p>' . esc_html( $table->table_number ) . '<span>Table</span></p>';
			}
			echo '</div>';
		}
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}
}