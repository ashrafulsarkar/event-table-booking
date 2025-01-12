<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="thank-you">
    <h1>Thank You for Your Booking!</h1>
    <p>Your table number is: <?php echo esc_html( $_GET['table_number'] ); ?></p>
    <p>Payment Status: Confirmed</p>
</div>
