<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canuuni E-Commerce Store</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .product-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important;
        }
        .category-list-group .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .product-img-container {
            height: 220px;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }

        /* Mobile Responsive Enhancements (320px - 768px) */
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }
            /* 2-Column Product Grid on Mobile */
            .mobile-product-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }
            .mobile-product-grid > .col {
                width: 100% !important;
                padding: 0 !important;
            }
            /* Image scaling and card typography adjustments for small screens */
            .product-img-container {
                height: 130px !important;
            }
            .card-body {
                padding: 10px !important;
            }
            .card-title {
                font-size: 0.85rem !important;
                line-height: 1.25rem !important;
                height: 2.5rem;
            }
            .card-text {
                font-size: 0.7rem !important;
                display: none; /* Hide long description on tiny screens to save space */
            }
            .fs-4 {
                font-size: 1rem !important;
            }
            .btn-add-to-cart, .btn-preorder {
                font-size: 0.75rem !important;
                padding: 6px 10px !important;
            }
            .badge {
                font-size: 0.65rem !important;
                padding: 0.25em 0.4em !important;
            }
            /* General mobile container adjustments */
            main.container {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">