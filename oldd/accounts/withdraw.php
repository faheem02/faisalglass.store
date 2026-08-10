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
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

$error = '';
$success = '';

// Fetch bank accounts dynamically
$banks_query = "SELECT id, bank_name, account_number, current_balance FROM bank_accounts WHERE status = 1 ORDER BY bank_name ASC";
$banks_result = mysqli_query($conn, $banks_query);
$banks = [];
$bank_balances = [];
if($banks_result && mysqli_num_rows($banks_result) > 0) {
    while($b = mysqli_fetch_assoc($banks_result)) {
        $banks[$b['id']] = $b;
        $bank_balances[$b['id']] = floatval($b['current_balance']);
    }
}

// Process withdrawal
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $withdraw_date = mysqli_real_escape_string($conn, $_POST['withdraw_date']);
    $bank_id = intval($_POST['bank_id']);
    $amount = floatval($_POST['amount']);
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif(!isset($banks[$bank_id])) {
        $error = "Please select a valid bank";
    } elseif($amount > $bank_balances[$bank_id]) {
        $error = "Insufficient balance in " . $banks[$bank_id]['bank_name'] . ". Available: " . formatCurrency($bank_balances[$bank_id]);
    } else {
        mysqli_begin_transaction($conn);
        try {
            $bank_name = $banks[$bank_id]['bank_name'];
            
            // Bank book entry (CREDIT - money going out)
            $bank_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $bank_id";
            $bank_bal_result = mysqli_query($conn, $bank_bal_query);
            $current_bank_balance = 0;
            if($bank_bal_result && $row = mysqli_fetch_assoc($bank_bal_result)) {
                $current_bank_balance = floatval($row['balance']);
            }
            $new_bank_balance = $current_bank_balance - $amount;
            
            $bank_insert = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, credit, balance, created_at)
                            VALUES ('$withdraw_date', $bank_id, 'WITHDRAW', 0, 'Withdrawal to Cash - $remarks', $amount, $new_bank_balance, NOW())";
            if(!mysqli_query($conn, $bank_insert)) throw new Exception("Bank entry failed: " . mysqli_error($conn));
            $bank_trans_id = mysqli_insert_id($conn);
            mysqli_query($conn, "UPDATE bank_book SET reference_id = $bank_trans_id WHERE id = $bank_trans_id");
            
            // Update bank_accounts current_balance
            mysqli_query($conn, "UPDATE bank_accounts SET current_balance = $new_bank_balance WHERE id = $bank_id");
            
            // Cash book entry (DEBIT - cash in)
            $cash_bal_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
            $cash_bal_result = mysqli_query($conn, $cash_bal_query);
            $current_cash_balance = 0;
            if($cash_bal_result && $row = mysqli_fetch_assoc($cash_bal_result)) {
                $current_cash_balance = floatval($row['balance']);
            }
            $new_cash_balance = $current_cash_balance + $amount;
            
            $cash_insert = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, balance, created_at)
                            VALUES ('$withdraw_date', 'WITHDRAW', $bank_trans_id, 'Cash withdrawal from $bank_name - $remarks', $amount, $new_cash_balance, NOW())";
            if(!mysqli_query($conn, $cash_insert)) throw new Exception("Cash entry failed: " . mysqli_error($conn));
            
            // Account transfers record
            $transfer_insert = "INSERT INTO account_transfers (transfer_date, from_account_type, from_account_id, to_account_type, to_account_id, amount, reference_no, remarks, created_by, created_at)
                                VALUES ('$withdraw_date', 'bank', $bank_id, 'cash', 0, $amount, '$reference_no', 'Withdrawal: $remarks', $user_id, NOW())";
            mysqli_query($conn, $transfer_insert);
            
            mysqli_commit($conn);
            $success = "Withdrawal successful! " . formatCurrency($amount) . " withdrawn from $bank_name.";
            
            // Refresh balances
            $banks_result = mysqli_query($conn, "SELECT id, bank_name, account_number, current_balance FROM bank_accounts WHERE status = 1 ORDER BY bank_name ASC");
            $banks = []; $bank_balances = [];
            while($b = mysqli_fetch_assoc($banks_result)) {
                $banks[$b['id']] = $b;
                $bank_balances[$b['id']] = floatval($b['current_balance']);
            }
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

$page_title = "Withdraw from Bank";
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #4e73df); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .required-field::after { content: " *"; color: red; }
        .balance-card { background: #f8f9fc; border-left: 4px solid #1e7e34; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .bank-balance {
            font-size: 18px;
            font-weight: bold;
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
                            <i class="fas fa-money-bill-wave"></i> Withdraw from Bank
                        </h1>
                        <a href="cashbook.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Cash Book
                        </a>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="withdraw.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Withdrawal
                            </a>
                            <a href="cashbook.php" class="btn btn-success btn-sm">
                                <i class="fas fa-book"></i> View Cash Book
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!$success): ?>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="card form-card">
                                <div class="card-header-custom">
                                    <i class="fas fa-hand-holding-usd"></i> Withdrawal Form
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="withdrawForm">
                                        <div class="form-group">
                                            <label class="required-field">Withdrawal Date</label>
                                            <input type="date" name="withdraw_date" class="form-control" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Select Bank</label>
                                            <select name="bank_id" id="bank_id" class="form-control" required>
                                                <option value="">-- Select Bank --</option>
                                                <?php foreach($banks as $bid => $bank): ?>
                                                    <option value="<?php echo $bid; ?>" 
                                                            data-balance="<?php echo $bank_balances[$bid]; ?>">
                                                        <?php echo htmlspecialchars($bank['bank_name']); ?> 
                                                        (Balance: <?php echo formatCurrency($bank_balances[$bid]); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="balance-card" id="balanceInfo" style="display: none;">
                                            <small class="text-muted">Available Balance</small>
                                            <div class="bank-balance" id="availableBalance"></div>
                                            <small class="text-muted mt-2 d-block">After Withdrawal Balance</small>
                                            <div class="bank-balance" id="afterWithdrawBalance" style="color: #dc3545;"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Amount (PKR)</label>
                                            <input type="number" name="amount" id="amount" class="form-control" 
                                                   step="0.01" min="0.01" required placeholder="Enter amount to withdraw">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Reference Number</label>
                                            <input type="text" name="reference_no" class="form-control" 
                                                   placeholder="Cheque number, transaction ID, or reference">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control" rows="3" 
                                                      placeholder="Purpose of withdrawal"></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-green btn-lg btn-block">
                                                <i class="fas fa-money-bill-wave"></i> Process Withdrawal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #4e73df; color: white;">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle"></i> Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><i class="fas fa-info-circle text-primary"></i> Withdraw money from bank to cash.</p>
                                    <hr>
                                    <p><strong>📊 Accounting Effect:</strong></p>
                                    <ul>
                                        <li>Bank Account: <span class="text-danger">CREDIT (-)</span></li>
                                        <li>Cash Book: <span class="text-success">DEBIT (+)</span></li>
                                    </ul>
                                    <hr>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong><br>
                                        Ensure sufficient balance in the selected bank account before withdrawal.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #1e7e34; color: white;">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-history"></i> Recent Withdrawals</h6>
                                </div>
                                <div class="card-body p-0">
                                    <?php
                                    $recent_query = "SELECT at.*, ba.bank_name 
                                                     FROM account_transfers at
                                                     LEFT JOIN bank_accounts ba ON at.from_account_id = ba.id
                                                     WHERE at.from_account_type = 'bank' AND at.to_account_type = 'cash'
                                                     ORDER BY at.id DESC LIMIT 5";
                                    $recent_result = mysqli_query($conn, $recent_query);
                                    if(mysqli_num_rows($recent_result) > 0):
                                    ?>
                                    <div class="list-group list-group-flush">
                                        <?php while($rec = mysqli_fetch_assoc($recent_result)): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d-m-Y', strtotime($rec['transfer_date'])); ?>
                                                </small>
                                                <small class="text-danger font-weight-bold">- <?php echo formatCurrency($rec['amount']); ?></small>
                                            </div>
                                            <div class="mt-1">
                                                <strong><?php echo htmlspecialchars($rec['bank_name'] ?? 'Bank'); ?></strong>
                                                <i class="fas fa-arrow-right"></i>
                                                <strong>Cash</strong>
                                            </div>
                                            <small class="text-muted">
                                                Ref: <?php echo !empty($rec['reference_no']) ? htmlspecialchars($rec['reference_no']) : 'N/A'; ?>
                                            </small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-2x text-muted mb-2 d-block"></i>
                                        <p class="text-muted mb-0">No recent withdrawals</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
            $('#bank_id').change(function() {
                var selectedOption = $(this).find('option:selected');
                var balance = parseFloat(selectedOption.data('balance'));
                
                if(balance !== undefined && $(this).val()) {
                    $('#balanceInfo').show();
                    $('#availableBalance').html('₨ ' + balance.toFixed(2));
                    $('#availableBalance').css('color', balance > 0 ? '#1e7e34' : '#dc3545');
                    
                    var amount = parseFloat($('#amount').val());
                    if(!isNaN(amount) && amount > 0) {
                        var afterBalance = balance - amount;
                        $('#afterWithdrawBalance').html('₨ ' + afterBalance.toFixed(2));
                        $('#afterWithdrawBalance').css('color', afterBalance >= 0 ? '#1e7e34' : '#dc3545');
                    } else {
                        $('#afterWithdrawBalance').html('₨ ' + balance.toFixed(2));
                    }
                } else {
                    $('#balanceInfo').hide();
                }
            });
            
            $('#amount').on('keyup change', function() {
                var amount = parseFloat($(this).val());
                var selectedOption = $('#bank_id').find('option:selected');
                var balance = parseFloat(selectedOption.data('balance'));
                
                if(!isNaN(amount) && amount > 0 && !isNaN(balance) && $('#bank_id').val()) {
                    var afterBalance = balance - amount;
                    $('#afterWithdrawBalance').html('₨ ' + afterBalance.toFixed(2));
                    $('#afterWithdrawBalance').css('color', afterBalance >= 0 ? '#1e7e34' : '#dc3545');
                    
                    if(afterBalance < 0) {
                        $('#amount').addClass('is-invalid');
                    } else {
                        $('#amount').removeClass('is-invalid');
                    }
                } else {
                    $('#afterWithdrawBalance').html('₨ ' + (balance || 0).toFixed(2));
                }
            });
            
            $('#withdrawForm').on('submit', function(e) {
                var amount = parseFloat($('#amount').val());
                var bankId = $('#bank_id').val();
                var selectedOption = $('#bank_id').find('option:selected');
                var balance = parseFloat(selectedOption.data('balance'));
                var bankName = selectedOption.text().trim();
                
                if(!bankId) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please select a bank',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(isNaN(amount) || amount <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter a valid amount greater than 0',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(amount > balance) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Insufficient Balance!',
                        text: `Available balance in ${bankName} is ₨ ${balance.toFixed(2)}`,
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Withdrawal',
                    html: `Are you sure you want to withdraw <strong>₨ ${amount.toFixed(2)}</strong> from <strong>${bankName}</strong> to Cash?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e7e34',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, withdraw!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#withdrawForm').off('submit').submit();
                    }
                });
                
                return false;
            });
        });
    </script>
</body>
</html>