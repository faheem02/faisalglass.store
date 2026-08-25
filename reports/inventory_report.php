<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

// Filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$company_filter = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$product_search = isset($_GET['product_search']) ? $_GET['product_search'] : '';

// Apply quick filters
if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'year') {
    $from_date = date('Y-01-01');
    $to_date = date('Y-m-d');
}

// Get categories for filter
$categories_query = "SELECT id, category_name FROM categories WHERE status = 1 ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);

// Get companies for filter
$companies_query = "SELECT id, company_name FROM companies WHERE status = 1 ORDER BY company_name";
$companies_result = mysqli_query($conn, $companies_query);

// Get all products with stock calculations
$products_query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN companies comp ON p.company_id = comp.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1";

if($category_filter > 0) {
    $products_query .= " AND p.category_id = $category_filter";
}
if($company_filter > 0) {
    $products_query .= " AND p.company_id = $company_filter";
}
if(!empty($product_search)) {
    $products_query .= " AND (p.product_name LIKE '%$product_search%' OR p.product_code LIKE '%$product_search%')";
}

$products_query .= " ORDER BY p.product_name ASC";
$products_result = mysqli_query($conn, $products_query);

// Calculate stock for each product
$inventory_data = [];
$total_stock_value = 0;
$total_products = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

while($product = mysqli_fetch_assoc($products_result)) {
    $product_id = $product['id'];
    
    // Get sizes for this product
    $sizes_query = "SELECT * FROM product_sizes WHERE product_id = $product_id ORDER BY id ASC";
    $sizes_result = mysqli_query($conn, $sizes_query);
    $sizes_list = [];
    while($sz = mysqli_fetch_assoc($sizes_result)) {
        $sizes_list[] = $sz;
    }
    
    // Get opening stock (before from_date)
    $opening_query = "SELECT COALESCE(SUM(qty_in) - SUM(qty_out), 0) as opening_stock
                      FROM inventory_ledger 
                      WHERE product_id = $product_id AND date < '$from_date'";
    $opening_result = mysqli_query($conn, $opening_query);
    $opening_stock = floatval(mysqli_fetch_assoc($opening_result)['opening_stock']);
    
    // Get purchases between dates
    $purchase_query = "SELECT COALESCE(SUM(qty_in), 0) as purchased_qty
                       FROM inventory_ledger 
                       WHERE product_id = $product_id 
                       AND reference_type = 'PURCHASE'
                       AND date BETWEEN '$from_date' AND '$to_date'";
    $purchase_result = mysqli_query($conn, $purchase_query);
    $purchased_qty = floatval(mysqli_fetch_assoc($purchase_result)['purchased_qty']);
    
    // Get sales between dates (SALE + QUOTATION both reduce stock)
    $sale_query = "SELECT COALESCE(SUM(qty_out), 0) as sold_qty
                   FROM inventory_ledger 
                   WHERE product_id = $product_id 
                   AND reference_type IN ('SALE', 'QUOTATION')
                   AND date BETWEEN '$from_date' AND '$to_date'";
    $sale_result = mysqli_query($conn, $sale_query);
    $sold_qty = floatval(mysqli_fetch_assoc($sale_result)['sold_qty']);
    
    // Get current stock
    $current_stock_query = "SELECT COALESCE(SUM(qty_in) - SUM(qty_out), 0) as current_stock
                            FROM inventory_ledger 
                            WHERE product_id = $product_id";
    $current_stock_result = mysqli_query($conn, $current_stock_query);
    $current_stock = floatval(mysqli_fetch_assoc($current_stock_result)['current_stock']);
    
    $stock_value = $current_stock * floatval($product['purchase_price']);
    $total_stock_value += $stock_value;
    $total_products++;
    
    if($current_stock <= 0) {
        $out_of_stock_count++;
    } elseif($current_stock <= $product['min_stock_alert']) {
        $low_stock_count++;
    }
    
    $base_row = [
        'id' => $product_id,
        'product_code' => $product['product_code'],
        'product_name' => $product['product_name'],
        'category_name' => $product['category_name'],
        'company_name' => $product['company_name'],
        'unit_name' => $product['unit_name'] ?? 'Pcs',
        'purchase_price' => floatval($product['purchase_price']),
        'sale_price' => floatval($product['sale_price']),
        'min_stock_alert' => $product['min_stock_alert'],
        'purchased_qty' => $purchased_qty,
        'sold_qty' => $sold_qty,
        'current_stock' => $current_stock,
        'stock_value' => $stock_value,
        'is_size_row' => false,
        'size_label' => null,
        'size_pieces' => null,
        'size_area' => null
    ];
    
    if(count($sizes_list) > 1) {
        // Multi-size product: one row per size
        foreach($sizes_list as $sz) {
            $sz_opening_query = "SELECT COALESCE(SUM(quantity),0) as area, COALESCE(SUM(pieces),0) as pieces
                                 FROM opening_stock 
                                 WHERE product_id = $product_id AND product_size_id = " . $sz['id'];
            $sz_opening_result = mysqli_query($conn, $sz_opening_query);
            $sz_open = mysqli_fetch_assoc($sz_opening_result);
            
            $sz_pieces_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                                FROM opening_stock 
                                WHERE product_id = $product_id AND product_size_id = " . $sz['id'];
            $sz_pieces_result = mysqli_query($conn, $sz_pieces_query);
            $sz_pieces = mysqli_fetch_assoc($sz_pieces_result);
            
            $row = $base_row;
            $row['is_size_row'] = true;
            $row['size_label'] = $sz['size_label'];
            $row['size_pieces'] = floatval($sz_pieces['pieces']);
            $row['size_area'] = floatval($sz_open['area']);
            $row['opening_stock'] = $row['size_area'];
            $sz_rate = floatval($sz['purchase_rate'] ?? 0);
            if($sz_rate > 0) $row['purchase_price'] = $sz_rate;
            $inventory_data[] = $row;
        }
    } else {
        // Single/zero-size product: one row with product-level data
        $row = $base_row;
        if(count($sizes_list) == 1) {
            $row['size_label'] = $sizes_list[0]['size_label'];
            $sz_opening_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                                 FROM opening_stock 
                                 WHERE product_id = $product_id AND product_size_id = " . $sizes_list[0]['id'];
            $sz_opening_result = mysqli_query($conn, $sz_opening_query);
            $sz_open = mysqli_fetch_assoc($sz_opening_result);
            $row['size_pieces'] = floatval($sz_open['pieces']);
        } else {
            // No explicit size entries - use product's own dimensions (products.length_feet / width_feet)
            if(floatval($product['length_feet']) > 0 && floatval($product['width_feet']) > 0) {
                $row['size_label'] = rtrim(rtrim(number_format($product['length_feet'], 2), '0'), '.')
                                   . ' x ' . rtrim(rtrim(number_format($product['width_feet'], 2), '0'), '.') . ' ft';
            }
            $os_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                         FROM opening_stock 
                         WHERE product_id = $product_id AND product_size_id IS NULL";
            $os_result = mysqli_query($conn, $os_query);
            $os_row = mysqli_fetch_assoc($os_result);
            $row['size_pieces'] = floatval($os_row['pieces']);
        }
        $row['opening_stock'] = $opening_stock;
        $inventory_data[] = $row;
    }
}

$page_title = "Inventory Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }
        
        /* Summary Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 28px; font-weight: bold; }
        
        /* Topbar */
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        /* Data Table Wrapper */
        .data-table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        /* Table Styles */
        .inventory-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .inventory-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .inventory-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }
        
        .inventory-table thead th.text-right {
            text-align: right;
        }
        
        .inventory-table thead th.text-center {
            text-align: center;
        }
        
        .inventory-table tbody tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }
        
        .inventory-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .inventory-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .inventory-table tbody td.text-right {
            text-align: right;
        }
        
        .inventory-table tbody td.text-center {
            text-align: center;
        }
        
        /* Product Code Badge */
        .product-code {
            background: #e8f5e9;
            color: #1e7e34;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            display: inline-block;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        /* Stock Badges */
        .stock-normal {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .stock-low {
            background: #ffc107;
            color: #2c3e50;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .stock-out {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Amount */
        .amount-text {
            font-weight: 700;
            color: #1e7e34;
            font-size: 12px;
        }
        
        .size-label-badge {
            background: #e3f2fd;
            color: #0066cc;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .size-sub-row td {
            background-color: #f8fbff;
        }
        
        /* Table Footer */
        .table-footer {
            background: linear-gradient(135deg, #f8f9fc, #eef2f7);
            font-weight: 700;
        }
        
        .total-value {
            background: linear-gradient(135deg, #1e7e34, #4e73df);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-block;
            font-weight: 700;
            font-size: 13px;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        /* Header Stats */
        .stat-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .stat-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .stat-badge.danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media print {
            body { background: #fff !important; }
            #wrapper { margin: 0 !important; }
            #accordionSidebar, .topbar, .sticky-footer, .scroll-to-top,
            .no-print, .modal, .modal-backdrop, .dataTables_length,
            .dataTables_filter, .dataTables_info, .dataTables_paginate,
            .dataTables_wrapper > .row:first-child, .dataTables_wrapper > .row:last-child {
                display: none !important;
            }
            .container-fluid { padding: 0 !important; }
            .card-header, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* DataTables Custom */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 20px;
            padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #1e7e34, #4e73df) !important;
            color: white !important;
            border: none;
            border-radius: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .inventory-table thead th { font-size: 9px; padding: 10px 5px; }
            .inventory-table tbody td { padding: 10px 5px; font-size: 10px; }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)
                        </span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-boxes"></i> Inventory Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.open('print_inventory_report.php?from_date=<?php echo urlencode($from_date); ?>&amp;to_date=<?php echo urlencode($to_date); ?>&amp;filter_type=<?php echo urlencode($filter_type); ?>&amp;category_id=<?php echo $category_filter; ?>&amp;company_id=<?php echo $company_filter; ?>&amp;product_search=<?php echo urlencode($product_search); ?>', '_blank', 'width=1100,height=700')" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button id="exportExcelBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button id="exportCSVBtn" class="btn btn-info btn-sm">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Products</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo $total_products; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Stock Value</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_stock_value, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Products</div>
                                <div class="stat-number" style="color: #f6c23e;"><?php echo $low_stock_count; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-danger">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Out of Stock</div>
                                <div class="stat-number" style="color: #e74a3b;"><?php echo $out_of_stock_count; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Products Chart -->
                    <!--<div class="row mb-4 no-print">-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-bar"></i> Top 5 Products by Stock Value-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="topProductsChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-pie"></i> Stock Status Distribution-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="stockStatusChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=week" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <label class="mr-2">From:</label>
                                <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo $from_date; ?>">
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <label class="mr-2 ml-md-3">To:</label>
                                <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo $to_date; ?>">
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <select name="category_id" class="form-control form-control-sm">
                                    <option value="0">-- All Categories --</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while($cat = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <select name="company_id" class="form-control form-control-sm">
                                    <option value="0">-- All Companies --</option>
                                    <?php 
                                    mysqli_data_seek($companies_result, 0);
                                    while($comp = mysqli_fetch_assoc($companies_result)): 
                                    ?>
                                    <option value="<?php echo $comp['id']; ?>" <?php echo $company_filter == $comp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($comp['company_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <input type="text" name="product_search" class="form-control form-control-sm" 
                                       placeholder="Search Product" value="<?php echo htmlspecialchars($product_search); ?>">
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="inventory_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #1e7e34;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                    <i class="fas fa-list"></i> Inventory Stock Report
                                    (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                                </h6>
                                <div class="header-stats">
                                    <span class="stat-badge warning">
                                        <i class="fas fa-exclamation-triangle"></i> Low Stock: <?php echo $low_stock_count; ?>
                                    </span>
                                    <span class="stat-badge danger ml-2">
                                        <i class="fas fa-times-circle"></i> Out of Stock: <?php echo $out_of_stock_count; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
    <div class="table-responsive">
        <table class="inventory-table" id="inventoryTable">
            <thead>
                <tr>
                    <th width="10%">PRODUCT CODE</th>
                    <th width="15%">PRODUCT NAME</th>
                    <th width="9%">SIZE</th>
                    <th width="9%">CATEGORY</th>
                    <th width="9%">COMPANY</th>
                    <th width="8%" class="text-right">OPENING</th>
                    <th width="8%" class="text-right">PURCHASED</th>
                    <th width="7%" class="text-right">SOLD</th>
                    <th width="7%" class="text-right">CURRENT</th>
                    <th width="7%" class="text-right">UNIT PRICE</th>
                    <th width="8%" class="text-right">STOCK VALUE</th>
                    <th width="7%" class="text-center">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($inventory_data)): ?>
                    <?php foreach($inventory_data as $index => $item): ?>
                        <?php $is_size = $item['is_size_row']; ?>
                        <tr<?php echo $is_size ? ' class="size-sub-row"' : ''; ?>>
                            <td>
                                <span class="product-code"><?php echo htmlspecialchars($item['product_code']); ?></span>
                            </td>
                            <td>
                                <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($item['unit_name']); ?></small>
                            </td>
                            <td>
                                <?php if($item['size_label']): ?>
                                    <span class="size-label-badge"><?php echo htmlspecialchars($item['size_label']); ?></span>
                                    <?php if($item['size_pieces'] !== null): ?>
                                        <small class="text-muted d-block"><?php echo number_format($item['size_pieces'], 0); ?> pcs</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['company_name'] ?? 'N/A'); ?></td>
                            <td class="text-right"><?php echo number_format($item['opening_stock'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($item['purchased_qty'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($item['sold_qty'], 2); ?></td>
                            <td class="text-right"><strong><?php echo number_format($item['current_stock'], 2); ?></strong></td>
                            <td class="text-right"><span class="amount-text">₨ <?php echo number_format($item['purchase_price'], 2); ?></span></td>
                            <td class="text-right"><span class="amount-text">₨ <?php echo number_format($item['stock_value'], 2); ?></span></td>
                            <td class="text-center">
                                <?php if($item['current_stock'] <= 0): ?>
                                    <span class="stock-out">
                                        <i class="fas fa-times-circle"></i> Out
                                    </span>
                                <?php elseif($item['current_stock'] <= $item['min_stock_alert']): ?>
                                    <span class="stock-low">
                                        <i class="fas fa-exclamation-triangle"></i> Low
                                    </span>
                                <?php else: ?>
                                    <span class="stock-normal">
                                        <i class="fas fa-check-circle"></i> Normal
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                            <h5>No products found</h5>
                            <p class="text-muted">No inventory data available</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-footer">
                    <td colspan="10" class="text-right"><strong>TOTAL STOCK VALUE:</strong></td>
                    <td class="text-right">
                        <span class="total-value">
                            ₨ <?php echo number_format($total_stock_value, 2); ?>
                        </span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
    
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                "pageLength": 25,
                "order": [[8, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No inventory data available",
                    "zeroRecords": "No matching products found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [11] }
                ]
            });
            
            // Top Products Chart
            var topProducts = <?php 
                $top_products = array_slice($inventory_data, 0, 5);
                $product_names = array_map(function($item) { return $item['product_name']; }, $top_products);
                $product_values = array_map(function($item) { return $item['stock_value']; }, $top_products);
                echo json_encode(['names' => $product_names, 'values' => $product_values]);
            ?>;
            
            if(topProducts.names.length > 0) {
                var chartEl1 = document.getElementById('topProductsChart');
                if(chartEl1) {
                var ctx1 = chartEl1.getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: topProducts.names,
                        datasets: [{
                            label: 'Stock Value (₨)',
                            data: topProducts.values,
                            backgroundColor: 'rgba(30, 126, 52, 0.7)',
                            borderColor: '#1e7e34',
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₨ ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                }
            }
            
            // Stock Status Chart
            var normalCount = <?php 
                $normal = count(array_filter($inventory_data, function($item) { 
                    return $item['current_stock'] > 0 && $item['current_stock'] > $item['min_stock_alert']; 
                }));
                echo $normal;
            ?>;
            var lowCount = <?php echo $low_stock_count; ?>;
            var outCount = <?php echo $out_of_stock_count; ?>;
            
            if(normalCount > 0 || lowCount > 0 || outCount > 0) {
                var chartEl2 = document.getElementById('stockStatusChart');
                if(chartEl2) {
                var ctx2 = chartEl2.getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [normalCount, lowCount, outCount],
                            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
                }
            }
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('inventoryTable');
                var html = table.cloneNode(true);
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html.outerHTML + '</body></html>');
                var link = document.createElement('a');
                link.download = 'inventory_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#inventoryTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    for (var j = 0; j < cols.length; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
                var link = document.createElement('a');
                link.download = 'inventory_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>