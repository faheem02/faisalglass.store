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
$balance_color = ($current_balance >= 0) ? '#28a745' : '#dc3545';
$balance_bg = ($current_balance >= 0) ? '#e8f5e9' : '#ffebee';
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
            --success-green: #28a745;
            --info-blue: #36b9cc;
            --dark-text: #2c3e50;
        }
        
        body {
            background-color: #f8f9fc;
            color: #2c3e50 !important;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            height: 100%;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            margin-top: 8px;
            word-break: break-word;
        }
        
        .balance-card {
            background: <?php echo $balance_bg; ?>;
            border-left: 4px solid <?php echo $balance_color; ?>;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .balance-amount {
            font-size: 36px;
            font-weight: bold;
            color: <?php echo $balance_color; ?>;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #1e7e34;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .section-header {
            background: linear-gradient(135deg, #e8f5e9 0%, #e3f2fd 100%);
            padding: 15px 20px;
            border-bottom: 2px solid #1e7e34;
        }
        
        .section-header h6 {
            color: #1e7e34;
            font-weight: 700;
            margin: 0;
        }
        
        .transaction-table {
            margin-bottom: 0;
        }
        
        .transaction-table th {
            background: #f8f9fc;
            color: #1e7e34;
            font-weight: 600;
            font-size: 12px;
            padding: 12px;
        }
        
        .transaction-table td {
            padding: 12px;
            vertical-align: middle;
            font-size: 13px;
        }
        
        .debit-text {
            color: #28a745;
            font-weight: 600;
        }
        
        .credit-text {
            color: #dc3545;
            font-weight: 600;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .customer-code-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .size-badge {
            background: #e3f2fd;
            color: #0066cc;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
        }
        
        .product-cell {
            min-width: 200px;
        }
        
        .size-cell {
            min-width: 100px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .profile-header {
                background: #1e7e34;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
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
                        <span class="user-name" style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </span>
                        <a href="../logout.php" class="logout-btn" style="color: #dc3545; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3 no-print">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-user-circle"></i> Customer Detail
                        </h1>
                        <div>
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="credit_rec.php?id=<?php echo $customer_id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-money-bill-wave"></i> Receive Payment
                            </a>
                            <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-book"></i> View Ledger
                            </a>
                            <a href="view_customer.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="profile-avatar mx-auto">
                                    <i class="fas fa-user-circle fa-3x"></i>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <h2 class="mb-1"><?php echo htmlspecialchars($customer['customer_name'] ?? 'N/A'); ?></h2>
                                <p class="mb-2">
                                    <span class="customer-code-badge">
                                        <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($customer['customer_code'] ?? 'N/A'); ?>
                                    </span>
                                    <?php if(!empty($customer['company_name'])): ?>
                                        <span class="customer-code-badge ml-2">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($customer['company_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['mobile'] ?? 'N/A'); ?>
                                    <?php if(!empty($customer['email'])): ?>
                                        &nbsp;|&nbsp; <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-3 text-right">
                                <div class="balance-card mb-0" style="background: rgba(255,255,255,0.15); border-left: none; text-align: center;">
                                    <div class="info-label" style="color: rgba(255,255,255,0.8);">Current Balance</div>
                                    <div class="balance-amount" style="color: white; font-size: 28px;">
                                        ₨ <?php echo number_format(abs($current_balance), 2); ?>
                                    </div>
                                    <div>
                                        <span class="badge badge-light">
                                            <?php echo $balance_status; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Left Column - Contact Information -->
                        <div class="col-md-4">
                            <!-- Contact Info Card -->
                            <div class="info-card">
                                <h6 class="mb-3" style="color: #1e7e34;">
                                    <i class="fas fa-address-card"></i> Contact Information
                                </h6>
                                <hr class="mt-0">
                                <div class="mb-3">
                                    <div class="info-label">Mobile Number</div>
                                    <div class="info-value">
                                        <i class="fas fa-phone text-success"></i> <?php echo htmlspecialchars($customer['mobile'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <?php if(!empty($customer['email'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value">
                                        <i class="fas fa-envelope text-info"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($customer['cnic'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">CNIC</div>
                                    <div class="info-value">
                                        <i class="fas fa-id-card text-warning"></i> <?php echo htmlspecialchars($customer['cnic']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Address Card -->
                            <?php if(!empty($customer['address'])): ?>
                            <div class="info-card">
                                <h6 class="mb-3" style="color: #1e7e34;">
                                    <i class="fas fa-map-marker-alt"></i> Address
                                </h6>
                                <hr class="mt-0">
                                <div class="info-value">
                                    <i class="fas fa-location-dot text-danger"></i> <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Status Card -->
                            <div class="info-card">
                                <h6 class="mb-3" style="color: #1e7e34;">
                                    <i class="fas fa-info-circle"></i> Account Status
                                </h6>
                                <hr class="mt-0">
                                <div class="mb-3">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <?php if(isset($customer['status']) && $customer['status'] == 1): ?>
                                            <span class="badge badge-success badge-status">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary badge-status">
                                                <i class="fas fa-ban"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if(isset($customer['created_at'])): ?>
                                <div class="mb-3">
                                    <div class="info-label">Customer Since</div>
                                    <div class="info-value">
                                        <i class="fas fa-calendar-alt text-info"></i> 
                                        <?php echo date('d-m-Y', strtotime($customer['created_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="info-label">Opening Balance</div>
                                    <div class="info-value">
                                        <i class="fas fa-chart-line text-success"></i> 
                                        ₨ <?php echo number_format(isset($customer['opening_balance']) ? floatval($customer['opening_balance']) : 0, 2); ?>
                                        <small class="text-muted">
                                            (<?php echo (isset($customer['balance_type']) && $customer['balance_type'] == 'receivable') ? 'Receivable' : 'Payable'; ?>)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-8">
                            <!-- Statistics Cards -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-icon" style="color: #4e73df;">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div class="stat-number"><?php echo $sales_data['total_sales']; ?></div>
                                        <div class="stat-label">Total Sales</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-icon" style="color: #1e7e34;">
                                            <i class="fas fa-rupee-sign"></i>
                                        </div>
                                        <div class="stat-number">₨ <?php echo number_format($sales_data['total_amount'], 0); ?></div>
                                        <div class="stat-label">Total Sales Amount</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Purchase History Section -->
                            <div class="section-card">
                                <div class="section-header">
                                    <h6 class="m-0">
                                        <i class="fas fa-shopping-cart"></i> Customer Purchase History
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered transaction-table mb-0" id="purchaseHistoryTable">
                                            <thead>
                                                <tr>
                                                    <th>Invoice No</th>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>Size (H x W)</th>
                                                    <th>Area (sq ft)</th>
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
                                                                <a href="../sales/print_invoice.php?invoice_no=<?php echo $purchase['invoice_no']; ?>" target="_blank" class="font-weight-bold">
                                                                    <?php echo $purchase['invoice_no']; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo date('d-m-Y', strtotime($purchase['sale_date'])); ?></td>
                                                            <td class="product-cell">
                                                                <strong><?php echo htmlspecialchars($purchase['product_name'] ?? 'N/A'); ?></strong>
                                                                <br><small class="text-muted"><?php echo $purchase['product_code'] ?? ''; ?></small>
                                                            </td>
                                                            <td class="size-cell">
                                                                <?php 
                                                                if(!empty($purchase['client_size'])) {
                                                                    echo '<span class="size-badge">' . htmlspecialchars($purchase['client_size']) . '</span>';
                                                                } elseif($purchase['client_height'] > 0 && $purchase['client_width'] > 0) {
                                                                    echo '<span class="size-badge">' . $purchase['client_height'] . ' x ' . $purchase['client_width'] . '</span>';
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
                                </div>
                                <?php if($purchase_result && mysqli_num_rows($purchase_result) > 0): ?>
                                    <div class="card-footer text-center bg-light">
                                        <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-link">
                                            View Complete Ledger <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Recent Transactions -->
                            <div class="section-card">
                                <div class="section-header">
                                    <h6 class="m-0">
                                        <i class="fas fa-history"></i> Recent Transactions (Last 10)
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover transaction-table mb-0" id="transactionsTable">
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
                                                            <td class="text-right debit-text">
                                                                <?php echo $trans['debit'] > 0 ? '₨ ' . number_format($trans['debit'], 2) : '-'; ?>
                                                            </td>
                                                            <td class="text-right credit-text">
                                                                <?php echo $trans['credit'] > 0 ? '₨ ' . number_format($trans['credit'], 2) : '-'; ?>
                                                            </td>
                                                            <td class="text-right">
                                                                <strong>₨ <?php echo number_format($trans['balance'], 2); ?></strong>
                                                            </td>
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
                                </div>
                                <?php if($trans_result && mysqli_num_rows($trans_result) > 0): ?>
                                    <div class="card-footer text-center bg-light">
                                        <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-link">
                                            View Complete Ledger <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
            // Initialize DataTable for purchase history
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
            
            // Initialize DataTable for transactions
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