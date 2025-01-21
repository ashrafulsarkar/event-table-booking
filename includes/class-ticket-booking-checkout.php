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
			<div id="error_massage"></div>
			<div class="chackout_page">
				<div id="checkout-form">
					<div class="payemnt_info">
						<div id="stripe-payment">
							<div id="card-errors" role="alert"></div>
							<h3>Payment info</h3>
							<label for="card-element">Enter your card details:</label>
							<div id="card-element"></div>
						</div>
					</div>
					<div class="billing_info">
						<h3>Billing info</h3>
						<div class="full_name">
							<div class="first_name">
								<label for="fname">First name*</label>
								<input type="text" id="fname" name="fname" required>
							</div>
							<div class="last_name">
								<label for="lname">Last name*</label>
								<input type="text" id="lname" name="lname" required>
							</div>
						</div>
						<label for="email">Email*</label>
						<input type="email" id="email" name="email" required>
						<label for="cname">Company name</label>
						<input type="text" id="cname" name="cname">
					</div>
				</div>
				<div class="payment_summary">
					<h3>Summary</h3>
					<div id="payment-details">
						<h4>Table plan</h4>
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
							<p class="amount">£<span id="price"><?php echo esc_html(number_format($price, 2, '.', ''));?></span></p>
						</div>
						<div class="vat_percentage">
							<?php 
							$vat_percentage = get_option( 'ticket_booking_vat_percentage', '0' );
							$total_vat = ($price * $vat_percentage) / 100;
							?>
							<p>VAT (<?php echo $vat_percentage;?>%)</p>
							<p class="amount">£<span id="vat"><?php echo number_format($total_vat, 2, '.', '');?></span></p>
						</div>
						<div class="total_price">
							<p class="total_amount">Total</p>
							<p class="amount">£<span id="total_price"><?php echo number_format(($price + $total_vat), 2, '.', '');?></span></p>
						</div>
						<input type="hidden" id="table_number" value="<?php echo esc_attr($table_number);?>">
						<input type="hidden" id="table-type" value="<?php echo esc_attr($table_type);?>">
						<button id="pay-now-button" class="button">Pay Now</button>
					</div>
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

				document.addEventListener('DOMContentLoaded', function () {
					// Initialize Stripe
					const stripe = Stripe(checkoutData.publishable_key);
					const elements = stripe.elements();
					
					const card = elements.create('card', {
						style: {
							base: {
								color: '#000',
								fontSize: '16px',
								fontSmoothing: 'antialiased',
								'::placeholder': {
									color: '#aab7c4'
								}
							},
							invalid: {
								color: '#fa755a',
								iconColor: '#fa755a'
							}
						},
						hidePostalCode: true,
					});

					card.mount('#card-element');

					// Handle form submission
					const payNowButton = document.getElementById('pay-now-button');
					payNowButton.addEventListener('click', async function (event) {
						event.preventDefault();

						// Disable the button to prevent duplicate clicks
						payNowButton.disabled = true;
						payNowButton.textContent = 'Processing...';
						payNowButton.style.cursor = 'not-allowed';
						payNowButton.style.opacity = '0.5';
						// Collect form data
						const totalPrice = document.getElementById('total_price').innerText.trim();
						const seatQuantity = document.getElementById('seat-quantity').innerText.trim();
						const tableNumber = document.getElementById('table_number').value.trim();
						const tableType = document.getElementById('table-type').value.trim();
						const fname = document.getElementById('fname').value.trim();
						const lname = document.getElementById('lname').value.trim();
						const email = document.getElementById('email').value.trim();
						const companyName = document.getElementById('cname').value.trim();

						// Validate fields
						if (!totalPrice || !tableNumber || !tableType || !fname || !lname || !email) {
							document.getElementById('error_massage').innerHTML = '<p>Please fill out all required fields.</p>';
							payNowButton.disabled = false;
							payNowButton.textContent = 'Pay Now';
							payNowButton.style.cursor = 'pointer';
							payNowButton.style.opacity = '1';
							return;
						}

						// Create a payment method
						const { paymentMethod, error } = await stripe.createPaymentMethod({
							type: 'card',
							card: card,
							billing_details: {
								email: email,
								name: `${fname} ${lname}`,
							},
						});

						if (error) {
							document.getElementById('card-errors').textContent = error.message;
							payNowButton.disabled = false;
							payNowButton.textContent = 'Pay Now';
							payNowButton.style.cursor = 'pointer';
							payNowButton.style.opacity = '1';
							return;
						}        

						jQuery.ajax({
							url: checkoutData.ajax_url,
							method: 'POST',
							data: {
								action: 'process_payment',
								payment_method: paymentMethod.id,
								amount: totalPrice * 100,
								table_number: tableNumber,
								seat_quantity: seatQuantity,
								table_type: tableType,
								fname: fname,
								lname: lname,
								email: email,
								company_name: companyName,
							},
							success: function (response) {
								if (response.success) {
									window.location.href = response.data.redirect_url+'?payment_intent='+response.data.payment_intent.id;
								} else {
									payNowButton.disabled = false;
									payNowButton.textContent = 'Pay Now';
									payNowButton.style.cursor = 'pointer';
									payNowButton.style.opacity = '1';
									document.getElementById('error_massage').innerHTML = '<p>Payment failed.</p>';
									console.log('Payment failed: ' + response.data);                    
								}
							},
							error: function (xhr, status, error) {
								payNowButton.disabled = false;
								payNowButton.textContent = 'Pay Now';
								payNowButton.style.cursor = 'pointer';
								payNowButton.style.opacity = '1';
								document.getElementById('error_massage').innerHTML = '<p>Payment failed.</p>';								
								console.log('Payment failed: ' + error);
							},
						});
					});
				});

			</script>

			<?php
		} else {
			echo '<p>Please select one Table.</p>';
		}
		return ob_get_clean();

	}
}