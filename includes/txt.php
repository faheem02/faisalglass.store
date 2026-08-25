<?php
/**
 * Software Configuration File
 * Faysal Glass And Aluminium Centre
 * 
 * This file contains global software variables and text strings
 * Must be included in all pages
 */

// Software Information
$software_name = "Faisal Glass & Aluminium Center";
$software_short_name = "FGAC";
$software_version = "1.0.0";
$company_name = "Kashif Brothers";
$company_address = "Lajna Chowk, College Road, Township, Lahore";
$company_phone = "+92 321 8825910";
$company_email = "info@faysalglass.com";

// Currency Settings
$currency_symbol = "₨";
$currency_code = "PKR";

// Date Format
$date_format = "d-m-Y";
$time_format = "h:i A";

// Business Hours
$business_start_time = "09:00:00";
$business_end_time = "22:00:00";

// Tax Settings
$gst_percentage = 0; // Set if applicable

// Session Start (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Function to format currency
 * @param float $amount Amount to format
 * @return string Formatted amount
 */
if (!function_exists('formatCurrency')) {
function formatCurrency($amount) {
    global $currency_symbol;
    return $currency_symbol . " " . number_format($amount, 2);
}
}

/**
 * Function to format date
 * @param string $date Date to format
 * @return string Formatted date
 */
if (!function_exists('formatDate')) {
function formatDate($date) {
    global $date_format;
    if ($date && $date != '0000-00-00') {
        return date($date_format, strtotime($date));
    }
    return '-';
}
}

/**
 * Function to get current datetime
 * @return string Current datetime
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Function to get current date
 * @return string Current date
 */
function getCurrentDate() {
    return date('Y-m-d');
}
?>
