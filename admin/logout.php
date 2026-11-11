<?php
// admin/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_role']);
// Note: branch_id is left in place since it's also used as a general
// storefront default (see config/db.php fallback) -- only the admin
// identity is cleared here.

header('Location: login');
exit;
