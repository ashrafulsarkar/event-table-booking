<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ticket_Booking_List_Table extends WP_List_Table {
	private $current_sl;
	private $table_name;

	public function __construct() {
		global $wpdb;
		parent::__construct( [ 
			'singular' => __( 'Ticket Booking', 'textdomain' ),
			'plural'   => __( 'Ticket Bookings', 'textdomain' ),
			'ajax'     => false,
		] );
		$this->current_sl = 0;
		$this->table_name = "{$wpdb->prefix}ticket_bookings";

		add_action( 'admin_footer', [ $this, 'edit_booking_script' ] );
	}

	public function prepare_items() {
		global $wpdb;

		// Set up pagination
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Handle search
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
		$where  = '';
		if ( $search ) {
			$where = $wpdb->prepare(
				"WHERE fname LIKE %s OR lname LIKE %s OR email LIKE %s OR order_id LIKE %s",
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}

		// Get total items for pagination
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} $where" );

		// Get items
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} $where ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		// Set up pagination args
		$this->set_pagination_args( [ 
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		] );

		// Set up columns
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->current_sl = 1 + $offset;
	}

	public function get_columns() {
		return [ 
			'sl'               => __( 'SL', 'textdomain' ),
			'table_number'     => __( 'Table Number', 'textdomain' ),
			'name'             => __( 'Name', 'textdomain' ),
			'email'            => __( 'Email', 'textdomain' ),
			'table_type'       => __( 'Type', 'textdomain' ),
			'number_of_seats'  => __( 'Booked Seats', 'textdomain' ),
			'payment_method'   => __( 'Payment Method', 'textdomain' ),
			'payment_status'   => __( 'Payment Status', 'textdomain' ),
			'company_location' => __( 'Location', 'textdomain' ),
			'bin_number'       => __( 'BIN Number', 'textdomain' ),
			'total_vat'        => __( 'Vat', 'textdomain' ),
			'total_amount'     => __( 'Amount', 'textdomain' ),
			'order_id'         => __( 'Order ID', 'textdomain' ),
			'order_date'       => __( 'Date', 'textdomain' ),
			'actions'          => __( 'Actions', 'textdomain' ),
		];
	}
	// action for edit and view details
	public function column_actions( $item ) {
		$actions         = [];
		$actions['edit'] = '';
		if ( $item->payment_status == 'Pending' || ( $item->payment_status == 'Confirmed' && $item->payment_method == 'Bank Deposit' ) ) {
			$actions['edit'] = sprintf( '<a href="#" class="edit-booking" data-order_id="%s">Edit</a> |', sanitize_text_field( $item->order_id ) );
		}
		$actions['view'] = sprintf( '<a href="#" class="view-booking" data-order_id="%s">View</a>', sanitize_text_field( $item->order_id ) );
		return sprintf( '%1$s %2$s', $actions['edit'], $actions['view'] );
	}

	// column_payment_status
	public function column_payment_status( $item ) {
		$payment_status = $item->payment_status;
		$color          = '';
		switch ( $payment_status ) {
			case 'Confirmed':
				$color = 'green';
				break;
			case 'Pending':
				$color = 'orange';
				break;
			case 'Refund':
				$color = 'blue';
				break;
			case 'Failed':
				$color = 'red';
				break;
			case 'Canceled':
				$color = 'red';
				break;
		}
		return "<span style='color: $color;'>$payment_status</span>";
	}

	// write edit and view details popup code here
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			?>
			<div id="edit-booking-popup" style="display: none;">
				<div class="popup_main_container">
					<form id="edit-booking-form">
						<h2>Edit Booking</h2>
						<p>Name: <span id="full_name"></span></p>
						<p>Email: <span id="email_add"></span></p>
						<p>Table Number: <span id="table_number"></span></p>
						<p>Number Of Seats: <span id="number_of_seats"></span></p>
						<label for="order_id">Order ID:</label>
						<input type="text" id="order_id" name="order_id" readonly>
						<br>
						<label for="edit_payment_status">Payment Status:</label>
						<select id="edit_payment_status" name="payment_status">
							<option value="Confirmed">Confirmed</option>
							<option value="Pending">Pending</option>
							<option value="Refund">Refund</option>
							<option value="Canceled">Canceled</option>
						</select>
						<br><br>
						<div class="table_button">
							<button type="button" id="edit_close-popup" class="button">Cancel</button>
							<button type="submit" id="edit_booking_button" class="button button-primary">Save Booking</button>
						</div>
					</form>
				</div>
			</div>
			<div id="view-booking-popup" style="display: none;">
				<div class="popup_main_container">
					<h2>View Booking Details</h2>
					<p>Table Number: <span id="vtable_number"></span></p>
					<p>Name: <span id="vfull_name"></span></p>
					<p>Email: <span id="vemail_add"></span></p>
					<p>Company Name: <span id="company_name"></span></p>
					<p>Table Type: <span id="table_type"></span></p>
					<p>Number Of Seats: <span id="vnumber_of_seats"></span></p>
					<p>Payment Method: <span id="payment_method"></span></p>
					<p>Payment Status: <span id="payment_status"></span></p>
					<p>Location: <span id="company_location"></span></p>
					<p>BIN Number: <span id="bin_number"></span></p>
					<p>Sub Total: <span id="subtotal"></span></p>
					<p>Vat: <span id="total_vat"></span></p>
					<p>Total Amount: <span id="total_amount"></span></p>
					<p>Payment ID: <span id="payment_id"></span></p>
					<p>Order ID: <span id="vorder_id"></span></p>
					<p>Order Date: <span id="order_date"></span></p>
					<div class="table_button_center">
						<button type="button" id="view_close-popup" class="button">Close</button>
					</div>
				</div>
			</div>
			<?php
		}
	}

	//edit_booking_script()
	public function edit_booking_script() {
		?>
		<script>
			jQuery(document).ready(function ($) {
				// Edit booking popup
				$('.edit-booking').on('click', function (e) {
					e.preventDefault();
					const order_id = $(this).data('order_id');
					$.post(ajaxurl, {
						action: 'get_booking',
						order_id: order_id,
						security: '<?php echo wp_create_nonce( 'get_booking' ); ?>'
					}, function (response) {
						if (response.success) {
							const booking = response.data.data;
							if (booking) {
								$('#edit-booking-popup').fadeIn();
								$('#full_name').text(booking.fname + ' ' + booking.lname);
								$('#email_add').text(booking.email);
								$('#number_of_seats').text(booking.number_of_seats);
								$('#table_number').text(booking.table_number);
								$('#order_id').val(booking.order_id);
								$('#edit_payment_status').val(booking.payment_status);
							}
						}
					});
				});

				// Close edit booking popup
				$('#edit_close-popup').on('click', function () {
					$('#edit-booking-popup').fadeOut();
				});

				$('.view-booking').on('click', function (e) {
					e.preventDefault();
					const order_id = $(this).data('order_id');
					$.post(ajaxurl, {
						action: 'get_booking',
						order_id: order_id,
						security: '<?php echo wp_create_nonce( 'get_booking' ); ?>'
					}, function (response) {
						if (response.success) {
							const booking = response.data.data;
							if (booking) {
								$('#view-booking-popup').fadeIn();
								$('#vtable_number').text(booking.table_number);
								$('#vfull_name').text(booking.fname + ' ' + booking.lname);
								$('#vemail_add').text(booking.email);
								$('#company_name').text(booking.company_name);
								$('#table_type').text(booking.table_type);
								$('#vnumber_of_seats').text(booking.number_of_seats);
								$('#payment_method').text(booking.payment_method);
								$('#payment_status').text(booking.payment_status);
								$('#company_location').text(booking.company_location);
								$('#bin_number').text(booking.bin_number);
								$('#subtotal').text(booking.total_amount - booking.total_vat);
								$('#total_vat').text(booking.total_vat);
								$('#total_amount').text(booking.total_amount);
								$('#payment_id').text(booking.payment_id);
								$('#vorder_id').text(booking.order_id);
								$('#order_date').text(booking.order_date);
							}
						}
					});
				});

				// Close edit booking popup
				$('#view_close-popup').on('click', function () {
					$('#view-booking-popup').fadeOut();
				});


				// Edit booking form submit
				$('#edit_booking_button').on('click', function (e) {
					e.preventDefault();
					const order_id = $('#order_id').val();
					const table_number = $('#table_number').text();
					const number_of_seats = $('#number_of_seats').text();
					const payment_status = $('#edit_payment_status').val();
					$.post(ajaxurl, {
						action: 'edit_booking',
						order_id: order_id,
						table_number: table_number,
						number_of_seats: number_of_seats,
						payment_status: payment_status,
						security: '<?php echo wp_create_nonce( 'edit_booking' ); ?>'
					}, function (response) {
						if (response.success) {
							// alert(response.data.message);
							alert('Booking updated successfully!');
							location.reload();
						} else {
							console.log(response);
							// alert('Failed to update booking.');
						}
					});
				});
			});
		</script>
		<?php
	}


	// public function get_sortable_columns() {
	// 	return [ 
	// 		'name'   => [ 'fname', true ],
	// 		'amount' => [ 'amount', true ],
	// 	];
	// }

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'sl':
				return $this->current_sl++;
			case 'name':
				return esc_html( "{$item->fname} {$item->lname}" );
			case 'payment_status':
				return $this->column_payment_status( $item );
			case 'actions':
				return $this->column_actions( $item );
			default:
				return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
		}
	}

	public function no_items() {
		_e( 'No bookings found.', 'textdomain' );
	}
}

// Display any messages
settings_errors( 'booking_messages' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e( 'Sell Details', 'textdomain' ); ?></h1>
	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php
		$list_table = new Ticket_Booking_List_Table();
		$list_table->prepare_items();
		$list_table->search_box( __( 'Search Bookings', 'textdomain' ), 'search-bookings' );
		$list_table->display();
		?>
	</form>
</div>