<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($customer_id == 0) {
    header("Location: view_customer.php");
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

// Fetch customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: view_customer.php");
    exit();
}

$customer = mysqli_fetch_assoc($result);

// Get sales summary from sale_master
$sales_query = "SELECT COUNT(*) as total_sales, COALESCE(SUM(grand_total), 0) as total_amount 
                FROM sale_master WHERE customer_id = $customer_id AND status = 1";
$sales_result = mysqli_query($conn, $sales_query);
if($sales_result && mysqli_num_rows($sales_result) > 0) {
    $sales_data = mysqli_fetch_assoc($sales_result);
} else {
    $sales_data = ['total_sales' => 0, 'total_amount' => 0];
}

// Get payment summary from customer_receipts
$payment_query = "SELECT COALESCE(SUM(amount), 0) as total_payments 
                  FROM customer_receipts WHERE customer_id = $customer_id";
$payment_result = mysqli_query($conn, $payment_query);
if($payment_result && mysqli_num_rows($payment_result) > 0) {
    $payment_data = mysqli_fetch_assoc($payment_result);
} else {
    $payment_data = ['total_payments' => 0];
}

// Get recent transactions from customer_ledger
$trans_query = "SELECT * FROM customer_ledger 
                WHERE customer_id = $customer_id 
                ORDER BY date DESC, id DESC LIMIT 10";
$trans_result = mysqli_query($conn, $trans_query);

// Get purchase history with product details
$purchase_query = "SELECT sm.invoice_no, sm.sale_date, sm.grand_total,
                   sd.id as detail_id, sd.product_id, sd.client_size, sd.client_height, sd.client_width,
                   sd.area, sd.quantity, sd.rate, sd.amount, sd.discount_percentage,
                   p.product_name, p.product_code
                   FROM sale_master sm
                   LEFT JOIN sale_details sd ON sm.id = sd.sale_id
                   LEFT JOIN products p ON sd.product_id = p.id
                   WHERE sm.customer_id = $customer_id AND sm.status = 1
                   ORDER BY sm.sale_date DESC, sm.id DESC
                   LIMIT 20";
$purchase_result = mysqli_query($conn, $purchase_query);

$current_balance = isset($customer['current_balance']) ? floatval($customer['current_balance']) : 0;
$balance_status = ($current_balance >= 0) ? 'Receivable' : 'Payable';
$opening_balance = isset($customer['opening_balance']) ? floatval($customer['opening_balance']) : 0;
$customer_initial = strtoupper(substr(trim($customer['customer_name'] ?? 'C'), 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Detail - <?php echo htmlspecialchars($customer['customer_name'] ?? 'Customer'); ?> | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #1e7e34;
            --primary-blue: #4e73df;
            --dark-text: #1f2937;
            --muted-text: #6b7280;
            --card-border: #e5e7eb;
        }
        
        body {
            background-color: #f4f6fb;
            color: var(--dark-text) !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* ===== Profile Banner ===== */
        .profile-banner {
            background: linear-gradient(135deg, #14532d 0%, #1e7e34 55%, #2f6fdd 100%);
            border-radius: 14px;
            padding: 24px 28px;
            color: white;
            margin: 24px 0 20px;
            box-shadow: 0 6px 18px rgba(30, 126, 52, 0.22);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        
        .profile-identity {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1 1 460px;
            min-width: 0;
        }
        
        .profile-avatar {
            width: 68px;
            height: 68px;
            min-width: 68px;
            border-radius: 50%;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .profile-name {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 4px;
            line-height: 1.2;
        }
        
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
            font-size: 13px;
            color: rgba(255,255,255,0.92);
        }
        
        .profile-meta i {
            margin-right: 5px;
            opacity: 0.85;
        }
        
        .chip-white {
            background: rgba(255,255,255,0.16);
            padding: 3px 12px;
            border-radius: 20px;
            font-family: 'Consolas', monospace;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .balance-panel {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: 12px;
            padding: 16px 24px;
            text-align: center;
            min-width: 230px;
        }
        
        .balance-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.85);
        }
        
        .balance-amount {
            font-size: 30px;
            font-weight: 800;
            margin: 4px 0;
            font-variant-numeric: tabular-nums;
        }
        
        /* ===== Contact Strip ===== */
        .contact-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }
        
        .contact-item {
            background: white;
            border: 1px solid var(--card-border);
            border-left: 3px solid var(--primary-green);
            border-radius: 8px;
            padding: 10px 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .contact-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted-text);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .contact-value {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--dark-text);
            word-break: break-word;
        }
        
        .contact-value i {
            width: 18px;
            color: var(--primary-green);
        }
        
        /* ===== Stats ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 16px 18px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .stat-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .stat-icon.green { background: #1e7e34; }
        .stat-icon.blue { background: #4e73df; }
        .stat-icon.teal { background: #36b9cc; }
        .stat-icon.amber { background: #f6a723; }
        
        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted-text);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        
        .stat-number {
            font-size: 19px;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }
        
        /* ===== Section Card ===== */
        .section-card {
            background: white;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 13px 18px;
            border-bottom: 1px solid var(--card-border);
            background: #fbfcfe;
        }
        
        .section-header h6 {
            font-weight: 700;
            color: var(--primary-green);
            margin: 0;
            font-size: 14px;
        }
        
        .section-header h6 i {
            margin-right: 7px;
        }
        
        .section-header .count-badge {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted-text);
            background: #f1f5f9;
            border: 1px solid var(--card-border);
            padding: 3px 10px;
            border-radius: 20px;
        }
        
        .table-card table {
            margin-bottom: 0;
        }
        
        .table-card th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 10px 12px;
            border-top: none;
            white-space: nowrap;
        }
        
        .table-card td {
            padding: 9px 12px;
            vertical-align: middle;
            font-size: 13px;
            border-color: #eef1f6;
        }
        
        .size-badge {
            background: #e3f2fd;
            color: #0066cc;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10.5px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        
        .product-cell {
            min-width: 200px;
        }
        
        .status-badge-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        
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
            .profile-banner {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @media (max-width: 991px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 575px) {
            .stats-row { grid-template-columns: 1fr; }
            .profile-identity { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34; font-weight: 500;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none; margin-left: 15px;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mt-3 no-print">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-user-circle"></i> Customer Detail
                        </h1>
                        <div>
                            <button onclick="window.open('print_customer_detail.php?id=<?php echo $customer_id; ?>', '_blank', 'width=1000,height=750')" class="btn btn-primary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="receiving_amount.php?id=<?php echo $customer_id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-money-bill-wave"></i> Receive Payment
                            </a>
                            <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-book"></i> View Ledger
                            </a>
                            <a href="view_customer.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <!-- Profile Banner -->
                    <div class="profile-banner">
                        <div class="profile-identity">
                            <div class="profile-avatar">
                                <?php echo $customer_initial; ?>
                            </div>
                            <div style="min-width: 0;">
                                <div class="profile-name">
                                    <?php echo htmlspecialchars($customer['customer_name'] ?? 'N/A'); ?>
                                </div>
                                <div class="profile-meta">
                                    <span class="chip-white"><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($customer['customer_code'] ?? 'N/A'); ?></span>
                                    <?php if(!empty($customer['company_name'])): ?>
                                        <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($customer['company_name']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($customer['mobile'] ?? 'N/A'); ?></span>
                                    <span>
                                        <?php if(isset($customer['status']) && $customer['status'] == 1): ?>
                                            <span class="badge badge-success status-badge-pill"><i class="fas fa-check-circle"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary status-badge-pill"><i class="fas fa-ban"></i> Inactive</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="balance-panel">
                            <div class="balance-label"><i class="fas fa-chart-line"></i> Current Balance</div>
                            <div class="balance-amount">Rs <?php echo number_format(abs($current_balance), 2); ?></div>
                            <div>
                                <span class="badge badge-light"><?php echo $balance_status; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Strip -->
                    <div class="contact-strip">
                        <div class="contact-item">
                            <div class="contact-label">Mobile Number</div>
                            <div class="contact-value"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['mobile'] ?? 'N/A'); ?></div>
                        </div>
                        <?php if(!empty($customer['email'])): ?>
                        <div class="contact-item">
                            <div class="contact-label">Email Address</div>
                            <div class="contact-value"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($customer['cnic'])): ?>
                        <div class="contact-item">
                            <div class="contact-label">CNIC</div>
                            <div class="contact-value"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($customer['cnic']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($customer['address'])): ?>
                        <div class="contact-item">
                            <div class="contact-label">Address</div>
                            <div class="contact-value"><i class="fas fa-map-marker-alt"></i> <?php echo nl2br(htmlspecialchars($customer['address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($customer['created_at'])): ?>
                        <div class="contact-item">
                            <div class="contact-label">Customer Since</div>
                            <div class="contact-value"><i class="fas fa-calendar-alt"></i> <?php echo date('d-m-Y', strtotime($customer['created_at'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="contact-item">
                            <div class="contact-label">Opening Balance</div>
                            <div class="contact-value"><i class="fas fa-chart-line"></i> Rs <?php echo number_format($opening_balance, 2); ?></div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
                            <div>
                                <div class="stat-label">Total Sales</div>
                                <div class="stat-number"><?php echo $sales_data['total_sales']; ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
                            <div>
                                <div class="stat-label">Total Sales Amount</div>
                                <div class="stat-number">Rs <?php echo number_format($sales_data['total_amount'], 0); ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon teal"><i class="fas fa-hand-holding-usd"></i></div>
                            <div>
                                <div class="stat-label">Total Payments</div>
                                <div class="stat-number">Rs <?php echo number_format($payment_data['total_payments'], 0); ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon amber"><i class="fas fa-balance-scale"></i></div>
                            <div>
                                <div class="stat-label">Current Balance</div>
                                <div class="stat-number" style="color: <?php echo $current_balance >= 0 ? '#dc3545' : '#28a745'; ?>;">
                                    Rs <?php echo number_format(abs($current_balance), 0); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purchase History -->
                    <div class="section-card table-card">
                        <div class="section-header">
                            <h6><i class="fas fa-shopping-cart"></i> Customer Purchase History</h6>
                            <span class="count-badge">Last 20 invoices</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="purchaseHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Size (H x W)</th>
                                        <th class="text-right">Area (sq ft)</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Rate</th>
                                        <th class="text-right">Amount</th>
                                        <th>Disc%</th>
                                        <th class="text-right">Net Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($purchase_result && mysqli_num_rows($purchase_result) > 0): ?>
                                        <?php while($purchase = mysqli_fetch_assoc($purchase_result)): ?>
                                            <tr>
                                                <td>
                                                    <a href="../sales/print_invoice.php?invoice_no=<?php echo urlencode($purchase['invoice_no']); ?>" target="_blank" class="font-weight-bold" style="color: #1e7e34;">
                                                        <?php echo htmlspecialchars($purchase['invoice_no']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo date('d-m-Y', strtotime($purchase['sale_date'])); ?></td>
                                                <td class="product-cell">
                                                    <strong><?php echo htmlspecialchars($purchase['product_name'] ?? 'N/A'); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($purchase['product_code'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if(!empty($purchase['client_size'])) {
                                                        echo '<span class="size-badge">' . htmlspecialchars($purchase['client_size']) . '</span>';
                                                    } elseif($purchase['client_height'] > 0 && $purchase['client_width'] > 0) {
                                                        echo '<span class="size-badge">' . htmlspecialchars($purchase['client_height']) . ' x ' . htmlspecialchars($purchase['client_width']) . '</span>';
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-right"><?php echo $purchase['area'] > 0 ? number_format($purchase['area'], 2) : '-'; ?></td>
                                                <td class="text-right"><?php echo number_format($purchase['quantity'], 2); ?></td>
                                                <td class="text-right"><?php echo formatCurrency($purchase['rate']); ?></td>
                                                <td class="text-right"><?php echo formatCurrency($purchase['amount']); ?></td>
                                                <td class="text-center"><?php echo $purchase['discount_percentage'] > 0 ? $purchase['discount_percentage'] . '%' : '-'; ?></td>
                                                <td class="text-right"><strong><?php echo formatCurrency($purchase['amount']); ?></strong></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                                No purchase history found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if($purchase_result && mysqli_num_rows($purchase_result) > 0): ?>
                            <div class="card-footer text-center bg-light">
                                <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-link" style="color: #1e7e34;">
                                    View Complete Ledger <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="section-card table-card">
                        <div class="section-header">
                            <h6><i class="fas fa-history"></i> Recent Transactions</h6>
                            <span class="count-badge">Last 10 entries</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="transactionsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th class="text-right">Debit (DR)</th>
                                        <th class="text-right">Credit (CR)</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($trans_result && mysqli_num_rows($trans_result) > 0): ?>
                                        <?php while($trans = mysqli_fetch_assoc($trans_result)): ?>
                                            <tr>
                                                <td><?php echo date('d-m-Y', strtotime($trans['date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if($trans['reference_type'] == 'SALE') {
                                                        echo '<span class="badge badge-info">SAL-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT) . '</span>';
                                                    } elseif($trans['reference_type'] == 'PAYMENT') {
                                                        echo '<span class="badge badge-success">RCP-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT) . '</span>';
                                                    } elseif($trans['reference_type'] == 'OPENING') {
                                                        echo '<span class="badge badge-secondary">Opening</span>';
                                                    } else {
                                                        echo '<span class="badge badge-secondary">' . ucfirst($trans['reference_type']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($trans['description'], 0, 80)); ?></td>
                                                <td class="text-right" style="color: #28a745; font-weight: 600;">
                                                    <?php echo $trans['debit'] > 0 ? 'Rs ' . number_format($trans['debit'], 2) : '-'; ?>
                                                </td>
                                                <td class="text-right" style="color: #dc3545; font-weight: 600;">
                                                    <?php echo $trans['credit'] > 0 ? 'Rs ' . number_format($trans['credit'], 2) : '-'; ?>
                                                </td>
                                                <td class="text-right"><strong>Rs <?php echo number_format($trans['balance'], 2); ?></strong></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                                No transactions found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if($trans_result && mysqli_num_rows($trans_result) > 0): ?>
                            <div class="card-footer text-center bg-light">
                                <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-link" style="color: #1e7e34;">
                                    View Complete Ledger <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
    
    <a class="scroll-to-top rounded no-print" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#purchaseHistoryTable').DataTable({
                "order": [[1, "desc"]],
                "pageLength": 10,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "zeroRecords": "No purchase history found"
                }
            });
            
            $('#transactionsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "zeroRecords": "No transactions found"
                }
            });
        });
    </script>
</body>
</html>
