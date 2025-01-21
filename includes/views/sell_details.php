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
		$this->table_name = $wpdb->prefix . 'ticket_bookings';
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
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->current_sl = 1 + $offset;
	}

	public function get_columns() {
		return [ 
			'sl'              => __( 'SL', 'textdomain' ),
			'table_number'    => __( 'Table Number', 'textdomain' ),
			'name'            => __( 'Name', 'textdomain' ),
			'email'           => __( 'Email', 'textdomain' ),
			'table_type'      => __( 'Type', 'textdomain' ),
			'number_of_seats' => __( 'Booked Seats', 'textdomain' ),
			'payment_status'  => __( 'Payment Status', 'textdomain' ),
			'amount'          => __( 'Amount', 'textdomain' ),
			'order_id'        => __( 'Order ID', 'textdomain' ),
			'payment_id'      => __( 'Payment ID', 'textdomain' ),
			'order_date'      => __( 'Date', 'textdomain' ),
		];
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
			case 'table_number':
				return esc_html( $item->table_number );
			case 'name':
				return esc_html( $item->fname . ' ' . $item->lname );
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