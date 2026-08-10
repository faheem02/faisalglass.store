<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?><?php echo $software_name; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom CSS - Vibrant Green & Blue Theme -->
    <style>
        /* Custom Theme Colors - Vibrant Green & Blue */
        :root {
            --primary-green: #059669;
            --primary-blue: #2563eb;
            --light-green: #10b981;
            --light-blue: #3b82f6;
            --dark-green: #047857;
            --dark-blue: #1d4ed8;
            --success-bg: #d1fae5;
            --info-bg: #dbeafe;
            --warning-bg: #fef3c7;
            --danger-bg: #fee2e2;
            --gradient-start: #059669;
            --gradient-end: #2563eb;
        }
        
        /* Body and Text Styles */
        body {
            font-size: 16px;
            color: #1f2937;
            background: linear-gradient(135deg, #f0f9ff 0%, #e8f5e9 100%);
            font-family: 'Poppins', sans-serif;
        }
        
        /* Strong contrast for all text */
        .text-muted {
            color: #6b7280 !important;
            font-weight: 500;
        }
        
        /* Sidebar Customization - Premium Dark Green Gradient */
        .sidebar {
            background: linear-gradient(180deg, #064e3b 0%, #065f46 50%, #047857 100%);
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #f0fdf4 !important;
            font-weight: 500;
            padding: 0.85rem 1rem;
            margin: 0.25rem 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: linear-gradient(90deg, #10b981, #3b82f6);
            color: #ffffff !important;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, #10b981, #3b82f6);
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        }
        
        .sidebar .nav-link i {
            color: #86efac;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link.active i {
            color: #ffffff;
        }
        
        .sidebar-heading {
            color: #86efac !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.7rem;
        }
        
        /* Sidebar Brand */
        .sidebar-brand {
            background: linear-gradient(135deg, #047857, #1d4ed8);
            padding: 1.5rem 0;
            margin-bottom: 1rem;
        }
        
        .sidebar-brand-text {
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: 1px;
        }
        
        /* Top Navbar - Vibrant Blue Gradient */
        .navbar-top {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 0.75rem 1.5rem;
        }
        
        .navbar-top .nav-link {
            color: #ffffff !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar-top .nav-link:hover {
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            transform: translateY(-2px);
        }
        
        /* Search Bar */
        .navbar-search .form-control {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white !important;
            border-radius: 30px;
            padding: 0.6rem 1.2rem;
        }
        
        .navbar-search .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .navbar-search .btn-light {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            border-radius: 30px;
        }
        
        .navbar-search .btn-light:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Cards with Green/Blue gradients */
        .card-stats {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-left: 4px solid;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .card-stats-green {
            border-left-color: #10b981;
        }
        
        .card-stats-blue {
            border-left-color: #3b82f6;
        }
        
        /* Buttons */
        .btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-green:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(5,150,105,0.3);
            color: #ffffff;
        }
        
        .btn-blue {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-blue:hover {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37,99,235,0.3);
            color: #ffffff;
        }
        
        /* Table Styles */
        .table {
            color: #1f2937;
            font-size: 13px;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #059669, #2563eb);
            color: #ffffff;
            font-weight: 600;
            border: none;
            padding: 12px;
        }
        
        .table tbody tr:hover {
            background-color: #f0fdf4;
            transition: all 0.2s ease;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            color: #1f2937;
            font-weight: 500;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
        }
        
        /* Labels */
        label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            color: #1f2937;
            font-weight: 700;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: linear-gradient(135deg, #f0fdf4, #eff6ff);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #d1fae5;
        }
        
        .breadcrumb-item a {
            color: #059669;
            font-weight: 600;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: #2563eb;
        }
        
        .breadcrumb-item.active {
            color: #2563eb;
            font-weight: 600;
        }
        
        /* Page Header */
        .page-header-custom {
            background: linear-gradient(135deg, #059669, #2563eb);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .page-header-custom h1, .page-header-custom h2 {
            color: #ffffff;
            margin: 0;
        }
        
        .page-header-custom p {
            margin: 0;
            opacity: 0.95;
        }
        
        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 0.5rem;
            background: white;
        }
        
        .dropdown-item {
            color: #1f2937;
            font-weight: 500;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(90deg, #ecfdf5, #eff6ff);
            color: #059669;
        }
        
        .dropdown-header {
            background: linear-gradient(135deg, #059669, #2563eb);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        /* Alerts */
        .alert {
            font-weight: 500;
            border-radius: 12px;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        /* User Dropdown Avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                font-size: 15px;
            }
            
            .sidebar {
                width: 260px;
            }
            
            .card-stats {
                margin-bottom: 1rem;
            }
            
            .page-header-custom {
                padding: 1rem;
            }
            
            .navbar-top {
                padding: 0.5rem 1rem;
            }
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1f2937, #111827);
            border-top: none;
            padding: 1.5rem;
            margin-top: 3rem;
            text-align: center;
            color: #9ca3af;
            font-weight: 500;
        }
        
        /* Scroll to Top Button */
        .scroll-to-top {
            background: linear-gradient(135deg, #059669, #2563eb);
            color: white;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .scroll-to-top:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #047857, #1d4ed8);
        }
        
        /* Card Styles */
        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 2px solid #10b981;
            font-weight: 700;
        }
        
        /* Modal Styles */
        .modal-header {
            background: linear-gradient(135deg, #059669, #2563eb);
            color: white;
        }
        
        .modal-header .close {
            color: white;
        }
        
        /* Badge Styles */
        .badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .badge-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        /* Pagination */
        .page-item.active .page-link {
            background: linear-gradient(135deg, #059669, #2563eb);
            border-color: #059669;
        }
        
        .page-link {
            color: #059669;
        }
        
        /* Progress Bar */
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }
    </style>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body id="page-top">
    
<div id="wrapper">