<?php
class Ticket_Booking_Checkout {

	public function __construct() {
		add_shortcode( 'ticket_checkout', array( $this, 'render_checkout_page' ) );
	}

	public function render_checkout_page() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$table_number  = isset( $_POST['table_number'] ) ? sanitize_text_field( $_POST['table_number'] ) : '';
			$seat_quantity = isset( $_POST['seat_quantity'] ) ? intval( $_POST['seat_quantity'] ) : 0;
			$table_type    = isset( $_POST['table_type'] ) ? sanitize_text_field( $_POST['table_type'] ) : '';
			$price         = isset( $_POST['price'] ) ? floatval( $_POST['price'] ) : 0;

			ob_start();
			?>
			<div id="error_massage"></div>
			<div class="chackout_page">
				<div id="checkout-form">
					<div class="payemnt_info">
						<h3>Payment info</h3>
						<div class="payment_method">
							<div class="payment_card">
								<div class="payment_option">
									<input type="radio" id="stripe" name="payment_method" value="Card" checked>
									<label for="stripe">Card</label>
								</div>
								<div id="stripe-payment">
									<div id="card-errors" role="alert"></div>
									<label for="card-element">Enter your card details:</label>
									<div id="card-element"></div>
								</div>
							</div>
							<div class="payment_option">
								<input type="radio" id="bank_deposit" name="payment_method" value="Bank Deposit">
								<label for="bank_deposit">Bank Deposit</label>
							</div>
						</div>
					</div>
					<div class="billing_info">
						<h3>Billing info</h3>
						<div class="location">
							<label for="location">Location*</label>
							<select id="location" name="location" required>
								<option value="United Kingdom" selected>United Kingdom</option>
								<option value="Outside UK">Outside UK</option>
							</select>
						</div>
						<div class="bin_number" id="bin_number_div" style="display: none;">
							<label for="bin_number">Business ID Number*</label>
							<input type="text" id="bin_number" name="bin_number" required>
						</div>
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
						if ( $table_type == 'full' ) {
							$table_type_text = 'Table of 10 seats';
						} elseif ( $table_type == 'half' ) {
							$table_type_text = 'Table of 5 seats';
						} else {
							$table_type_text = 'Individual seat';
						}
						?>
						<div class="table_plan">
							<p class="table_type"><?php echo esc_html( $table_type_text ); ?> x<span id="seat-quantity"><?php echo esc_html( $seat_quantity ); ?></span></p>
							<p class="amount">£<span id="price"><?php echo esc_html( number_format( $price, 2, '.', '' ) ); ?></span></p>
						</div>
						<div class="vat_percentage">
							<p>VAT (<span id="vat_percentage"></span>%)</p>
							<p class="amount">£<span id="vat"></span></p>
						</div>
						<div class="total_price">
							<p class="total_amount">Total</p>
							<p class="amount">£<span id="total_price"></span></p>
						</div>
						<input type="hidden" id="table_number" value="<?php echo esc_attr( $table_number ); ?>">
						<input type="hidden" id="table-type" value="<?php echo esc_attr( $table_type ); ?>">
						<button id="pay-now-button" class="button">Pay Now</button>
						<button id="book-now-button" class="button" style="display: none;">Book Now</button>
					</div>
				</div>
			</div>
			<script>
				function calculateVAT() {
					let vatPercentage = 0;
					const location = document.getElementById('location').value;
					if (location === 'Outside UK') {
						vatPercentage = 0;
					} else {
						vatPercentage = <?php echo get_option( 'ticket_booking_vat_percentage', '0' ); ?>;
					}

					const price = parseFloat(document.getElementById('price').innerText);
					const totalVat = (price * vatPercentage) / 100;

					document.getElementById('vat').innerText = totalVat.toFixed(2);
					document.getElementById('vat_percentage').innerText = vatPercentage;
					document.getElementById('total_price').innerText = (price + totalVat).toFixed(2);
				}

				// Initial calculation on page load
				document.addEventListener('DOMContentLoaded', function () {
					calculateVAT();
				});

				// Recalculate VAT when location changes
				document.getElementById('location').addEventListener('change', function () {
					calculateVAT();
				});
				//when location value will be outside UK then show bin_number field
				document.getElementById('location').addEventListener('change', function () {
					if (this.value === 'Outside UK') {
						document.getElementById('bin_number_div').style.display = 'block';
					} else {
						document.getElementById('bin_number_div').style.display = 'none';
					}
				});

				//payment_option hide/show
				document.querySelectorAll('.payment_option').forEach(function (element) {
					element.addEventListener('click', function () {
						if (element.querySelector('input').value === 'Bank Deposit') {
							document.getElementById('stripe-payment').style.display = 'none';
							document.getElementById('book-now-button').style.display = 'block';
							document.getElementById('pay-now-button').style.display = 'none';
						} else {
							document.getElementById('stripe-payment').style.display = 'block';
							document.getElementById('book-now-button').style.display = 'none';
							document.getElementById('pay-now-button').style.display = 'block';
						}
					});
				});

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
						const tableNumber = document.getElementById('table_number').value.trim();
						const fname = document.getElementById('fname').value.trim();
						const lname = document.getElementById('lname').value.trim();
						const email = document.getElementById('email').value.trim();
						const companyName = document.getElementById('cname').value.trim();
						const tableType = document.getElementById('table-type').value.trim();
						const seatQuantity = document.getElementById('seat-quantity').innerText.trim();
						const payMethod = document.querySelector('input[name="payment_method"]:checked').value;
						const location = document.getElementById('location').value.trim();
						const binNumber = document.getElementById('bin_number').value.trim();
						const totalPrice = document.getElementById('total_price').innerText.trim();
						const vatAmount = document.getElementById('vat').innerText.trim();
						const vatPercentage = document.getElementById('vat_percentage').innerText.trim();

						//when location value will be outside UK then bin_number field will be required
						if (location === 'Outside UK' && !binNumber) {
							document.getElementById('error_massage').innerHTML = '<p>Please fill out all required fields.</p>';
							payNowButton.disabled = false;
							payNowButton.textContent = 'Pay Now';
							payNowButton.style.cursor = 'pointer';
							payNowButton.style.opacity = '1';
							return;
						}

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
								table_number: tableNumber,
								fname: fname,
								lname: lname,
								email: email,
								company_name: companyName,
								table_type: tableType,
								seat_quantity: seatQuantity,
								payMethod: payMethod,
								location: location,
								bin_number: binNumber,
								total_amount: totalPrice * 100,
								total_vat: vatAmount,
								vatPercentage: vatPercentage,
							},
							success: function (response) {
								if (response.success) {
									window.location.href = response.data.redirect_url + '?payment_intent=' + response.data.payment_intent.id;
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

					const bookNowButton = document.getElementById('book-now-button');
					bookNowButton.addEventListener('click', async function (event) {
						event.preventDefault();

						// Disable the button to prevent duplicate clicks
						bookNowButton.disabled = true;
						bookNowButton.textContent = 'Processing...';
						bookNowButton.style.cursor = 'not-allowed';
						bookNowButton.style.opacity = '0.5';

						// Collect form data
						const tableNumber = document.getElementById('table_number').value.trim();
						const fname = document.getElementById('fname').value.trim();
						const lname = document.getElementById('lname').value.trim();
						const email = document.getElementById('email').value.trim();
						const companyName = document.getElementById('cname').value.trim();
						const tableType = document.getElementById('table-type').value.trim();
						const seatQuantity = document.getElementById('seat-quantity').innerText.trim();
						const payMethod = document.querySelector('input[name="payment_method"]:checked').value;
						const location = document.getElementById('location').value.trim();
						const binNumber = document.getElementById('bin_number').value.trim();
						const totalPrice = document.getElementById('total_price').innerText.trim();
						const vatAmount = document.getElementById('vat').innerText.trim();
						const vatPercentage = document.getElementById('vat_percentage').innerText.trim();

						//when location value will be outside UK then bin_number field will be required
						if (location === 'Outside UK' && !binNumber) {
							document.getElementById('error_massage').innerHTML = '<p>Please fill out all required fields.</p>';
							bookNowButton.disabled = false;
							bookNowButton.textContent = 'Book Now';
							bookNowButton.style.cursor = 'pointer';
							bookNowButton.style.opacity = '1';
							return;
						}

						// Validate fields
						if (!totalPrice || !tableNumber || !tableType || !fname || !lname || !email ) {
							document.getElementById('error_massage').innerHTML = '<p>Please fill out all required fields.</p>';
							bookNowButton.disabled = false;
							bookNowButton.textContent = 'Book Now';
							bookNowButton.style.cursor = 'pointer';
							bookNowButton.style.opacity = '1';
							return;
						}

						jQuery.ajax({
							url: checkoutData.ajax_url,
							method: 'POST',
							data: {
								action: 'process_booking',
								table_number: tableNumber,
								fname: fname,
								lname: lname,
								email: email,
								company_name: companyName,
								table_type: tableType,
								seat_quantity: seatQuantity,
								payMethod: payMethod,
								location: location,
								bin_number: binNumber,
								total_amount: totalPrice * 100,
								total_vat: vatAmount,
								vatPercentage: vatPercentage,
							},
							success: function (response) {
								if (response.success) {
									window.location.href = response.data.redirect_url + '?order_id=' + response.data.order_id;
								} else {
									bookNowButton.disabled = false;
									bookNowButton.textContent = 'Book Now';
									bookNowButton.style.cursor = 'pointer';
									bookNowButton.style.opacity = '1';
									document.getElementById('error_massage').innerHTML = '<p>Booking failed.</p>';
									console.log('Booking failed: ' + response.data);
								}
							},
							error: function (xhr, status, error) {
								bookNowButton.disabled = false;
								bookNowButton.textContent = 'Book Now';
								bookNowButton.style.cursor = 'pointer';
								bookNowButton.style.opacity = '1';
								document.getElementById('error_massage').innerHTML = '<p>Booking failed.</p>';
								console.log('Booking failed: ' + error);
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