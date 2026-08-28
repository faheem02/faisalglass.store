<?php
/**
 * Supplier Payment Page
 * Faysal Glass And Aluminium Centre
 * 
 * Make payment to supplier and update ledger entries
 * Page: Paid Amount
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Supplier Payment";
$success_msg = '';
$error_msg = '';

// Payment history filters
$hist_from   = isset($_GET['from_date'])        ? mysqli_real_escape_string($conn, $_GET['from_date'])        : date('Y-m-01');
$hist_to     = isset($_GET['to_date'])          ? mysqli_real_escape_string($conn, $_GET['to_date'])          : date('Y-m-d');
$hist_sup_id = isset($_GET['hist_supplier_id']) ? intval($_GET['hist_supplier_id']) : 0;

// Check if supplier ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: supplier_view.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: supplier_view.php");
    exit();
}

$supplier = mysqli_fetch_assoc($result);

// Calculate current balance from ledger
$balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                  FROM supplier_ledger WHERE supplier_id = $supplier_id";
$balance_result = mysqli_query($conn, $balance_query);

$total_debit = 0;
$total_credit = 0;

if($balance_result && mysqli_num_rows($balance_result) > 0) {
    $balance_data = mysqli_fetch_assoc($balance_result);
    $total_debit = floatval($balance_data['total_debit']);
    $total_credit = floatval($balance_data['total_credit']);
}
$current_balance = $total_credit - $total_debit;

// Fetch bank accounts
$bank_query = "SELECT * FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);

// Get current cash balance
$cash_query = "SELECT SUM(debit) - SUM(credit) as cash_balance FROM cash_book";
$cash_result = mysqli_query($conn, $cash_query);
$cash_balance = 0;
if($cash_result && mysqli_num_rows($cash_result) > 0) {
    $cash_data = mysqli_fetch_assoc($cash_result);
    $cash_balance = floatval($cash_data['cash_balance']);
}

// Handle Payment Submission
if(isset($_POST['save_payment'])) {
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no'] ?? ''));
    $purchase_invoice_no = mysqli_real_escape_string($conn, trim($_POST['purchase_invoice_no'] ?? ''));
    $amount = floatval($_POST['amount']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    
    // Validation
    if(empty($payment_date)) {
        $error_msg = "Payment date is required!";
    } elseif($amount <= 0) {
        $error_msg = "Amount must be greater than 0!";
    } elseif($payment_method == 'bank' && $bank_account_id <= 0) {
        $error_msg = "Please select a bank account!";
    } elseif($amount > $current_balance && $current_balance > 0) {
        $error_msg = "Payment amount cannot exceed outstanding balance! Outstanding: " . formatCurrency($current_balance);
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Insert into supplier_payments
            $insert_payment = "INSERT INTO supplier_payments (payment_date, supplier_id, payment_method, 
                              bank_account_id, reference_no, purchase_invoice_no, amount, remarks, created_by) 
                              VALUES ('$payment_date', '$supplier_id', '$payment_method', 
                              '$bank_account_id', '$reference_no', '$purchase_invoice_no', '$amount', '$remarks', '{$_SESSION['user_id']}')";
            
            if(!mysqli_query($conn, $insert_payment)) {
                throw new Exception("Failed to save payment record");
            }
            
            $payment_id = mysqli_insert_id($conn);
            
            // 2. Calculate new balance
            $new_balance = $current_balance - $amount;
            
            // 3. Insert into supplier_ledger (Debit entry for payment)
            $description = "Payment made to supplier - " . ucfirst($payment_method);
            if($reference_no) {
                $description .= " (Ref: $reference_no)";
            }
            
            $ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                              description, debit, credit, balance) 
                              VALUES ('$payment_date', '$supplier_id', 'PAYMENT', '$payment_id', 
                              '$description', '$amount', 0, '$new_balance')";
            
            if(!mysqli_query($conn, $ledger_query)) {
                throw new Exception("Failed to update supplier ledger");
            }
            
            // 4. Update cash or bank book
            if($payment_method == 'cash') {
                // Get current cash balance
                $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
                $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                $current_cash_balance = 0;
                if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                    $cash_bal_data = mysqli_fetch_assoc($cash_bal_result);
                    $current_cash_balance = floatval($cash_bal_data['balance']);
                }
                
                $new_cash_balance = $current_cash_balance - $amount;
                
                $cash_book_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, 
                                    debit, credit, balance) 
                                    VALUES ('$payment_date', 'SUPPLIER_PAYMENT', '$payment_id', 
                                    'Payment to supplier: {$supplier['supplier_name']}', 0, '$amount', '$new_cash_balance')";
                
                if(!mysqli_query($conn, $cash_book_query)) {
                    throw new Exception("Failed to update cash book");
                }
            } 
            elseif($payment_method == 'bank' && $bank_account_id > 0) {
                // Get current bank balance
                $bank_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_account_id";
                $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                $current_bank_balance = 0;
                if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                    $bank_bal_data = mysqli_fetch_assoc($bank_bal_result);
                    $current_bank_balance = floatval($bank_bal_data['balance']);
                }
                
                $new_bank_balance = $current_bank_balance - $amount;
                
                $bank_book_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, 
                                    description, debit, credit, balance) 
                                    VALUES ('$payment_date', '$bank_account_id', 'SUPPLIER_PAYMENT', '$payment_id', 
                                    'Payment to supplier: {$supplier['supplier_name']}', 0, '$amount', '$new_bank_balance')";
                
                if(!mysqli_query($conn, $bank_book_query)) {
                    throw new Exception("Failed to update bank book");
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success_msg = "Payment of " . formatCurrency($amount) . " made successfully to " . htmlspecialchars($supplier['supplier_name']);
            
            // Refresh balance
            $current_balance = $new_balance;
            
            // Redirect to print receipt
            header("Location: print_payment_receipt.php?id=$payment_id");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    }
}
// Fetch suppliers for history dropdown
$all_sup_q = "SELECT id, supplier_name, supplier_code FROM suppliers WHERE status=1 ORDER BY supplier_name";
$all_sup_r = mysqli_query($conn, $all_sup_q);

// Payment history query
$hist_q = "SELECT p.*, s.supplier_name, s.supplier_code
            FROM supplier_payments p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE DATE(p.payment_date) BETWEEN '$hist_from' AND '$hist_to'";
if ($hist_sup_id > 0) $hist_q .= " AND p.supplier_id = $hist_sup_id";
$hist_q .= " ORDER BY p.payment_date DESC, p.id DESC";
$hist_r = mysqli_query($conn, $hist_q);

$hist_sum_q = "SELECT SUM(amount) as total,
               SUM(CASE WHEN payment_method='cash' THEN amount ELSE 0 END) as cash_total,
               SUM(CASE WHEN payment_method='bank' THEN amount ELSE 0 END) as bank_total,
               COUNT(*) as cnt
               FROM supplier_payments p
               WHERE DATE(p.payment_date) BETWEEN '$hist_from' AND '$hist_to'"
             . ($hist_sup_id > 0 ? " AND p.supplier_id=$hist_sup_id" : "");
$hist_sum_r   = mysqli_query($conn, $hist_sum_q);
$hist_summary = mysqli_fetch_assoc($hist_sum_r);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: white;
        }
        .btn-green:hover {
            background-color: #155724;
            border-color: #155724;
            color: white;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .form-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .supplier-info {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #1e7e34;
        }
        .balance-amount {
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
        }
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-money-bill-wave text-success mr-2"></i> Supplier Payment
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="supplier_view.php">Supplier Ledger</a></li>
                <li class="breadcrumb-item active">Paid Amount</li>
            </ol>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Supplier Information -->
        <div class="supplier-info">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-label"><i class="fas fa-truck"></i> Supplier Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label"><i class="fas fa-barcode"></i> Supplier Code</div>
                    <div class="info-value"><?php echo $supplier['supplier_code']; ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label"><i class="fas fa-phone"></i> Mobile</div>
                    <div class="info-value"><?php echo htmlspecialchars($supplier['mobile']); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="info-label"><i class="fas fa-chart-line"></i> Current Outstanding</div>
                    <div class="balance-amount">
                        <?php 
                        if($current_balance > 0) {
                            echo formatCurrency($current_balance) . ' (Payable)';
                        } elseif($current_balance < 0) {
                            echo formatCurrency(abs($current_balance)) . ' (Receivable)';
                        } else {
                            echo formatCurrency(0);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($current_balance <= 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            This supplier has no outstanding payable amount. No payment needed at this time.
        </div>
        <?php endif; ?>
        
        <!-- Payment Form -->
        <?php if($current_balance > 0): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Make Payment
            </div>
            <div class="card-body">
                <form method="POST" action="" id="paymentForm">
                    <input type="hidden" name="save_payment" value="1">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer / Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-rupee-sign text-success mr-1"></i> Amount (₨)</label>
                                <input type="number" step="0.01" name="amount" id="amount" class="form-control" 
                                       placeholder="Enter amount" max="<?php echo $current_balance; ?>" required>
                                <small class="text-muted">Maximum: <?php echo formatCurrency($current_balance); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="bank_row" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-university text-success mr-1"></i> Bank Account</label>
                                <select name="bank_account_id" class="form-control">
                                    <option value="">Select Bank Account</option>
                                    <?php while($bank = mysqli_fetch_assoc($bank_result)): ?>
                                        <option value="<?php echo $bank['id']; ?>">
                                            <?php echo htmlspecialchars($bank['bank_name'] . ' - ' . ($bank['account_number'] ?? $bank['account_title'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-receipt text-success mr-1"></i> Reference No / Cheque No</label>
                                <input type="text" name="reference_no" class="form-control" 
                                       placeholder="Enter reference or cheque number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-file-invoice text-success mr-1"></i> Purchase Invoice No (Against which payment)</label>
                                <input type="text" name="purchase_invoice_no" class="form-control" 
                                       placeholder="e.g. PUR-0001 (optional)">
                                <small class="text-muted">Enter the purchase invoice number this payment is against</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-comment text-success mr-1"></i> Remarks (Optional)</label>
                                <textarea name="remarks" class="form-control" rows="2" 
                                          placeholder="Enter any remarks about this payment"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="save_payment" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Process Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Payment Information Card -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-info-circle mr-2"></i> Payment Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                            <h6>Outstanding Balance</h6>
                            <p class="small text-muted">Current payable amount to supplier</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-money-bill-wave fa-2x text-primary mb-2"></i>
                            <h6>Payment Methods</h6>
                            <p class="small text-muted">Cash or Bank Transfer with reference</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-book fa-2x text-info mb-2"></i>
                            <h6>Auto Accounting</h6>
                            <p class="small text-muted">Automatically updates supplier ledger and cash/bank book</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment History Section -->
        <div class="mt-5">
            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0 text-gray-800"><i class="fas fa-history text-success mr-2"></i> Supplier Payment History</h4>
                <button class="btn btn-info btn-sm" onclick="window.open('print_payment_history.php?from_date=<?php echo urlencode($hist_from); ?>&to_date=<?php echo urlencode($hist_to); ?>&supplier_id=<?php echo $hist_sup_id; ?>','_blank','width=1000,height=750')">
                    <i class="fas fa-print mr-1"></i> Print History
                </button>
            </div>

            <!-- History Filter -->
            <div class="card form-card">
                <div class="card-header-custom"><i class="fas fa-filter mr-2"></i> Filter Payment History</div>
                <div class="card-body">
                    <form method="GET" action="" id="histFilterForm">
                        <input type="hidden" name="id" value="<?php echo $supplier_id; ?>">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" name="from_date" class="form-control" value="<?php echo $hist_from; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" name="to_date" class="form-control" value="<?php echo $hist_to; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <select name="hist_supplier_id" class="form-control">
                                        <option value="0">All Suppliers</option>
                                        <?php
                                        mysqli_data_seek($all_sup_r, 0);
                                        while ($hs = mysqli_fetch_assoc($all_sup_r)): ?>
                                        <option value="<?php echo $hs['id']; ?>" <?php echo ($hist_sup_id == $hs['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hs['supplier_name'] . ' (' . $hs['supplier_code'] . ')'); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-green form-control"><i class="fas fa-search mr-1"></i> Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card shadow text-center p-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Paid</div>
                        <div style="font-size:22px;font-weight:bold;color:#1e7e34;"><?php echo formatCurrency(floatval($hist_summary['total'])); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow text-center p-3">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cash Paid</div>
                        <div style="font-size:22px;font-weight:bold;color:#28a745;"><?php echo formatCurrency(floatval($hist_summary['cash_total'])); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow text-center p-3">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bank Paid</div>
                        <div style="font-size:22px;font-weight:bold;color:#0066cc;"><?php echo formatCurrency(floatval($hist_summary['bank_total'])); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow text-center p-3">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Transactions</div>
                        <div style="font-size:22px;font-weight:bold;color:#e67e22;"><?php echo intval($hist_summary['cnt']); ?></div>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="card form-card">
                <div class="card-header-custom"><i class="fas fa-list mr-2"></i> Payment Transactions
                    <span class="float-right">Period: <?php echo date('d-m-Y', strtotime($hist_from)); ?> to <?php echo date('d-m-Y', strtotime($hist_to)); ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="histTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Payment #</th>
                                    <th>Date</th>
                                    <th>Supplier Code</th>
                                    <th>Supplier Name</th>
                                    <th>Method</th>
                                    <th>Reference No</th>
                                    <th>Invoice No</th>
                                    <th class="text-right">Amount</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $hist_grand = 0;
                                if ($hist_r && mysqli_num_rows($hist_r) > 0):
                                    while ($hp = mysqli_fetch_assoc($hist_r)):
                                        $hist_grand += floatval($hp['amount']);
                                ?>
                                <tr>
                                    <td><strong class="text-primary">PAY-<?php echo str_pad($hp['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($hp['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($hp['supplier_code']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($hp['supplier_name']); ?></strong></td>
                                    <td>
                                        <?php if ($hp['payment_method'] == 'cash'): ?>
                                            <span class="badge badge-success"><i class="fas fa-money-bill-wave"></i> Cash</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><i class="fas fa-university"></i> Bank</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($hp['reference_no']) ?: '-'; ?></td>
                                    <td>
                                        <?php if(!empty($hp['purchase_invoice_no'])): ?>
                                            <span class="font-weight-bold text-warning"><?php echo htmlspecialchars($hp['purchase_invoice_no']); ?></span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="text-right text-danger font-weight-bold"><?php echo formatCurrency(floatval($hp['amount'])); ?></td>
                                    <td><?php echo htmlspecialchars($hp['remarks']) ?: '-'; ?></td>
                                    <td class="text-center">
                                        <a href="print_payment_receipt.php?id=<?php echo $hp['id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Print Receipt">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="10" class="text-center py-4 text-muted">No payment records found for the selected period</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if ($hist_r && mysqli_num_rows($hist_r) > 0): ?>
                            <tfoot>
                                <tr style="background:#f8f9fc;font-weight:bold;">
                                    <td colspan="7" class="text-right"><strong>Total:</strong></td>
                                    <td class="text-right text-danger"><strong><?php echo formatCurrency($hist_grand); ?></strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Payment History -->

    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
            </div>
        </div>
    </footer>
    
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    $('#histTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "language": { "search": "Search:", "zeroRecords": "No records found" }
    });

    // Show/hide bank fields based on payment method
    $('#payment_method').on('change', function() {
        if($(this).val() === 'bank') {
            $('#bank_row').show();
        } else {
            $('#bank_row').hide();
            $('select[name="bank_account_id"]').val('');
            $('input[name="reference_no"]').val('');
        }
    });
    
    // Trigger on load
    $('#payment_method').trigger('change');
    
    // Validate amount against max balance
    $('#amount').on('keyup change', function() {
        var amount = parseFloat($(this).val());
        var maxAmount = <?php echo $current_balance; ?>;
        
        if(amount > maxAmount) {
            $(this).val(maxAmount);
            Swal.fire({
                title: 'Limit Exceeded!',
                text: 'Payment amount cannot exceed outstanding balance: ' + formatCurrencyDisplay(maxAmount),
                icon: 'warning',
                confirmButtonColor: '#1e7e34',
                timer: 2000
            });
        }
    });
    
    // Format currency display helper
    function formatCurrencyDisplay(amount) {
        return '₨ ' + amount.toFixed(2);
    }
});

// Form validation
$('#paymentForm').on('submit', function(e) {
    var paymentDate = $('input[name="payment_date"]').val();
    var amount = parseFloat($('input[name="amount"]').val());
    var paymentMethod = $('select[name="payment_method"]').val();
    var bankAccount = $('select[name="bank_account_id"]').val();
    var maxAmount = <?php echo $current_balance; ?>;
    
    if(!paymentDate) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Payment date is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(isNaN(amount) || amount <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please enter a valid amount greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(amount > maxAmount) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Amount cannot exceed outstanding balance!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(paymentMethod === 'bank' && (!bankAccount || bankAccount === '')) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select a bank account!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    // Confirm payment
    e.preventDefault();
    Swal.fire({
        title: 'Confirm Payment',
        text: 'Are you sure you want to make a payment of ' + formatCurrencyDisplay(amount) + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Process Payment!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#paymentForm').off('submit').submit();
        }
    });
    
    function formatCurrencyDisplay(amount) {
        return '₨ ' + amount.toFixed(2);
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>