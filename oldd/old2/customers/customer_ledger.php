<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

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
$customer = mysqli_fetch_assoc($result);

if(!$customer) {
    header("Location: view_customer.php");
    exit();
}

// Get opening balance (balance before from_date)
$opening_query = "SELECT balance FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND DATE(date) < '$from_date' 
                  ORDER BY date DESC, id DESC LIMIT 1";
$opening_result = mysqli_query($conn, $opening_query);

if(mysqli_num_rows($opening_result) > 0) {
    $opening_balance = floatval(mysqli_fetch_assoc($opening_result)['balance']);
} else {
    // If no previous transactions, use opening balance from customer table
    $opening_balance = floatval($customer['opening_balance']);
    if($customer['balance_type'] == 'payable') {
        $opening_balance = -$opening_balance;
    }
}
// Build ledger query with sale details - FIXED
$ledger_query = "SELECT cl.*, 
                 s.invoice_no, s.sale_date,
                 sd.product_id, sd.client_size, sd.client_height, sd.client_width,
                 sd.area, sd.quantity, sd.rate, sd.amount,
                 p.product_name, p.product_code
                 FROM customer_ledger cl
                 LEFT JOIN sale_master s ON cl.reference_type = 'SALE' AND cl.reference_id = s.id
                 LEFT JOIN sale_details sd ON s.id = sd.sale_id
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE cl.customer_id = $customer_id 
                 AND cl.date BETWEEN '$from_date' AND '$to_date'
                 ORDER BY cl.date ASC, cl.id ASC";
$ledger_result = mysqli_query($conn, $ledger_query);

// Get correct opening balance - FIXED
// Get balance before from_date
$opening_query = "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance 
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND date < '$from_date'";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if($opening_result && mysqli_num_rows($opening_result) > 0) {
    $opening_data = mysqli_fetch_assoc($opening_result);
    $opening_balance = floatval($opening_data['balance']);
} else {
    // If no transactions before from_date, get opening from OPENING entry
    $opening_entry_query = "SELECT debit, credit FROM customer_ledger 
                            WHERE customer_id = $customer_id AND reference_type = 'OPENING'
                            ORDER BY id ASC LIMIT 1";
    $opening_entry_result = mysqli_query($conn, $opening_entry_query);
    if($opening_entry_result && mysqli_num_rows($opening_entry_result) > 0) {
        $opening_entry = mysqli_fetch_assoc($opening_entry_result);
        $opening_balance = floatval($opening_entry['debit']) - floatval($opening_entry['credit']);
    }
}

// Get summary totals
$summary_query = "SELECT 
                    COALESCE(SUM(debit), 0) as total_debit,
                    COALESCE(SUM(credit), 0) as total_credit
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_debit = floatval($summary['total_debit']);
$total_credit = floatval($summary['total_credit']);
$closing_balance = $opening_balance + $total_debit - $total_credit;

$current_balance = floatval($customer['current_balance']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ledger - <?php echo htmlspecialchars($customer['customer_name']); ?> | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-green: #1e7e34;
            --primary-blue: #4e73df;
        }
        
        body {
            background-color: #f8f9fc;
            color: #2c3e50 !important;
        }
        
        .ledger-header {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .balance-summary {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .balance-box {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.2s;
        }
        
        .balance-box:hover {
            transform: translateY(-3px);
        }
        
        .balance-box h4 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-ledger {
            margin-bottom: 0;
        }
        
        .table-ledger th {
            background: linear-gradient(135deg, #e8f5e9 0%, #e3f2fd 100%);
            color: #1e7e34 !important;
            font-weight: 700;
            border-bottom: 2px solid #1e7e34;
            padding: 12px;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table-ledger td {
            padding: 12px;
            vertical-align: middle;
            font-size: 13px;
        }
        
        .table-ledger tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .debit-amount {
            color: #28a745;
            font-weight: 700;
        }
        
        .credit-amount {
            color: #dc3545;
            font-weight: 700;
        }
        
        .balance-positive {
            color: #28a745;
            font-weight: 700;
        }
        
        .balance-negative {
            color: #dc3545;
            font-weight: 700;
        }
        
        .opening-row {
            background-color: #f8f9fc;
            font-weight: 600;
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
        
        @media print {
            .no-print {
                display: none !important;
            }
            .ledger-header {
                background: #1e7e34;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .balance-box {
                border: 1px solid #ddd;
            }
            .table-ledger th {
                background: #e8f5e9;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        .customer-code-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .btn-export:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            color: white;
        }
        
        /* Product details styling */
        .product-details {
            font-size: 12px;
            color: #555;
            margin-top: 8px;
            padding: 10px 12px;
            background: #f8f9fc;
            border-radius: 8px;
            border-left: 3px solid #1e7e34;
        }
        
        .product-details strong {
            color: #1e7e34;
        }
        
        .product-item {
            margin-bottom: 8px;
            padding: 6px 0;
            border-bottom: 1px dashed #e9ecef;
            font-size: 12px;
        }
        
        .product-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .product-total {
            font-size: 12px;
            font-weight: 600;
            color: #1e7e34;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #e9ecef;
        }
        
        .payment-info {
            font-size: 11px;
            color: #0066cc;
            margin-top: 5px;
        }
        
        .size-badge {
            background: #e3f2fd;
            color: #0066cc;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
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
                    <div class="welcome-text" style="color: #1e7e34;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3 no-print">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-book"></i> Customer Ledger
                        </h1>
                        <div>
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button id="exportExcelBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <a href="credit_rec.php?id=<?php echo $customer_id; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-money-bill-wave"></i> Receive Payment
                            </a>
                            <a href="customer_detail.php?id=<?php echo $customer_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-user"></i> Customer Detail
                            </a>
                            <a href="view_customer.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <!-- Ledger Header -->
                    <div class="ledger-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-1"><?php echo htmlspecialchars($customer['customer_name']); ?></h2>
                                <p class="mb-2">
                                    <span class="customer-code-badge">
                                        <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($customer['customer_code']); ?>
                                    </span>
                                    <span class="customer-code-badge ml-2">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['mobile']); ?>
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo !empty($customer['address']) ? htmlspecialchars($customer['address']) : 'No address on file'; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <h6>Current Balance</h6>
                                <h2 style="font-size: 32px;">
                                    ₨ <?php echo number_format(abs($current_balance), 2); ?>
                                    <small><?php echo ($current_balance >= 0) ? 'DR (Receivable)' : 'CR (Payable)'; ?></small>
                                </h2>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Balance Summary Cards -->
                    <div class="balance-summary no-print">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="balance-box" style="background: #e8f5e9;">
                                    <small class="text-muted">Opening Balance</small>
                                    <h4 style="color: #1e7e34;">
                                        ₨ <?php echo number_format(abs($opening_balance), 2); ?>
                                        <small><?php echo $opening_balance >= 0 ? 'DR' : 'CR'; ?></small>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="balance-box" style="background: #e3f2fd;">
                                    <small class="text-muted">Total Debit (Sales)</small>
                                    <h4 style="color: #4e73df;" id="totalDebitDisplay">
                                        ₨ <?php echo number_format($total_debit, 2); ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="balance-box" style="background: #ffebee;">
                                    <small class="text-muted">Total Credit (Payments)</small>
                                    <h4 style="color: #dc3545;" id="totalCreditDisplay">
                                        ₨ <?php echo number_format($total_credit, 2); ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="balance-box" style="background: #fff3e0;">
                                    <small class="text-muted">Closing Balance</small>
                                    <h4 style="color: <?php echo $closing_balance >= 0 ? '#1e7e34' : '#dc3545'; ?>">
                                        ₨ <?php echo number_format(abs($closing_balance), 2); ?>
                                        <small><?php echo $closing_balance >= 0 ? 'DR' : 'CR'; ?></small>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date Filter -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between">
                            <div class="form-group mr-2">
                                <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                                <label class="mr-2 font-weight-bold">From Date:</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                            <div class="form-group mr-2">
                                <label class="mr-2 font-weight-bold">To Date:</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="customer_ledger.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Ledger Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                <i class="fas fa-list"></i> Ledger Statement 
                                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-ledger" id="ledgerTable" width="100%">
                                    <thead>
                                        <tr>
                                            <th width="12%">Date</th>
                                            <th width="12%">Reference #</th>
                                            <th width="40%">Description / Product Details</th>
                                            <th width="12%" class="text-right">Debit (DR)</th>
                                            <th width="12%" class="text-right">Credit (CR)</th>
                                            <th width="12%" class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $running_balance = $opening_balance;
                                        ?>
                                        <!-- Opening Balance Row -->
                                        <tr class="opening-row">
                                            <td><?php echo date('d-m-Y', strtotime($from_date)); ?></td>
                                            <td><span class="badge badge-secondary">Opening</span></td>
                                            <td><strong>Opening Balance</strong></td>
                                            <td class="text-right">
                                                <?php if($opening_balance > 0): ?>
                                                    ₨ <?php echo number_format($opening_balance, 2); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                             </td>
                                            <td class="text-right">
                                                <?php if($opening_balance < 0): ?>
                                                    ₨ <?php echo number_format(abs($opening_balance), 2); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                             </td>
                                            <td class="text-right <?php echo $running_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                                <strong>₨ <?php echo number_format(abs($running_balance), 2); ?> <?php echo $running_balance >= 0 ? 'DR' : 'CR'; ?></strong>
                                             </td>
                                        </tr>
                                        
                                        <?php
                                        $has_transactions = false;
                                        if(mysqli_num_rows($ledger_result) > 0):
                                            $has_transactions = true;
                                            while($row = mysqli_fetch_assoc($ledger_result)):
                                                $debit = floatval($row['debit']);
                                                $credit = floatval($row['credit']);
                                                $running_balance = floatval($row['balance']);
                                                
                                                $ref_display = '';
                                                $ref_link = '';
                                                
                                                if($row['reference_type'] == 'SALE') {
                                                    $ref_display = '<span class="badge badge-info">SAL-' . str_pad($row['reference_id'], 5, '0', STR_PAD_LEFT) . '</span>';
                                                    $ref_link = "../sales/print_invoice.php?invoice_no=" . $row['invoice_no'];
                                                } elseif($row['reference_type'] == 'PAYMENT') {
                                                    $ref_display = '<span class="badge badge-success">RCP-' . str_pad($row['reference_id'], 5, '0', STR_PAD_LEFT) . '</span>';
                                                } elseif($row['reference_type'] == 'OPENING') {
                                                    $ref_display = '<span class="badge badge-secondary">Opening</span>';
                                                } else {
                                                    $ref_display = '<span class="badge badge-secondary">' . ucfirst($row['reference_type']) . '</span>';
                                                }
                                        ?>
                                            <tr>
                                                <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                                <td>
                                                    <?php if($row['reference_type'] == 'SALE' && isset($row['invoice_no'])): ?>
                                                        <a href="<?php echo $ref_link; ?>" target="_blank" class="font-weight-bold">
                                                            <?php echo $ref_display; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php echo $ref_display; ?>
                                                    <?php endif; ?>
                                                 </td>
                                                <td>
                                                    <?php if($row['reference_type'] == 'SALE'): ?>
                                                        <div>
                                                            <strong>Sale Invoice: <?php echo isset($row['invoice_no']) ? $row['invoice_no'] : 'N/A'; ?></strong>
                                                            <div class="product-details mt-2">
                                                                <?php if(isset($row['product_name']) && $row['product_name']): ?>
                                                                    <div class="product-item">
                                                                        <i class="fas fa-cube text-success mr-1" style="font-size: 10px;"></i>
                                                                        <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                                                        <?php if($row['client_size']): ?>
                                                                            <br><span class="size-badge"><i class="fas fa-arrows-alt"></i> Size: <?php echo htmlspecialchars($row['client_size']); ?></span>
                                                                        <?php elseif($row['client_height'] > 0 && $row['client_width'] > 0): ?>
                                                                            <br><span class="size-badge"><i class="fas fa-arrows-alt"></i> Size: <?php echo $row['client_height']; ?> x <?php echo $row['client_width']; ?></span>
                                                                        <?php endif; ?>
                                                                        <?php if($row['area'] > 0): ?>
                                                                            <span class="size-badge ml-1"><i class="fas fa-chart-area"></i> Area: <?php echo number_format($row['area'], 2); ?> sq ft</span>
                                                                        <?php endif; ?>
                                                                        <br><small><i class="fas fa-boxes"></i> Qty: <?php echo number_format($row['quantity'], 2); ?> | Rate: <?php echo formatCurrency($row['rate']); ?> | Amount: <?php echo formatCurrency($row['amount']); ?></small>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <?php 
                                                                    $description = $row['description'];
                                                                    $lines = explode("\n", $description);
                                                                    foreach($lines as $line):
                                                                        if(preg_match('/^\d+\./', trim($line))):
                                                                    ?>
                                                                            <div class="product-item">
                                                                                <i class="fas fa-cube text-success mr-1" style="font-size: 10px;"></i>
                                                                                <?php echo nl2br(htmlspecialchars($line)); ?>
                                                                            </div>
                                                                    <?php 
                                                                        elseif(strpos($line, 'Total Amount:') !== false):
                                                                    ?>
                                                                            <div class="product-total">
                                                                                <small><?php echo htmlspecialchars($line); ?></small>
                                                                            </div>
                                                                    <?php
                                                                        elseif(strpos($line, 'Payment Type:') !== false):
                                                                    ?>
                                                                            <div class="payment-info">
                                                                                <small><?php echo htmlspecialchars($line); ?></small>
                                                                            </div>
                                                                    <?php
                                                                        endif;
                                                                    endforeach;
                                                                    ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                                                    <?php endif; ?>
                                                 </td>
                                                <td class="text-right debit-amount">
                                                    <?php echo $debit > 0 ? '₨ ' . number_format($debit, 2) : '-'; ?>
                                                 </td>
                                                <td class="text-right credit-amount">
                                                    <?php echo $credit > 0 ? '₨ ' . number_format($credit, 2) : '-'; ?>
                                                 </td>
                                                <td class="text-right <?php echo $running_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                                                    <strong>₨ <?php echo number_format(abs($running_balance), 2); ?> <?php echo $running_balance >= 0 ? 'DR' : 'CR'; ?></strong>
                                                 </td>
                                             </tr>
                                        <?php 
                                            endwhile;
                                        endif;
                                        ?>
                                        
                                        <?php if(!$has_transactions): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No transactions found in selected date range</h5>
                                                    <p class="text-muted">Try changing the date range or add some sales/payments</p>
                                                 </td>
                                             </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if($has_transactions): ?>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); font-weight: bold;">
                                            <td colspan="3" class="text-right">Totals for Period:</td>
                                            <td class="text-right debit-amount">₨ <?php echo number_format($total_debit, 2); ?></td>
                                            <td class="text-right credit-amount">₨ <?php echo number_format($total_credit, 2); ?></td>
                                            <td class="text-right">
                                                <strong>₨ <?php echo number_format(abs($closing_balance), 2); ?> <?php echo $closing_balance >= 0 ? 'DR' : 'CR'; ?></strong>
                                            </td>
                                         </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
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
            // Initialize DataTable
            $('#ledgerTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 25,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "zeroRecords": "No entries found"
                }
            });
            
            // Export to Excel functionality
            $('#exportExcelBtn').click(function(e) {
                e.preventDefault();
                
                // Get the ledger table data
                var data = [];
                
                // Add header
                data.push(['Customer Ledger Report']);
                data.push(['Customer Name: <?php echo addslashes($customer['customer_name']); ?>']);
                data.push(['Customer Code: <?php echo $customer['customer_code']; ?>']);
                data.push(['Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>']);
                data.push(['']);
                data.push(['Date', 'Reference #', 'Description', 'Debit (DR)', 'Credit (CR)', 'Balance']);
                
                // Get table data
                $('#ledgerTable tbody tr').each(function() {
                    var row = [];
                    $(this).find('td').each(function() {
                        var text = $(this).text().trim();
                        // Clean up the text
                        text = text.replace('₨', '').replace('DR', '').replace('CR', '').trim();
                        row.push(text);
                    });
                    if(row.length > 0 && row[0] !== 'No transactions found') {
                        data.push(row);
                    }
                });
                
                // Add summary
                data.push(['']);
                data.push(['SUMMARY']);
                data.push(['Total Debit (Sales):', '₨ <?php echo number_format($total_debit, 2); ?>']);
                data.push(['Total Credit (Payments):', '₨ <?php echo number_format($total_credit, 2); ?>']);
                data.push(['Closing Balance:', '₨ <?php echo number_format(abs($closing_balance), 2); ?> <?php echo $closing_balance >= 0 ? 'DR' : 'CR'; ?>']);
                data.push(['']);
                data.push(['Generated on:', '<?php echo date('d-m-Y H:i:s'); ?>']);
                
                // Create CSV
                var csv = data.map(row => row.join(',')).join('\n');
                var blob = new Blob([csv], {type: 'text/csv'});
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                link.href = url;
                link.download = 'customer_ledger_<?php echo $customer['customer_code']; ?>_<?php echo date('Y-m-d'); ?>.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                Swal.fire({
                    title: 'Success!',
                    text: 'Export completed successfully!',
                    icon: 'success',
                    confirmButtonColor: '#1e7e34',
                    timer: 2000
                });
            });
        });
    </script>
</body>
</html>