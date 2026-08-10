<?php
/**
 * Index Page
 * Faysal Glass And Aluminium Centre
 * 
 * Entry point - redirects to login page
 */

// Start session
session_start();

// If already logged in, redirect to dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard/dashboard.php");
    exit();
}

// Otherwise redirect to login page
header("Location: login.php");
exit();
?>