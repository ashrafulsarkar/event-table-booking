<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;
$table_name = $wpdb->prefix . 'ticket_details';

// Fetch all table data
$tables = $wpdb->get_results( "SELECT * FROM $table_name" );

?>
<div class="wrap">
	<?php if( isset($_GET['action']) && $_GET['action'] === 'success'){ ?>
		<div class="notice notice-success is-dismissible" role="alert">
			<p>Table added.</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	<?php }	?>
	<h1 class="wp-heading-inline">Ticket Options</h1>
	<button id="add-table-btn" class="page-title-action">Add Table</button>

	<div id="add-table-popup" style="display: none;">
		<form id="add-table-form">
			<h2>Add New Table</h2>
			<label for="table_number">Table Number:</label>
			<input type="number" id="table_number" name="table_number" required>
			<br>
			<label for="table_status">Table Status:</label>
			<select id="table_status" name="table_status">
				<option value="Unsold">Unsold</option>
				<option value="Sold">Sold</option>
			</select>
			<br>
			<label for="table_type">Table Type:</label>
			<select id="table_type" name="table_type">
				<option value="Full Table">Full Table</option>
				<option value="Half Table">Half Table</option>
				<option value="Individual">Individual</option>
			</select>
			<br><br>
			<button type="button" id="close-popup" class="button">Cancel</button>
			<button type="submit" class="button button-primary">Save Table</button>
		</form>
	</div>
    <div id="edit-table-popup" style="display: none;">
		<form id="edit-table-form">
			<h2>Edit Table</h2>
			<label for="edit_table_number">Table Number:</label>
			<input type="number" id="edit_table_number" name="table_number" readonly>
			<br>
			<label for="edit_table_status">Table Status:</label>
			<select id="edit_table_status" name="table_status">
				<option value="Unsold">Unsold</option>
				<option value="Sold">Sold</option>
			</select>
			<br>
			<label for="edit_table_type">Table Type:</label>
			<select id="edit_table_type" name="table_type">
				<option value="Full Table">Full Table</option>
				<option value="Half Table">Half Table</option>
				<option value="Individual">Individual</option>
			</select>
			<br><br>
			<button type="button" id="edit_close-popup" class="button">Cancel</button>
			<button type="submit" class="button button-primary">Edit Table</button>
		</form>
	</div>

	

	<h2>All Tables</h2>
	
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Table Number</th>
				<th>Status</th>
				<th>Type</th>
				<th>Sell Seats</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $tables ) : ?>
				<?php foreach ( $tables as $table ) : ?>
					<tr>
						<td><?php echo esc_html( $table->table_number ); ?></td>
						<td><?php echo esc_html( $table->table_status ); ?></td>
						<td><?php echo esc_html( $table->table_type ); ?></td>
						<td><?php echo esc_html( $table->sell_seats ); ?></td>
						<td>
							<button class="button edit-table" data-id="<?php echo esc_attr( $table->id ); ?>">Edit</button>
							<button class="button delete-table" data-id="<?php echo esc_attr( $table->id ); ?>">Delete</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5">No tables found.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<script>
	jQuery(document).ready(function ($) {
		$('#add-table-btn').on('click', function () {
			$('#add-table-popup').fadeIn();
		});

		$('#close-popup').on('click', function () {
			$('#add-table-popup').fadeOut();
		});

		$('#add-table-form').on('submit', function (e) {
			e.preventDefault();

			const data = {
				action: 'add_table',
				table_number: $('#table_number').val(),
				table_status: $('#table_status').val(),
				table_type: $('#table_type').val(),
				security: ticketBookingAjax.ajax_nonce,
			};

			$.post(ticketBookingAjax.ajax_url, data, function (response) {
				if (response.success) {
					// alert(response.data.message);
					// location.reload();
					const url = new URL(window.location.href);
					url.searchParams.set('action', 'success');
					window.location.href = url.toString();
				} else {
					alert(response.data.message || 'An error occurred.');
				}
			}).fail(function (xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.log(xhr.responseText);
				alert('An unexpected error occurred. Check console for details.');
			});
		});

		// Edit button handler
		$('.edit-table').on('click', function () {
			const tableId = $(this).data('id');

            $('#edit_close-popup').on('click', function () {
                $('#edit-table-popup').fadeOut();
            });
			// Fetch the table data and open a modal to edit
			$.post(ticketBookingAjax.ajax_url, {
				action: 'get_table_data',
				table_id: tableId,
				security: ticketBookingAjax.ajax_nonce
			}, function (response) {
				if (response.success) {
					const table = response.data.data;
					// console.log(response);

					$('#edit_table_number').val(table.table_number);
					$('#edit_table_status').val(table.table_status);
					$('#edit_table_type').val(table.table_type);
					$('#edit-table-popup').fadeIn();
					$('#edit-table-popup').on('submit', function (e) {
						e.preventDefault();
						// Send the updated data to the server
						$.post(ticketBookingAjax.ajax_url, {
							action: 'edit_table',
							table_id: tableId,
							table_number: $('#edit_table_number').val(),
							table_status: $('#edit_table_status').val(),
							table_type: $('#edit_table_type').val(),
							security: ticketBookingAjax.ajax_nonce,
						}, function (response) {
							if (response.success) {
								alert('Table updated successfully');
								location.reload();
							} else {
								alert('Error: ' + response.data.message);
							}
						});
					});
				}
			});
		});

		// Delete button handler
		$('.delete-table').on('click', function () {
			const tableId = $(this).data('id');
			if (confirm('Are you sure you want to delete this table?')) {
				$.post(ticketBookingAjax.ajax_url, {
					action: 'delete_table',
					table_id: tableId,
					security: ticketBookingAjax.ajax_nonce
				}, function (response) {
					if (response.success) {
						alert('Table deleted successfully');
						location.reload();
					} else {
						alert('Error: ' + response.data.message);
					}
				});
			}
		});
	});
</script>

<?php
