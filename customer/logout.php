<?php
// customer/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear customer-specific session variables
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_code']);

// Redirect to index page
header('Location: ../index');
exit;