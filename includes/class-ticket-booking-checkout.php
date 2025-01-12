<?php
class Ticket_Booking_Checkout {

	public function __construct() {
		add_shortcode( 'ticket_checkout', array( $this, 'render_checkout_page' ) );
	}

	public function render_checkout_page() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $table_number = isset($_POST['table_number']) ? sanitize_text_field($_POST['table_number']) : '';
		    $seat_quantity = isset($_POST['seat_quantity']) ? intval($_POST['seat_quantity']) : 0;
		    $table_type = isset($_POST['table_type']) ? sanitize_text_field($_POST['table_type']) : '';
		    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

			ob_start();
			?>
			<h2>Complete your booking</h2>
			<div id="checkout-form">
				<div class="payemnt_info">
					<div id="stripe-payment">
						<h3>Payment info</h3>
						<label for="card-element">Enter your card details:</label>
						<div id="card-element"><!-- Stripe will embed the card input here --></div>
						<div id="card-errors" role="alert"></div>

					</div>
				</div>
				<div class="billing_info">
					<h3>Billing info</h3>
					<label for="fname">First name*</label>
					<input type="text" id="fname" name="fname" required>
					<label for="lname">Last name*</label>
					<input type="text" id="lname" name="lname" required>
					<label for="email">Email</label>
					<input type="email" id="email" name="email" required>
					<label for="phone">Phone</label>
					<input type="tel" id="phone" name="phone" required>
					<label for="cname">Company name</label>
					<input type="text" id="cname" name="cname">
				</div>
			</div>
			<div class="payment_summary">
				<h3>Summary</h3>
				<div id="payment-details">
					<h3>Table plan</h3>
					<?php
					if ($table_type == 'full') {
						$table_type_text = 'Table of 10 seats';
					} elseif ( $table_type == 'half' ) {
						$table_type_text = 'Table of 5 seats';
					} else {
						$table_type_text = 'Individual seat';
					}
					?>
					<div class="table_plan">
						<p class="table_type"><?php echo esc_html($table_type_text);?> x<span id="seat-quantity"><?php echo esc_html($seat_quantity);?></span></p>
						<p>£<span id="price"><?php echo esc_html($price);?></span></p>
					</div>
					<div class="vat_percentage">
						<?php 
						$vat_percentage = get_option( 'ticket_booking_vat_percentage', '0' );
						$total_vat = ($price * $vat_percentage) / 100;
						?>
						<p>VAT (<?php echo $vat_percentage;?>%)</p>
						<p>£<span id="vat"><?php echo $total_vat;?></span></p>
					</div>
					<div class="total_price">
						<p>Total</p>
						<p>£<span id="total_price"><?php echo $price + $total_vat;?></span></p>
					</div>
					<input type="hidden" id="table_number" value="<?php echo esc_attr($table_number);?>">
					<input type="hidden" id="table-type" value="<?php echo esc_attr($table_type);?>">
					<button id="pay-now-button" class="button">Pay Now</button>
				</div>
			</div>
			<script>
				// Pass PHP data to JavaScript (use WordPress functions).
				<?php
				$stripe_public_key = get_option( 'stripe_public_key', '' );
				?>
				const checkoutData = <?php echo json_encode( [ 
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'publishable_key' => $stripe_public_key,
				] ); ?>;
			</script>

			<?php
		} else {
			echo '<p>Please select one Table.</p>';
		}
		return ob_get_clean();

	}
}