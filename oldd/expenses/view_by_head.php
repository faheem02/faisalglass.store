<?php
/**
 * View Expense By Head Page
 * Faysal Glass And Aluminium Centre
 * 
 * Record and manage expenses with automatic accounting entries
 * Page: View Expense By Head
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Expense By Head";
$success_msg = '';
$error_msg = '';

// Fetch expense heads for dropdown
$heads_query = "SELECT id, head_name FROM expense_heads WHERE status = 1 ORDER BY head_name";
$heads_result = mysqli_query($conn, $heads_query);

// Fetch bank accounts for dropdown
$bank_query = "SELECT id, bank_name, account_title, account_number FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);

// Get current cash balance
$cash_query = "SELECT SUM(debit) - SUM(credit) as cash_balance FROM cash_book";
$cash_result = mysqli_query($conn, $cash_query);
$cash_balance = 0;
if($cash_result && mysqli_num_rows($cash_result) > 0) {
    $cash_data = mysqli_fetch_assoc($cash_result);
    $cash_balance = floatval($cash_data['cash_balance']);
}

// Get filter parameters
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : 'today';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_head = isset($_GET['head_id']) ? intval($_GET['head_id']) : 0;

// Apply date filter logic
switch($filter_date) {
    case 'today':
        $from_date = date('Y-m-d');
        $to_date = date('Y-m-d');
        break;
    case 'week':
        $from_date = date('Y-m-d', strtotime('monday this week'));
        $to_date = date('Y-m-d');
        break;
    case 'month':
        $from_date = date('Y-m-01');
        $to_date = date('Y-m-d');
        break;
    case 'custom':
        $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
        $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
        break;
}

// Handle Save Expense
if(isset($_POST['save_expense'])) {
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $head_id = intval($_POST['head_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
    $amount = floatval($_POST['amount']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    
    // Validation
    if(empty($expense_date)) {
        $error_msg = "Expense date is required!";
    } elseif($head_id <= 0) {
        $error_msg = "Please select an expense head!";
    } elseif($amount <= 0) {
        $error_msg = "Amount must be greater than 0!";
    } elseif($payment_method == 'bank' && $bank_account_id <= 0) {
        $error_msg = "Please select a bank account!";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Insert into expenses
            $insert_expense = "INSERT INTO expenses (expense_date, head_id, payment_method, bank_account_id, 
                              reference_no, amount, remarks, created_by) 
                              VALUES ('$expense_date', '$head_id', '$payment_method', 
                              '$bank_account_id', '$reference_no', '$amount', '$remarks', '{$_SESSION['user_id']}')";
            
            if(!mysqli_query($conn, $insert_expense)) {
                throw new Exception("Failed to save expense record: " . mysqli_error($conn));
            }
            
            $expense_id = mysqli_insert_id($conn);
            
            // Get expense head name for description
            $head_name_query = "SELECT head_name FROM expense_heads WHERE id = $head_id";
            $head_name_result = mysqli_query($conn, $head_name_query);
            $head_name = "";
            if($head_name_result && mysqli_num_rows($head_name_result) > 0) {
                $head_data = mysqli_fetch_assoc($head_name_result);
                $head_name = $head_data['head_name'];
            }
            
            // 2. Get current expense ledger balance
            $ledger_balance_query = "SELECT SUM(debit) - SUM(credit) as balance FROM expense_ledger";
            $ledger_balance_result = mysqli_query($conn, $ledger_balance_query);
            $current_ledger_balance = 0;
            if($ledger_balance_result && mysqli_num_rows($ledger_balance_result) > 0) {
                $bal_data = mysqli_fetch_assoc($ledger_balance_result);
                $current_ledger_balance = floatval($bal_data['balance']);
            }
            
            $new_ledger_balance = $current_ledger_balance + $amount;
            
            // 3. Insert into expense_ledger (Debit entry)
            $description = "Expense: $head_name" . ($reference_no ? " (Ref: $reference_no)" : "");
            $ledger_query = "INSERT INTO expense_ledger (date, expense_id, head_id, description, debit, credit, balance) 
                              VALUES ('$expense_date', '$expense_id', '$head_id', '$description', '$amount', 0, '$new_ledger_balance')";
            
            if(!mysqli_query($conn, $ledger_query)) {
                throw new Exception("Failed to update expense ledger: " . mysqli_error($conn));
            }
            
            // 4. Update cash or bank book (Credit entry - money going out)
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
                                    VALUES ('$expense_date', 'EXPENSE', '$expense_id', 
                                    'Expense: $head_name', 0, '$amount', '$new_cash_balance')";
                
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
                                    VALUES ('$expense_date', '$bank_account_id', 'EXPENSE', '$expense_id', 
                                    'Expense: $head_name', 0, '$amount', '$new_bank_balance')";
                
                if(!mysqli_query($conn, $bank_book_query)) {
                    throw new Exception("Failed to update bank book");
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success_msg = "Expense of " . formatCurrency($amount) . " recorded successfully under '" . htmlspecialchars($head_name) . "'";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    }
}

// Handle Delete Expense
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Get expense details before deletion
    $expense_query = "SELECT * FROM expenses WHERE id = $delete_id";
    $expense_result = mysqli_query($conn, $expense_query);
    
    if($expense_result && mysqli_num_rows($expense_result) > 0) {
        $expense = mysqli_fetch_assoc($expense_result);
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete from expense_ledger
            $delete_ledger = "DELETE FROM expense_ledger WHERE expense_id = $delete_id";
            if(!mysqli_query($conn, $delete_ledger)) {
                throw new Exception("Failed to delete ledger entry");
            }
            
            // Delete from cash_book or bank_book
            $delete_cash = "DELETE FROM cash_book WHERE reference_type = 'EXPENSE' AND reference_id = $delete_id";
            mysqli_query($conn, $delete_cash);
            
            $delete_bank = "DELETE FROM bank_book WHERE reference_type = 'EXPENSE' AND reference_id = $delete_id";
            mysqli_query($conn, $delete_bank);
            
            // Delete from expenses
            $delete_expense = "DELETE FROM expenses WHERE id = $delete_id";
            if(!mysqli_query($conn, $delete_expense)) {
                throw new Exception("Failed to delete expense record");
            }
            
            // Recalculate expense ledger balances
            $ledger_query = "SELECT id, debit, credit FROM expense_ledger ORDER BY date ASC, id ASC";
            $ledger_result = mysqli_query($conn, $ledger_query);
            $running_balance = 0;
            while($entry = mysqli_fetch_assoc($ledger_result)) {
                $running_balance = $running_balance + $entry['debit'] - $entry['credit'];
                $update_balance = "UPDATE expense_ledger SET balance = $running_balance WHERE id = {$entry['id']}";
                mysqli_query($conn, $update_balance);
            }
            
            // Recalculate cash book balances
            $cash_ledger_query = "SELECT id, debit, credit FROM cash_book ORDER BY date ASC, id ASC";
            $cash_ledger_result = mysqli_query($conn, $cash_ledger_query);
            $running_cash_balance = 0;
            while($entry = mysqli_fetch_assoc($cash_ledger_result)) {
                $running_cash_balance = $running_cash_balance + $entry['debit'] - $entry['credit'];
                $update_cash = "UPDATE cash_book SET balance = $running_cash_balance WHERE id = {$entry['id']}";
                mysqli_query($conn, $update_cash);
            }
            
            // Recalculate bank book balances
            $bank_ledger_query = "SELECT id, debit, credit, bank_account_id FROM bank_book ORDER BY date ASC, id ASC";
            $bank_ledger_result = mysqli_query($conn, $bank_ledger_query);
            $running_bank_balance = [];
            while($entry = mysqli_fetch_assoc($bank_ledger_result)) {
                $acc_id = $entry['bank_account_id'];
                if(!isset($running_bank_balance[$acc_id])) {
                    $running_bank_balance[$acc_id] = 0;
                }
                $running_bank_balance[$acc_id] = $running_bank_balance[$acc_id] + $entry['debit'] - $entry['credit'];
                $update_bank = "UPDATE bank_book SET balance = {$running_bank_balance[$acc_id]} WHERE id = {$entry['id']}";
                mysqli_query($conn, $update_bank);
            }
            
            mysqli_commit($conn);
            $success_msg = "Expense deleted successfully!";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    }
}

// Build expense query with filters
$expenses_query = "SELECT e.*, eh.head_name, 
                   ba.bank_name, ba.account_title
                   FROM expenses e
                   LEFT JOIN expense_heads eh ON e.head_id = eh.id
                   LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
                   WHERE e.expense_date BETWEEN '$from_date' AND '$to_date'";

if($filter_head > 0) {
    $expenses_query .= " AND e.head_id = $filter_head";
}

$expenses_query .= " ORDER BY e.expense_date DESC, e.id DESC";
$expenses_result = mysqli_query($conn, $expenses_query);

// Calculate summary
$summary_query = "SELECT 
                    SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as total_cash,
                    SUM(CASE WHEN payment_method = 'bank' THEN amount ELSE 0 END) as total_bank,
                    SUM(amount) as total_expense,
                    COUNT(*) as total_count
                  FROM expenses e
                  WHERE e.expense_date BETWEEN '$from_date' AND '$to_date'";

if($filter_head > 0) {
    $summary_query .= " AND e.head_id = $filter_head";
}

$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_cash = floatval($summary['total_cash']);
$total_bank = floatval($summary['total_bank']);
$total_expense = floatval($summary['total_expense']);
$total_count = intval($summary['total_count']);

// Calculate today's expense
$today_summary_query = "SELECT SUM(amount) as today_expense FROM expenses WHERE expense_date = CURDATE()";
$today_summary_result = mysqli_query($conn, $today_summary_query);
$today_summary = mysqli_fetch_assoc($today_summary_result);
$today_expense = floatval($today_summary['today_expense']);

// Calculate this month's expense
$month_summary_query = "SELECT SUM(amount) as month_expense FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$month_summary_result = mysqli_query($conn, $month_summary_query);
$month_summary = mysqli_fetch_assoc($month_summary_result);
$month_expense = floatval($month_summary['month_expense']);

// Get expense head wise totals
$headwise_query = "SELECT eh.head_name, SUM(e.amount) as total_amount
                   FROM expenses e
                   LEFT JOIN expense_heads eh ON e.head_id = eh.id
                   WHERE e.expense_date BETWEEN '$from_date' AND '$to_date'
                   GROUP BY e.head_id
                   ORDER BY total_amount DESC
                   LIMIT 10";
$headwise_result = mysqli_query($conn, $headwise_query);
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
        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-3px);
        }
        .summary-number {
            font-size: 28px;
            font-weight: bold;
        }
        .badge-cash {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-bank {
            background-color: #0066cc;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .table thead th {
            background-color: #1e7e34;
            color: white;
            font-weight: 600;
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
                <i class="fas fa-chart-line text-success mr-2"></i> View Expense By Head
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Expense</a></li>
                <li class="breadcrumb-item active">View Expense By Head</li>
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
        
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Expense</div>
                    <div class="summary-number text-primary"><?php echo formatCurrency($today_expense); ?></div>
                    <small><?php echo date('d-m-Y'); ?></small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">This Month's Expense</div>
                    <div class="summary-number text-success"><?php echo formatCurrency($month_expense); ?></div>
                    <small><?php echo date('F Y'); ?></small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Selected Period</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($total_expense); ?></div>
                    <small><?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Transactions</div>
                    <div class="summary-number text-warning"><?php echo $total_count; ?></div>
                    <small>Expense Records</small>
                </div>
            </div>
        </div>
        
        <!-- Add Expense Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Add New Expense
            </div>
            <div class="card-body">
                <form method="POST" action="" id="expenseForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Date</label>
                                <input type="date" name="expense_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tags text-success mr-1"></i> Expense Head</label>
                                <select name="head_id" class="form-control" required>
                                    <option value="">Select Expense Head</option>
                                    <?php while($head = mysqli_fetch_assoc($heads_result)): ?>
                                        <option value="<?php echo $head['id']; ?>"><?php echo htmlspecialchars($head['head_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer / Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-rupee-sign text-success mr-1"></i> Amount (₨)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" 
                                       placeholder="Enter amount" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="bank_row" style="display: none;">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-receipt text-success mr-1"></i> Reference No</label>
                                <input type="text" name="reference_no" class="form-control" 
                                       placeholder="Enter reference or cheque number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-comment text-success mr-1"></i> Remarks (Optional)</label>
                                <textarea name="remarks" class="form-control" rows="2" 
                                          placeholder="Enter any remarks about this expense"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="save_expense" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Save Expense
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Expenses
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> Period</label>
                                <select name="filter_date" id="filter_date" class="form-control">
                                    <option value="today" <?php echo $filter_date == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $filter_date == 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $filter_date == 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="custom" <?php echo $filter_date == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2" id="from_date_div" style="display: <?php echo $filter_date == 'custom' ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <label><i class="fas fa-calendar text-success mr-1"></i> From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-2" id="to_date_div" style="display: <?php echo $filter_date == 'custom' ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <label><i class="fas fa-calendar text-success mr-1"></i> To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-tags text-success mr-1"></i> Expense Head</label>
                                <select name="head_id" class="form-control">
                                    <option value="0">All Heads</option>
                                    <?php 
                                    mysqli_data_seek($heads_result, 0);
                                    while($head = mysqli_fetch_assoc($heads_result)): 
                                    ?>
                                        <option value="<?php echo $head['id']; ?>" <?php echo ($filter_head == $head['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($head['head_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-green form-control">
                                    <i class="fas fa-search mr-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cash vs Bank Summary -->
        <div class="row">
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cash Expenses</div>
                    <div class="summary-number text-success"><?php echo formatCurrency($total_cash); ?></div>
                    <small><i class="fas fa-money-bill-wave"></i> Cash Payments</small>
                </div>
            </div>
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Bank Expenses</div>
                    <div class="summary-number text-primary"><?php echo formatCurrency($total_bank); ?></div>
                    <small><i class="fas fa-university"></i> Bank Payments</small>
                </div>
            </div>
        </div>
        
        <!-- Expense Head Wise Summary -->
        <?php if(mysqli_num_rows($headwise_result) > 0): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie mr-2"></i> Expense Head Wise Summary
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Expense Head</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-right">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($headwise = mysqli_fetch_assoc($headwise_result)): 
                                $percentage = ($total_expense > 0) ? ($headwise['total_amount'] / $total_expense) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($headwise['head_name']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($headwise['total_amount']); ?></td>
                                <td class="text-right">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%">
                                            <?php echo round($percentage, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Expenses List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Expense List
                <span class="float-right">
                    Total: <strong><?php echo formatCurrency($total_expense); ?></strong>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="expensesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Expense Head</th>
                                <th>Payment Method</th>
                                <th>Reference No</th>
                                <th class="text-right">Amount</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($expense = mysqli_fetch_assoc($expenses_result)): ?>
                            <tr>
                                <td><?php echo $expense['id']; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($expense['expense_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($expense['head_name']); ?></strong></td>
                                <td>
                                    <?php if($expense['payment_method'] == 'cash'): ?>
                                        <span class="badge-cash"><i class="fas fa-money-bill-wave"></i> Cash</span>
                                    <?php else: ?>
                                        <span class="badge-bank"><i class="fas fa-university"></i> Bank</span>
                                        <?php if($expense['bank_name']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($expense['bank_name']); ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($expense['reference_no']) ?: '-'; ?></td>
                                <td class="text-right text-danger font-weight-bold"><?php echo formatCurrency($expense['amount']); ?></td>
                                <td><?php echo htmlspecialchars($expense['remarks']) ?: '-'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewExpense(<?php echo $expense['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $expense['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($expenses_result) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">No expense records found for the selected period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;">
                                <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                <td class="text-right text-danger"><strong><?php echo formatCurrency($total_expense); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Accounting Information -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-info-circle mr-2"></i> Accounting Information
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <i class="fas fa-arrow-right fa-2x text-success mb-2"></i>
                        <h6>Expense Entry</h6>
                        <p class="small text-muted">Expense Ledger → Debit<br>Cash/Bank Book → Credit</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-book fa-2x text-primary mb-2"></i>
                        <h6>Auto Ledger Update</h6>
                        <p class="small text-muted">Every expense automatically updates all ledgers</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h6>Report Compatible</h6>
                        <p class="small text-muted">Works with Profit & Loss and Balance Sheet</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
            </div>
        </div>
    </footer>
    
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #0066cc); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Expense Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="expenseDetails">
                <!-- Expense details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
var expensesTable;

$(document).ready(function() {
    // Initialize DataTable
    if($('#expensesTable tbody tr').length > 0) {
        expensesTable = $('#expensesTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "zeroRecords": "No expenses found"
            }
        });
    }
    
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
    
    // Show/hide custom date range
    $('#filter_date').on('change', function() {
        if($(this).val() === 'custom') {
            $('#from_date_div').show();
            $('#to_date_div').show();
        } else {
            $('#from_date_div').hide();
            $('#to_date_div').hide();
        }
    });
    
    // Trigger on load
    $('#payment_method').trigger('change');
});

// View Expense Details
function viewExpense(id) {
    $.ajax({
        url: 'get_expense_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            $('#expenseDetails').html(response);
            $('#viewExpenseModal').modal('show');
        },
        error: function() {
            Swal.fire({ title: 'Error!', text: 'Failed to load expense details!', icon: 'error', confirmButtonColor: '#1e7e34' });
        }
    });
}

// Confirm Delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This expense record will be permanently deleted and accounting entries will be reversed!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'view_expbyhead.php?delete_id=' + id;
        }
    });
}

// Form validation
$('#expenseForm').on('submit', function(e) {
    var expenseDate = $('input[name="expense_date"]').val();
    var headId = $('select[name="head_id"]').val();
    var amount = parseFloat($('input[name="amount"]').val());
    var paymentMethod = $('select[name="payment_method"]').val();
    var bankAccount = $('select[name="bank_account_id"]').val();
    
    if(!expenseDate) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Expense date is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(!headId || headId === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select an expense head!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(isNaN(amount) || amount <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please enter a valid amount greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(paymentMethod === 'bank' && (!bankAccount || bankAccount === '')) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select a bank account!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>