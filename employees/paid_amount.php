<?php
/**
 * Employee Payment Page
 * Faysal Glass And Aluminium Centre
 * 
 * Make salary payment to employee and update ledger entries
 * Page: Paid Amount (Salary Payment)
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Employee Payment";
$success_msg = '';
$error_msg = '';

// Check if employee ID is provided
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$employee = null;

if($employee_id > 0) {
    // Fetch employee details
    $query = "SELECT * FROM employees WHERE id = $employee_id";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $employee = mysqli_fetch_assoc($result);
    }
}

// Calculate current balance from ledger
$current_balance = 0;
if($employee_id > 0) {
    $balance_query = "SELECT SUM(credit) - SUM(debit) as balance 
                      FROM employee_ledger WHERE employee_id = $employee_id";
    $balance_result = mysqli_query($conn, $balance_query);
    if($balance_result && mysqli_num_rows($balance_result) > 0) {
        $balance_data = mysqli_fetch_assoc($balance_result);
        $current_balance = floatval($balance_data['balance']);
    }
}

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
    $employee_id = intval($_POST['employee_id']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account_id = isset($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : 0;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
    $amount = floatval($_POST['amount']);
    $salary_month = mysqli_real_escape_string($conn, $_POST['salary_month']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    
    // Fetch employee details
    $emp_query = "SELECT * FROM employees WHERE id = $employee_id";
    $emp_result = mysqli_query($conn, $emp_query);
    if(!$emp_result || mysqli_num_rows($emp_result) == 0) {
        $error_msg = "Employee not found!";
    } else {
        $employee_data = mysqli_fetch_assoc($emp_result);
        
        // Validation
        if(empty($payment_date)) {
            $error_msg = "Payment date is required!";
        } elseif($amount <= 0) {
            $error_msg = "Amount must be greater than 0!";
        } elseif($payment_method == 'bank' && $bank_account_id <= 0) {
            $error_msg = "Please select a bank account!";
        } elseif($amount > $current_balance) {
            $error_msg = "Payment amount cannot exceed outstanding payable balance! Outstanding: " . formatCurrency($current_balance);
        } else {
            // Begin transaction
            mysqli_begin_transaction($conn);
            
            try {
                // 1. Insert into employee_payments
                $insert_payment = "INSERT INTO employee_payments (payment_date, employee_id, payment_method, 
                                  bank_account_id, reference_no, amount, salary_month, remarks, created_by) 
                                  VALUES ('$payment_date', '$employee_id', '$payment_method', 
                                  '$bank_account_id', '$reference_no', '$amount', '$salary_month', '$remarks', '{$_SESSION['user_id']}')";
                
                if(!mysqli_query($conn, $insert_payment)) {
                    throw new Exception("Failed to save payment record: " . mysqli_error($conn));
                }
                
                $payment_id = mysqli_insert_id($conn);
                
                // 2. Calculate new balance (Debit entry for payment - reduces payable)
                $new_balance = $current_balance - $amount;
                
                // 3. Insert into employee_ledger (Debit entry for payment)
                $description = "Salary payment made - " . ucfirst($payment_method);
                if($reference_no) {
                    $description .= " (Ref: $reference_no)";
                }
                if($salary_month) {
                    $description .= " for $salary_month";
                }
                
                $ledger_query = "INSERT INTO employee_ledger (date, employee_id, reference_type, reference_id, 
                                  description, debit, credit, balance, month_year) 
                                  VALUES ('$payment_date', '$employee_id', 'PAYMENT', '$payment_id', 
                                  '$description', '$amount', 0, '$new_balance', '$salary_month')";
                
                if(!mysqli_query($conn, $ledger_query)) {
                    throw new Exception("Failed to update employee ledger: " . mysqli_error($conn));
                }
                
                // 4. Update employee current balance
                $update_employee = "UPDATE employees SET current_balance = $new_balance WHERE id = $employee_id";
                mysqli_query($conn, $update_employee);
                
                // 5. Update cash or bank book
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
                                        VALUES ('$payment_date', 'EMPLOYEE_PAYMENT', '$payment_id', 
                                        'Salary payment to: {$employee_data['employee_name']}', 0, '$amount', '$new_cash_balance')";
                    
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
                                        VALUES ('$payment_date', '$bank_account_id', 'EMPLOYEE_PAYMENT', '$payment_id', 
                                        'Salary payment to: {$employee_data['employee_name']}', 0, '$amount', '$new_bank_balance')";
                    
                    if(!mysqli_query($conn, $bank_book_query)) {
                        throw new Exception("Failed to update bank book");
                    }
                }
                
                // 6. Update employee_salary table if salary_month is specified
                if($salary_month) {
                    // Check if salary record exists
                    $check_salary = "SELECT id, paid_amount, remaining_amount, net_salary, status FROM employee_salary 
                                     WHERE employee_id = $employee_id AND month_year = '$salary_month'";
                    $check_result = mysqli_query($conn, $check_salary);
                    
                    if(mysqli_num_rows($check_result) > 0) {
                        $salary_record = mysqli_fetch_assoc($check_result);
                        $new_paid = $salary_record['paid_amount'] + $amount;
                        $new_remaining = $salary_record['net_salary'] - $new_paid;
                        $new_status = ($new_remaining <= 0) ? 'paid' : 'partial';
                        
                        $update_salary = "UPDATE employee_salary SET 
                                          paid_amount = $new_paid, 
                                          remaining_amount = $new_remaining, 
                                          status = '$new_status' 
                                          WHERE id = {$salary_record['id']}";
                        mysqli_query($conn, $update_salary);
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                $success_msg = "Payment of " . formatCurrency($amount) . " made successfully to " . htmlspecialchars($employee_data['employee_name']);
                
                // Refresh balance
                $current_balance = $new_balance;
                
                echo "<script>setTimeout(() => { window.location.href = 'employee_detail.php?id=$employee_id'; }, 2000);</script>";
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_msg = $e->getMessage();
            }
        }
    }
}

// Get all employees for dropdown if no specific ID provided
$employees_query = "SELECT id, employee_name, employee_code FROM employees WHERE status = 1 ORDER BY employee_name";
$employees_result = mysqli_query($conn, $employees_query);
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
        .employee-info {
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
                <i class="fas fa-money-bill-wave text-success mr-2"></i> Employee Salary Payment
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_ledger.php">Salary Ledger</a></li>
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
        
        <?php if($employee_id > 0 && $employee): ?>
        <!-- Employee Information -->
        <div class="employee-info">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-label"><i class="fas fa-barcode"></i> Employee Code</div>
                    <div class="info-value"><?php echo $employee['employee_code']; ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label"><i class="fas fa-user"></i> Employee Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label"><i class="fas fa-chart-line"></i> Current Outstanding</div>
                    <div class="balance-amount">
                        <?php 
                        if($current_balance > 0) {
                            echo formatCurrency($current_balance) . ' (Payable)';
                        } elseif($current_balance < 0) {
                            echo formatCurrency(abs($current_balance)) . ' (Advance)';
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
            This employee has no outstanding payable amount. No payment needed at this time.
        </div>
        <?php endif; ?>
        
        <!-- Payment Form -->
        <?php if($current_balance > 0): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Make Salary Payment
            </div>
            <div class="card-body">
                <form method="POST" action="" id="paymentForm">
                    <input type="hidden" name="save_payment" value="1">
                    <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                    
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
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> Salary Month (Optional)</label>
                                <input type="month" name="salary_month" class="form-control" 
                                       value="<?php echo date('Y-m'); ?>">
                                <small class="text-muted">Select month if paying specific month's salary</small>
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
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer / Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="bank_row" style="display: none;">
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
                        <div class="col-md-4" id="ref_row" style="display: none;">
                            <div class="form-group">
                                <label><i class="fas fa-receipt text-success mr-1"></i> Reference No / Cheque No</label>
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
        
        <?php else: ?>
        <!-- Employee Selection Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-user-plus mr-2"></i> Select Employee
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-users text-success mr-1"></i> Select Employee</label>
                                <select name="id" class="form-control" required>
                                    <option value="">-- Select Employee --</option>
                                    <?php while($emp = mysqli_fetch_assoc($employees_result)): ?>
                                        <option value="<?php echo $emp['id']; ?>">
                                            <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['employee_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-green form-control">
                                    <i class="fas fa-arrow-right mr-1"></i> Continue to Payment
                                </button>
                            </div>
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
                            <p class="small text-muted">Current payable amount to employee</p>
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
                            <p class="small text-muted">Automatically updates employee ledger and cash/bank book</p>
                        </div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    // Show/hide bank fields based on payment method
    $('#payment_method').on('change', function() {
        if($(this).val() === 'bank') {
            $('#bank_row').show();
            $('#ref_row').show();
        } else {
            $('#bank_row').hide();
            $('#ref_row').hide();
            $('select[name="bank_account_id"]').val('');
            $('input[name="reference_no"]').val('');
        }
    });
    
    // Trigger on load if payment method exists
    if($('#payment_method').length) {
        $('#payment_method').trigger('change');
    }
    
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