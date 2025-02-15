<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Refund Ticket</h1>

	<div class="order_search">
        <input type="text" id="order_id" name="order_id" placeholder="Order ID" required>
        <button id="search_order" type="submit" class="button button-primary">Search Order</button>
	</div>
    <div class="order_details">
        <div class="order_info" id="order_info" style="display:none;">
            <h2>Order Details</h2>
            <p>Order ID: <span id="order_id_value"></span></p>
            <p>Table Number: <span id="table_number_text"></span></p>
            <p>Name: <span id="full_name"></span></p>
            <p>Email: <span id="email"></span></p>
            <p>Number of Seats: <span id="number_of_seats_text"></span></p>
            <p>Amount: <span id="amount"></span></p>
            <p>Order Date: <span id="order_date"></span></p>
            <p>Payment Status: <span id="payment_status"></span></p>
            <input type="hidden" name="payment_id" id="payment_id" />
            <input type="hidden" name="number_of_seats" id="number_of_seats" />
            <input type="hidden" name="table_number" id="table_number" />
            <h4 id="bank_deposit_massage" style="display: none;">Bank Deposit payment cannot refund using this option. Please manually complete this option using the Bank.</h4>
            <button id="refund_btn" type="submit" class="button button-primary" style="display: none;">Refund</button>
        </div>
    </div>
</div>
<script>
	jQuery(document).ready(function ($) {

        // Search order button handler
        $('#search_order').on('click', function () {
            const order_id = $('#order_id').val();

            if (!order_id) {
                alert('Please enter an Order ID.');
                return;
            }

            $.post(ticketBookingAjax.ajax_url, {
                action: 'search_order',
                order_id: order_id,
                security: ticketBookingAjax.ajax_nonce
            }, function (response) {
                if (response.success) {
                    const order = response.data.data;
                    if (order) {
                        $('#order_info').fadeIn();
                        $('#order_id_value').text(order.order_id);
                        $('#table_number_text').text(order.table_number);
                        $('#full_name').text(order.fname + ' ' + order.lname);
                        $('#email').text(order.email);
                        $('#number_of_seats_text').text(order.number_of_seats);
                        $('#amount').text(order.total_amount);
                        $('#order_date').text(order.order_date);
                        $('#payment_id').val(order.payment_id);
                        $('#number_of_seats').val(order.number_of_seats);
                        $('#table_number').val(order.table_number);
                        $('#payment_status').text(order.payment_status);
                        if (order.payment_status === 'Confirmed' || order.payment_method === 'Card') {
                            $('#bank_deposit_massage').hide();
                            $('#refund_btn').show();
                        } else {
                            $('#bank_deposit_massage').show();
                            $('#refund_btn').hide();
                        }
                    } else {
                        $('#order_info').fadeOut();
                        alert('Order not found');
                    }
                } else {
                    $('#order_info').fadeOut();
                    alert('Order not found');
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                alert('Request failed: ' + textStatus);
            });
        });

        // Refund button handler
        $('#refund_btn').on('click', function () {
            const payment_id = $('#payment_id').val();
            const number_of_seats = $('#number_of_seats').val();
            const table_number = $('#table_number').val();
            const order_id = $('#order_id').val();

            if (!payment_id || !number_of_seats || !table_number || !order_id) {
                alert('Missing necessary information for refund.');
                return;
            }

            $.post(ticketBookingAjax.ajax_url, {
                action: 'refund_order',
                table_number: table_number,
                payment_id: payment_id,
                order_id: order_id,
                number_of_seats: number_of_seats,
                security: ticketBookingAjax.ajax_nonce
            }, function (response) {
                if (response.success) {
                    alert('Order refunded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }).fail(function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
				console.log(xhr.responseText);
                alert('Request failed: ' + xhr.responseText);
            });
        });
    });
</script>

<?php
