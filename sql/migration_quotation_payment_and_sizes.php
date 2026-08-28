<?php
include(__DIR__ . '/../includes/database.php');

$queries = [
    // 1. quotation_master
    "ALTER TABLE quotation_master ADD COLUMN IF NOT EXISTS received_amount decimal(15,2) DEFAULT 0.00 AFTER grand_total",
    "ALTER TABLE quotation_master ADD COLUMN IF NOT EXISTS remaining_amount decimal(15,2) DEFAULT 0.00 AFTER received_amount",
    "ALTER TABLE quotation_master ADD COLUMN IF NOT EXISTS payment_type enum('cash','bank','credit','partial') DEFAULT 'credit' AFTER remaining_amount",
    "ALTER TABLE quotation_master ADD COLUMN IF NOT EXISTS bank_account_id int(11) DEFAULT NULL AFTER payment_type",

    // 2. quotation_details
    "ALTER TABLE quotation_details ADD COLUMN IF NOT EXISTS client_size varchar(100) DEFAULT NULL AFTER client_width",
    "ALTER TABLE quotation_details ADD COLUMN IF NOT EXISTS multiple_of int(11) DEFAULT 6 AFTER client_size",
    "ALTER TABLE quotation_details ADD COLUMN IF NOT EXISTS rate decimal(15,2) DEFAULT 0.00 AFTER area",

    // 3. hold_quotations_master
    "ALTER TABLE hold_quotations_master ADD COLUMN IF NOT EXISTS received_amount decimal(15,2) DEFAULT 0.00 AFTER grand_total",
    "ALTER TABLE hold_quotations_master ADD COLUMN IF NOT EXISTS remaining_amount decimal(15,2) DEFAULT 0.00 AFTER received_amount",
    "ALTER TABLE hold_quotations_master ADD COLUMN IF NOT EXISTS payment_type enum('cash','bank','credit','partial') DEFAULT 'credit' AFTER remaining_amount",
    "ALTER TABLE hold_quotations_master ADD COLUMN IF NOT EXISTS bank_account_id int(11) DEFAULT NULL AFTER payment_type",

    // 4. hold_quotations_details
    "ALTER TABLE hold_quotations_details ADD COLUMN IF NOT EXISTS client_size varchar(100) DEFAULT NULL AFTER client_width",
    "ALTER TABLE hold_quotations_details ADD COLUMN IF NOT EXISTS multiple_of int(11) DEFAULT 6 AFTER client_size",
    "ALTER TABLE hold_quotations_details ADD COLUMN IF NOT EXISTS rate decimal(15,2) DEFAULT 0.00 AFTER area"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "OK: $q\n";
    } else {
        echo "ERR: " . mysqli_error($conn) . " on query: $q\n";
    }
}
echo "Migration finished.\n";
