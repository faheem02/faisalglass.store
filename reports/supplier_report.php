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

// Date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// Apply quick filters
if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'yesterday') {
    $from_date = date('Y-m-d', strtotime('-1 day'));
    $to_date = date('Y-m-d', strtotime('-1 day'));
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

// Get suppliers for filter
$suppliers_query = "SELECT id, supplier_name, supplier_code FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Get supplier data with ledger calculations
$supplier_where = "";
if($supplier_filter > 0) {
    $supplier_where = " AND id = $supplier_filter";
}

$suppliers_data_query = "SELECT id, supplier_code, supplier_name, company_name, contact_person, mobile, email, 
                         opening_balance, balance_type, current_balance, status, created_at
                         FROM suppliers 
                         WHERE status = 1 $supplier_where
                         ORDER BY supplier_name ASC";
$suppliers_result_data = mysqli_query($conn, $suppliers_data_query);

// Calculate summaries
$total_suppliers = 0;
$total_payable = 0;
$total_receivable_from_suppliers = 0;
$supplier_data = [];

while($supplier = mysqli_fetch_assoc($suppliers_result_data)) {
    $supplier_id = $supplier['id'];
    $current_balance = floatval($supplier['current_balance']);
    
    // Get purchase amount for the period
    $purchase_query = "SELECT COALESCE(SUM(grand_total), 0) as purchase_amount 
                       FROM purchase_master 
                       WHERE supplier_id = $supplier_id 
                       AND purchase_date BETWEEN '$from_date' AND '$to_date'";
    $purchase_result = mysqli_query($conn, $purchase_query);
    $purchase_amount = floatval(mysqli_fetch_assoc($purchase_result)['purchase_amount']);
    
    // Get paid amount for the period
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as paid_amount 
                   FROM supplier_payments 
                   WHERE supplier_id = $supplier_id 
                   AND payment_date BETWEEN '$from_date' AND '$to_date'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_amount = floatval(mysqli_fetch_assoc($paid_result)['paid_amount']);
    
    // Get opening balance
    $opening_balance = floatval($supplier['opening_balance']);
    if($supplier['balance_type'] == 'receivable') {
        $opening_balance = -$opening_balance;
    }
    
    $supplier_data[] = [
        'id' => $supplier_id,
        'supplier_code' => $supplier['supplier_code'],
        'supplier_name' => $supplier['supplier_name'],
        'company_name' => $supplier['company_name'],
        'contact_person' => $supplier['contact_person'],
        'mobile' => $supplier['mobile'],
        'email' => $supplier['email'],
        'opening_balance' => $opening_balance,
        'purchase_amount' => $purchase_amount,
        'paid_amount' => $paid_amount,
        'current_balance' => $current_balance,
        'status' => $supplier['status'],
        'created_at' => $supplier['created_at']
    ];
    
    $total_suppliers++;
    if($current_balance > 0) {
        $total_payable += $current_balance;
    } elseif($current_balance < 0) {
        $total_receivable_from_suppliers += abs($current_balance);
    }
}

$page_title = "Supplier Report";
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
        .supplier-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .supplier-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .supplier-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }
        
        .supplier-table thead th.text-right {
            text-align: right;
        }
        
        .supplier-table thead th.text-center {
            text-align: center;
        }
        
        .supplier-table tbody tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }
        
        .supplier-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .supplier-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .supplier-table tbody td.text-right {
            text-align: right;
        }
        
        .supplier-table tbody td.text-center {
            text-align: center;
        }
        
        /* Supplier Code Badge */
        .supplier-code {
            background: #e3f2fd;
            color: #4e73df;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            display: inline-block;
        }
        
        .supplier-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        /* Balance Badges */
        .balance-payable {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .balance-receivable {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .balance-zero {
            background: #6c757d;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Amount Styles */
        .amount-positive {
            color: #dc3545;
            font-weight: 700;
        }
        
        /* Total Value Badge */
        .total-value {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 6px 15px;
            border-radius: 25px;
            display: inline-block;
            font-weight: 700;
            font-size: 13px;
        }
        
        /* Action Buttons */
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #36b9cc;
            color: white;
            border-radius: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .action-btn:hover {
            background: #2c9faf;
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        /* Table Footer */
        .table-footer {
            background: linear-gradient(135deg, #f8f9fc, #eef2f7);
            font-weight: 700;
        }
        
        .table-footer td {
            padding: 15px 10px !important;
            border-top: 2px solid #dc3545;
        }
        
        /* Header Stats */
        .header-stats .stat-badge {
            background: #e3f2fd;
            color: #4e73df;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 280px;
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
        
        @media (max-width: 768px) {
            .supplier-table thead th { font-size: 9px; padding: 10px 5px; }
            .supplier-table tbody td { padding: 10px 5px; font-size: 10px; }
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
                            <i class="fas fa-truck"></i> Supplier Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.open('print_supplier_report.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&filter_type=<?php echo urlencode($filter_type); ?>&supplier_id=<?php echo $supplier_filter; ?>', '_blank', 'width=1000,height=750')" class="btn btn-secondary btn-sm">
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
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Suppliers</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo $total_suppliers; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-danger">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Payable</div>
                                <div class="stat-number" style="color: #dc3545;">₨ <?php echo number_format($total_payable, 2); ?></div>
                                <small class="text-muted">Company owes supplier</small>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Supplier Receivable</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_receivable_from_suppliers, 2); ?></div>
                                <small class="text-muted">Supplier owes company</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Suppliers Chart -->
                    <!--<div class="row mb-4 no-print">-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-bar"></i> Top 5 Suppliers by Payable-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="topSuppliersChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-pie"></i> Balance Distribution-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="balanceChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today&supplier_id=<?php echo $supplier_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=yesterday&supplier_id=<?php echo $supplier_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'yesterday' ? 'btn-success' : 'btn-outline-success'; ?>">Yesterday</a>
                                <a href="?filter_type=week&supplier_id=<?php echo $supplier_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month&supplier_id=<?php echo $supplier_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year&supplier_id=<?php echo $supplier_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
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
                                <select name="supplier_id" class="form-control form-control-sm">
                                    <option value="0">-- All Suppliers --</option>
                                    <?php 
                                    mysqli_data_seek($suppliers_result, 0);
                                    while($s = mysqli_fetch_assoc($suppliers_result)): 
                                    ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $supplier_filter == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['supplier_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="supplier_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #dc3545;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h6 class="m-0 font-weight-bold" style="color: #dc3545;">
                                    <i class="fas fa-list"></i> Supplier Ledger Summary
                                    (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                                </h6>
                                <div class="header-stats">
                                    <span class="stat-badge">
                                        <i class="fas fa-truck"></i> Total: <?php echo $total_suppliers; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="supplier-table" id="supplierTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">SUPPLIER CODE</th>
                                            <th width="18%">SUPPLIER NAME</th>
                                            <th width="12%">COMPANY</th>
                                            <th width="10%">CONTACT</th>
                                            <th width="10%">MOBILE</th>
                                            <th width="10%" class="text-right">OPENING</th>
                                            <th width="10%" class="text-right">PURCHASES</th>
                                            <th width="10%" class="text-right">PAID</th>
                                            <th width="12%" class="text-right">CURRENT BALANCE</th>
                                            <th width="8%" class="text-center">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($supplier_data)): ?>
                                            <?php foreach($supplier_data as $supplier): ?>
                                                <tr>
                                                    <td>
                                                        <span class="supplier-code"><?php echo htmlspecialchars($supplier['supplier_code']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="supplier-name"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
                                                        <?php if(!empty($supplier['email'])): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($supplier['email']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($supplier['company_name'] ?? '—'); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '—'); ?></td>
                                                    <td><?php echo htmlspecialchars($supplier['mobile']); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($supplier['opening_balance'], 2); ?></td>
                                                    <td class="text-right amount-positive">₨ <?php echo number_format($supplier['purchase_amount'], 2); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($supplier['paid_amount'], 2); ?></td>
                                                    <td class="text-right">
                                                        <?php if($supplier['current_balance'] > 0): ?>
                                                            <span class="balance-payable">
                                                                ₨ <?php echo number_format($supplier['current_balance'], 2); ?> CR
                                                            </span>
                                                        <?php elseif($supplier['current_balance'] < 0): ?>
                                                            <span class="balance-receivable">
                                                                ₨ <?php echo number_format(abs($supplier['current_balance']), 2); ?> DR
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="balance-zero">
                                                                ₨ 0.00
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="../suppliers/supplier_detail.php?id=<?php echo $supplier['id']; ?>" 
                                                           class="action-btn" target="_blank" title="View Supplier">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="../suppliers/supplier_ledger.php?id=<?php echo $supplier['id']; ?>" 
                                                           class="action-btn ml-1" target="_blank" title="View Ledger">
                                                            <i class="fas fa-book"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-5">
                                                    <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No suppliers found</h5>
                                                    <p class="text-muted">No supplier data available</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-footer">
                                            <td colspan="6" class="text-right"><strong>TOTALS:</strong></td>
                                            <td class="text-right amount-positive">
                                                <strong>₨ <?php echo number_format(array_sum(array_column($supplier_data, 'purchase_amount')), 2); ?></strong>
                                            </td>
                                            <td class="text-right">
                                                <strong>₨ <?php echo number_format(array_sum(array_column($supplier_data, 'paid_amount')), 2); ?></strong>
                                            </td>
                                            <td class="text-right">
                                                <span class="total-value">
                                                    ₨ <?php echo number_format($total_payable - $total_receivable_from_suppliers, 2); ?>
                                                </span>
                                            </td>
                                            <td class="text-center"></td>
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
            $('#supplierTable').DataTable({
                "pageLength": 25,
                "order": [[8, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No supplier data available",
                    "zeroRecords": "No matching suppliers found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [9] }
                ]
            });
            
            // Top Suppliers Chart
            var topSuppliers = <?php 
                $top_suppliers = array_slice($supplier_data, 0, 5);
                $supplier_names = array_map(function($item) { 
                    return strlen($item['supplier_name']) > 20 ? substr($item['supplier_name'], 0, 17) . '...' : $item['supplier_name']; 
                }, $top_suppliers);
                $supplier_payables = array_map(function($item) { return $item['current_balance'] > 0 ? $item['current_balance'] : 0; }, $top_suppliers);
                echo json_encode(['names' => $supplier_names, 'payables' => $supplier_payables]);
            ?>;
            
            if(topSuppliers.names.length > 0 && topSuppliers.payables.length > 0 && Math.max(...topSuppliers.payables) > 0) {
                var ctx1 = document.getElementById('topSuppliersChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: topSuppliers.names,
                        datasets: [{
                            label: 'Payable Amount (₨)',
                            data: topSuppliers.payables,
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: '#dc3545',
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
            
            // Balance Distribution Chart
            var payableCount = <?php echo count(array_filter($supplier_data, function($item) { return $item['current_balance'] > 0; })); ?>;
            var receivableCount = <?php echo count(array_filter($supplier_data, function($item) { return $item['current_balance'] < 0; })); ?>;
            var zeroCount = <?php echo count(array_filter($supplier_data, function($item) { return $item['current_balance'] == 0; })); ?>;
            
            if(payableCount > 0 || receivableCount > 0 || zeroCount > 0) {
                var ctx2 = document.getElementById('balanceChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: ['Payable (We Owe Supplier)', 'Receivable (Supplier Owes Us)', 'Settled'],
                        datasets: [{
                            data: [payableCount, receivableCount, zeroCount],
                            backgroundColor: ['#dc3545', '#28a745', '#6c757d'],
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
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('supplierTable');
                var html = table.cloneNode(true);
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html.outerHTML + '</body></html>');
                var link = document.createElement('a');
                link.download = 'supplier_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#supplierTable tr');
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
                link.download = 'supplier_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>