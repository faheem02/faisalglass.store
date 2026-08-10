<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

// Fetch customer if ID provided
$customer = null;
if($customer_id > 0) {
    $query = "SELECT * FROM customers WHERE id = $customer_id";
    $result = mysqli_query($conn, $query);
    $customer = mysqli_fetch_assoc($result);
}

// Get bank accounts
$bank_query = "SELECT * FROM bank_accounts WHERE status = 1 ORDER BY bank_name";
$bank_result = mysqli_query($conn, $bank_query);

// Get all customers for dropdown
$all_customers_query = "SELECT id, customer_code, customer_name, current_balance FROM customers WHERE status = 1 ORDER BY customer_name";
$all_customers_result = mysqli_query($conn, $all_customers_query);

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receipt_date = mysqli_real_escape_string($conn, $_POST['receipt_date']);
    $customer_id = intval($_POST['customer_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account_id = ($payment_method == 'bank' && isset($_POST['bank_account_id'])) ? intval($_POST['bank_account_id']) : NULL;
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $amount = floatval($_POST['amount']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $created_by = $_SESSION['user_id'];
    
    // Validation
    if($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif($payment_method == 'bank' && empty($bank_account_id)) {
        $error = "Please select a bank account";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get customer current balance
            $bal_query = "SELECT current_balance FROM customers WHERE id = $customer_id";
            $bal_result = mysqli_query($conn, $bal_query);
            $current_balance = floatval(mysqli_fetch_assoc($bal_result)['current_balance']);
            
            // Insert into customer_receipts
            $insert_receipt = "INSERT INTO customer_receipts (receipt_date, customer_id, payment_method, bank_account_id, reference_no, amount, remarks, created_by, created_at) 
                               VALUES ('$receipt_date', $customer_id, '$payment_method', " . ($bank_account_id ? $bank_account_id : "NULL") . ", '$reference_no', $amount, '$remarks', $created_by, NOW())";
            
            if(!mysqli_query($conn, $insert_receipt)) {
                throw new Exception("Failed to save receipt: " . mysqli_error($conn));
            }
            
            $receipt_id = mysqli_insert_id($conn);
            
            // Update customer ledger (CREDIT entry - payment reduces receivable)
            $new_balance = $current_balance - $amount;
            
            $ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, credit, balance, created_at) 
                             VALUES ('$receipt_date', $customer_id, 'PAYMENT', $receipt_id, 'Payment received - $remarks', $amount, $new_balance, NOW())";
            
            if(!mysqli_query($conn, $ledger_query)) {
                throw new Exception("Failed to update ledger: " . mysqli_error($conn));
            }
            
            // Update customer current balance
            $update_customer = "UPDATE customers SET current_balance = $new_balance WHERE id = $customer_id";
            if(!mysqli_query($conn, $update_customer)) {
                throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
            }
            
            // Update cash book or bank book
            if($payment_method == 'cash') {
                // Get current cash balance
                $cash_bal_query = "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1";
                $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                $cash_balance = 0;
                if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                    $cash_balance = floatval(mysqli_fetch_assoc($cash_bal_result)['balance']);
                }
                $new_cash_balance = $cash_balance + $amount;
                
                $cash_query = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, balance, created_at) 
                               VALUES ('$receipt_date', 'customer_receipt', $receipt_id, 'Payment received from customer - $remarks', $amount, $new_cash_balance, NOW())";
                if(!mysqli_query($conn, $cash_query)) {
                    throw new Exception("Failed to update cash book: " . mysqli_error($conn));
                }
            } elseif($payment_method == 'bank') {
                // Get current bank balance for this account
                $bank_bal_query = "SELECT balance FROM bank_book WHERE bank_account_id = $bank_account_id ORDER BY id DESC LIMIT 1";
                $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                $bank_balance = 0;
                if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                    $bank_balance = floatval(mysqli_fetch_assoc($bank_bal_result)['balance']);
                }
                $new_bank_balance = $bank_balance + $amount;
                
                $bank_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, balance, created_at) 
                               VALUES ('$receipt_date', $bank_account_id, 'customer_receipt', $receipt_id, 'Payment received from customer - $remarks', $amount, $new_bank_balance, NOW())";
                if(!mysqli_query($conn, $bank_query)) {
                    throw new Exception("Failed to update bank book: " . mysqli_error($conn));
                }
                
                // Update bank account current balance
                $update_bank = "UPDATE bank_accounts SET current_balance = current_balance + $amount WHERE id = $bank_account_id";
                mysqli_query($conn, $update_bank);
            }
            
            mysqli_commit($conn);
            $success = "Payment received successfully!";
            
            // Refresh customer data
            $query = "SELECT * FROM customers WHERE id = $customer_id";
            $result = mysqli_query($conn, $query);
            $customer = mysqli_fetch_assoc($result);
            
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Payment | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .required-field::after { content: " *"; color: red; }
        .customer-info { background: #f8f9fc; border-left: 4px solid #1e7e34; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .balance-amount { font-size: 24px; font-weight: bold; color: #1e7e34; }
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <div class="topbar">
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
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-money-bill-wave"></i> Receive Payment from Customer
                        </h1>
                        <a href="view_customer.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card form-card">
                                <div class="card-header-custom">
                                    <i class="fas fa-hand-holding-usd"></i> Payment Receipt Form
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="receiptForm">
                                        <div class="form-group">
                                            <label class="required-field">Receipt Date</label>
                                            <input type="date" name="receipt_date" class="form-control" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Select Customer</label>
                                            <select name="customer_id" id="customer_id" class="form-control" required>
                                                <option value="">-- Select Customer --</option>
                                                <?php 
                                                mysqli_data_seek($all_customers_result, 0);
                                                while($c = mysqli_fetch_assoc($all_customers_result)): 
                                                ?>
                                                    <option value="<?php echo $c['id']; ?>" 
                                                        <?php echo ($customer && $customer['id'] == $c['id']) ? 'selected' : ''; ?>
                                                        data-balance="<?php echo $c['current_balance']; ?>"
                                                        data-name="<?php echo htmlspecialchars($c['customer_name']); ?>"
                                                        data-code="<?php echo htmlspecialchars($c['customer_code']); ?>">
                                                        <?php echo htmlspecialchars($c['customer_code'] . ' - ' . $c['customer_name']); ?>
                                                        (Balance: <?php echo number_format(abs($c['current_balance']), 2); ?> <?php echo $c['current_balance'] >= 0 ? 'DR' : 'CR'; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <?php if($customer): ?>
                                        <div class="customer-info" id="customerInfo">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Customer Code</small>
                                                    <div><strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted">Customer Name</small>
                                                    <div><strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong></div>
                                                </div>
                                                <div class="col-md-12 mt-2">
                                                    <small class="text-muted">Current Balance</small>
                                                    <div class="balance-amount">
                                                        <?php 
                                                        $bal = floatval($customer['current_balance']);
                                                        echo '₨ ' . number_format(abs($bal), 2);
                                                        echo ($bal >= 0) ? ' DR (Receivable)' : ' CR (Payable)';
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="customer-info" id="customerInfo" style="display: none;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Customer Code</small>
                                                    <div><strong id="displayCode"></strong></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted">Customer Name</small>
                                                    <div><strong id="displayName"></strong></div>
                                                </div>
                                                <div class="col-md-12 mt-2">
                                                    <small class="text-muted">Current Balance</small>
                                                    <div class="balance-amount" id="displayBalance"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Payment Method</label>
                                            <select name="payment_method" id="payment_method" class="form-control" required>
                                                <option value="">-- Select Payment Method --</option>
                                                <option value="cash">Cash</option>
                                                <option value="bank">Bank Transfer / Cheque</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group" id="bank_account_div" style="display: none;">
                                            <label class="required-field">Bank Account</label>
                                            <select name="bank_account_id" class="form-control">
                                                <option value="">-- Select Bank Account --</option>
                                                <?php 
                                                mysqli_data_seek($bank_result, 0);
                                                while($bank = mysqli_fetch_assoc($bank_result)): 
                                                ?>
                                                    <option value="<?php echo $bank['id']; ?>">
                                                        <?php echo htmlspecialchars($bank['bank_name'] . ' - ' . ($bank['account_title'] ?? $bank['account_number'])); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Reference / Cheque Number</label>
                                            <input type="text" name="reference_no" class="form-control" 
                                                   placeholder="Cheque number, transaction ID, or reference">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Amount (PKR)</label>
                                            <input type="number" name="amount" id="amount" class="form-control" 
                                                   step="0.01" min="0.01" required placeholder="Enter amount">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control" rows="3" 
                                                      placeholder="Any additional notes about this payment"></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-green btn-lg btn-block">
                                                <i class="fas fa-save"></i> Receive Payment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #4e73df; color: white;">
                                    <h6 class="m-0 font-weight-bold">Payment Instructions</h6>
                                </div>
                                <div class="card-body">
                                    <p><i class="fas fa-info-circle text-primary"></i> This will record the payment received from customer.</p>
                                    <hr>
                                    <p><strong>Accounting Effect:</strong></p>
                                    <ul>
                                        <li>Customer Ledger: <span class="text-danger">CREDIT</span></li>
                                        <li>Cash/Bank Book: <span class="text-success">DEBIT</span></li>
                                        <li>Customer Balance: <span class="text-danger">DECREASES</span></li>
                                    </ul>
                                    <hr>
                                    <div class="alert alert-info">
                                        <i class="fas fa-lightbulb"></i> <strong>Note:</strong><br>
                                        After receiving payment, the customer's receivable balance will decrease.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #1e7e34; color: white;">
                                    <h6 class="m-0 font-weight-bold">Recent Payments</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $recent_query = "SELECT cr.*, c.customer_name, c.customer_code 
                                                     FROM customer_receipts cr 
                                                     JOIN customers c ON cr.customer_id = c.id 
                                                     ORDER BY cr.id DESC LIMIT 5";
                                    $recent_result = mysqli_query($conn, $recent_query);
                                    if(mysqli_num_rows($recent_result) > 0):
                                    ?>
                                    <div class="list-group">
                                        <?php while($rec = mysqli_fetch_assoc($recent_result)): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <small class="text-muted"><?php echo date('d-m-Y', strtotime($rec['receipt_date'])); ?></small>
                                                <small class="text-success">₨ <?php echo number_format($rec['amount'], 2); ?></small>
                                            </div>
                                            <div class="mt-1">
                                                <strong><?php echo htmlspecialchars($rec['customer_name']); ?></strong>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo ucfirst($rec['payment_method']); ?>
                                                <?php echo !empty($rec['reference_no']) ? ' - Ref: ' . htmlspecialchars($rec['reference_no']) : ''; ?>
                                            </small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted text-center mb-0">No recent payments</p>
                                    <?php endif; ?>
                                </div>
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
    
    <script>
        $(document).ready(function() {
            // Show/hide bank account field based on payment method
            $('#payment_method').change(function() {
                if($(this).val() == 'bank') {
                    $('#bank_account_div').show();
                    $('select[name="bank_account_id"]').prop('required', true);
                } else {
                    $('#bank_account_div').hide();
                    $('select[name="bank_account_id"]').prop('required', false);
                }
            });
            
            // Load customer info when selected
            $('#customer_id').change(function() {
                var selectedOption = $(this).find('option:selected');
                var balance = selectedOption.data('balance');
                var customerName = selectedOption.data('name');
                var customerCode = selectedOption.data('code');
                var customerId = $(this).val();
                
                if(customerId) {
                    $('#customerInfo').show();
                    $('#displayCode').text(customerCode);
                    $('#displayName').text(customerName);
                    
                    var balanceNum = parseFloat(balance);
                    var balanceText = '₨ ' + Math.abs(balanceNum).toFixed(2);
                    balanceText += (balanceNum >= 0) ? ' DR (Receivable)' : ' CR (Payable)';
                    $('#displayBalance').html(balanceText);
                    
                    if(balanceNum >= 0) {
                        $('#displayBalance').css('color', '#28a745');
                    } else {
                        $('#displayBalance').css('color', '#dc3545');
                    }
                } else {
                    $('#customerInfo').hide();
                }
            });
            
            // Form validation
            $('#receiptForm').on('submit', function(e) {
                var amount = parseFloat($('#amount').val());
                var customerId = $('#customer_id').val();
                
                if(!customerId) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please select a customer',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(amount <= 0 || isNaN(amount)) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter a valid amount greater than 0',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                var paymentMethod = $('#payment_method').val();
                if(!paymentMethod) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please select a payment method',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(paymentMethod == 'bank') {
                    var bankAccount = $('select[name="bank_account_id"]').val();
                    if(!bankAccount) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error!',
                            text: 'Please select a bank account',
                            icon: 'error',
                            confirmButtonColor: '#1e7e34'
                        });
                        return false;
                    }
                }
                
                // Confirm before submitting
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Payment',
                    text: `Are you sure you want to receive ₨ ${amount.toFixed(2)} from this customer?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e7e34',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, receive payment!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#receiptForm').off('submit').submit();
                    }
                });
                
                return false;
            });
        });
    </script>
</body>
</html>