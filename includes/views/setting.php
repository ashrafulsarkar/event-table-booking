<div class="wrap">
	<h1>Ticket Booking Settings</h1>
	<form method="POST">
		<?php wp_nonce_field( 'ticket_booking_save_prices' ); ?>
		<table class="form-table">
			<!-- Full Table Price -->
			<tr>
				<th scope="row">
					<label for="full_table_price">Full Table Price</label>
				</th>
				<td>
					<input type="number" id="full_table_price" name="full_table_price"
						value="<?php echo esc_attr( $full_table_price ); ?>" class="regular-text">
				</td>
			</tr>
			<!-- Half Table Price -->
			<tr>
				<th scope="row">
					<label for="half_table_price">Half Table Price</label>
				</th>
				<td>
					<input type="number" id="half_table_price" name="half_table_price"
						value="<?php echo esc_attr( $half_table_price ); ?>" class="regular-text">
				</td>
			</tr>
			<!-- Individual Seat Price -->
			<tr>
				<th scope="row">
					<label for="individual_price">Individual Seat Price</label>
				</th>
				<td>
					<input type="number" id="individual_price" name="individual_price"
						value="<?php echo esc_attr( $individual_price ); ?>" class="regular-text">
				</td>
			</tr>
			<!-- VAT Percentage -->
			<tr>
				<th scope="row">
					<label for="vat_percentage">VAT Percentage</label>
				</th>
				<td>
					<input type="number" id="vat_percentage" name="vat_percentage"
						value="<?php echo esc_attr( $vat_percentage ); ?>" class="regular-text">
				</td>
			</tr>
			<!-- Stripe Public Key -->
			<tr>
				<th scope="row">
					<label for="stripe_public_key">Stripe Public Key</label>
				</th>
				<td>
					<input type="text" id="stripe_public_key" name="stripe_public_key"
						value="<?php echo esc_attr( $stripe_public_key ); ?>" class="regular-text">
				</td>
			</tr>
			<!-- Stripe Client Secret -->
			<tr>
				<th scope="row">
					<label for="stripe_client_secret">Stripe Client Secret</label>
				</th>
				<td>
					<input type="text" id="stripe_client_secret" name="stripe_client_secret"
						value="<?php echo esc_attr( $stripe_client_secret ); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" name="save_prices" class="button button-primary">Save Changes</button>
		</p>
	</form>
</div>