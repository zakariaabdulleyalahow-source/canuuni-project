<?php
$cu_asset_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if (in_array(basename($cu_asset_base), ['customer', 'admin'], true)) {
    $cu_asset_base = rtrim(dirname($cu_asset_base), '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#17223c">
    <meta name="description" content="Discover quality electronics, everyday essentials and great value at Canuuni.">
    <title>Canuuni | Electronics & Everyday Essentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($cu_asset_base . '/assets/css/canuuni-2026.css', ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100">
