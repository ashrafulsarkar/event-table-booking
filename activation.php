<?php
function create_ticket_booking_tables() {
    global $wpdb;

    // Table names.
    $sell_details_table = $wpdb->prefix . 'ticket_bookings';
    $table_details_table = $wpdb->prefix . 'ticket_details';

    // Character set and collation for tables.
    $charset_collate = $wpdb->get_charset_collate();

    // SQL for Sell Details Table.
    $sql1 = "CREATE TABLE $sell_details_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        table_number VARCHAR(50) NOT NULL,
        fname VARCHAR(255) NOT NULL,
        lname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        company_name VARCHAR(50) NOT NULL,
        table_type ENUM('Full Table', 'Half Table', 'Individual') NOT NULL,
        number_of_seats INT(11) NOT NULL,
        payment_method ENUM('Card', 'Bank Deposit') DEFAULT 'Card',
        payment_status ENUM('Confirmed', 'Pending', 'Refund', 'Failed', 'Canceled') DEFAULT 'Pending',
        company_location ENUM('United Kingdom', 'Outside UK') DEFAULT 'United Kingdom',
        bin_number VARCHAR(255) DEFAULT NULL,
        total_amount DECIMAL(10,2) DEFAULT 0.00,
        total_vat DECIMAL(10,2) DEFAULT 0.00,
        vat_percentage SMALLINT(5) NOT NULL,
        payment_id VARCHAR(255) DEFAULT NULL,
        order_id VARCHAR(255) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL for Table Details Table.
    $sql2 = "CREATE TABLE $table_details_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        table_number VARCHAR(50) NOT NULL UNIQUE,
        table_status ENUM('Unsold', 'Sold') DEFAULT 'Unsold',
        table_type ENUM('Full Table', 'Half Table', 'Individual') NOT NULL,
        sell_seats INT(11) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include WordPress upgrade file to execute SQL.
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Execute the SQL to create tables.
    dbDelta($sql1);
    dbDelta($sql2);
}