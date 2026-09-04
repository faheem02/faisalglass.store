<?php
/**
 * Database Configuration File
 * Faysal Glass And Aluminium Center
 * 
 * This file handles database connection using MySQLi
 * Last Updated: 2026-06-05
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Update with your DB username
define('DB_PASS', '');   // Update with your DB password
define('DB_NAME', 'faysal_glass_live');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8 for proper encoding
mysqli_set_charset($conn, "utf8mb4");

// Set timezone to Pakistan Standard Time
date_default_timezone_set('Asia/Karachi');

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Function to get database connection
 * @return mysqli Connection object
 */
function getConnection() {
    global $conn;
    return $conn;
}

/**
 * Function to close database connection
 */
function closeConnection() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
}

/**
 * Function to escape strings safely
 * @param string $value Value to escape
 * @return string Escaped value
 */
function escapeString($value) {
    global $conn;
    return mysqli_real_escape_string($conn, $value);
}

/**
 * Function to execute queries with prepared statements
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (s, i, d, b)
 * @param array $params Parameters for the query
 * @return mysqli_stmt|false Statement object or false
 */
function executePrepared($sql, $types = "", $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    return $stmt;
}
?>