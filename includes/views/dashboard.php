<div class="wrap">
    <h1>Plugin ShortCode</h1>
    <h4>For Booking table: <code>[ticket_booking]</code></h4>
    <h4>Checkout Page (This page url must be '/checkout'): <code>[ticket_checkout]</code></h4>
    <h4>Payment Return / Thank You page (This page url must be '/payment-return'): <code>[payment_return_handler]</code></h4>

    <?php
    global $wpdb;
    // show total number of tables
    $total_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ticket_details");
    echo '<h2>Total Tables: ' . $total_tables . '</h2>';

    // show total number of tickets sold
    $total_tickets_sold = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ticket_details WHERE table_status = 'sold'");
    echo '<h2>Total Table Sold: ' . $total_tickets_sold . '</h2>';
    ?>
</div>
