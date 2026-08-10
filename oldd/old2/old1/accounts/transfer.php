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

$error = '';
$success = '';

// Account types
$account_types = [
    'cash' => 'Cash Account',
    'bank1' => 'Bank 1 - HBL',
    'bank2' => 'Bank 2 - UBL',
    'bank3' => 'Bank 3 - MCB'
];

// Get current balances
$balances = [];

// Cash balance
$cash_query = "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1";
$cash_result = mysqli_query($conn, $cash_query);
$balances['cash'] = 0;
if($cash_result && mysqli_num_rows($cash_result) > 0) {
    $balances['cash'] = floatval(mysqli_fetch_assoc($cash_result)['balance']);
}

// Bank 1 balance
$bank1_query = "SELECT balance FROM bank1_transactions ORDER BY id DESC LIMIT 1";
$bank1_result = mysqli_query($conn, $bank1_query);
$balances['bank1'] = 0;
if($bank1_result && mysqli_num_rows($bank1_result) > 0) {
    $balances['bank1'] = floatval(mysqli_fetch_assoc($bank1_result)['balance']);
}

// Bank 2 balance
$bank2_query = "SELECT balance FROM bank2_transactions ORDER BY id DESC LIMIT 1";
$bank2_result = mysqli_query($conn, $bank2_query);
$balances['bank2'] = 0;
if($bank2_result && mysqli_num_rows($bank2_result) > 0) {
    $balances['bank2'] = floatval(mysqli_fetch_assoc($bank2_result)['balance']);
}

// Bank 3 balance
$bank3_query = "SELECT balance FROM bank3_transactions ORDER BY id DESC LIMIT 1";
$bank3_result = mysqli_query($conn, $bank3_query);
$balances['bank3'] = 0;
if($bank3_result && mysqli_num_rows($bank3_result) > 0) {
    $balances['bank3'] = floatval(mysqli_fetch_assoc($bank3_result)['balance']);
}

// Process transfer
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transfer_date = mysqli_real_escape_string($conn, $_POST['transfer_date']);
    $from_account = mysqli_real_escape_string($conn, $_POST['from_account']);
    $to_account = mysqli_real_escape_string($conn, $_POST['to_account']);
    $amount = floatval($_POST['amount']);
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    // Validation
    if($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif($from_account == $to_account) {
        $error = "From and To accounts cannot be the same";
    } elseif(!isset($balances[$from_account]) || !isset($balances[$to_account])) {
        $error = "Invalid account selected";
    } elseif($amount > $balances[$from_account]) {
        $error = "Insufficient balance in " . $account_types[$from_account] . ". Available: ₨ " . number_format($balances[$from_account], 2);
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $transfer_id = null;
            
            // CASE 1: Cash to Bank
            if($from_account == 'cash' && strpos($to_account, 'bank') === 0) {
                $bank_table = $to_account . '_transactions';
                $bank_name = $account_types[$to_account];
                
                // Get current cash balance
                $cash_bal_query = "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1";
                $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                $current_cash_balance = floatval(mysqli_fetch_assoc($cash_bal_result)['balance']);
                $new_cash_balance = $current_cash_balance - $amount;
                
                // Cash book (CREDIT)
                $cash_insert = "INSERT INTO cash_book (date, reference_type, reference_id, description, credit, balance, created_at) 
                                VALUES ('$transfer_date', 'TRANSFER', 0, 'Transfer to $bank_name - $remarks', $amount, $new_cash_balance, NOW())";
                if(!mysqli_query($conn, $cash_insert)) {
                    throw new Exception("Failed to record cash entry: " . mysqli_error($conn));
                }
                $transfer_id = mysqli_insert_id($conn);
                
                // Update cash book reference
                mysqli_query($conn, "UPDATE cash_book SET reference_id = $transfer_id WHERE id = $transfer_id");
                
                // Get current bank balance
                $bank_bal_query = "SELECT balance FROM $bank_table ORDER BY id DESC LIMIT 1";
                $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                $current_bank_balance = 0;
                if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                    $current_bank_balance = floatval(mysqli_fetch_assoc($bank_bal_result)['balance']);
                }
                $new_bank_balance = $current_bank_balance + $amount;
                
                // Bank transaction (DEBIT)
                $bank_insert = "INSERT INTO $bank_table (date, reference_type, reference_id, description, debit, balance, created_at) 
                                VALUES ('$transfer_date', 'TRANSFER_IN', $transfer_id, 'Transfer from Cash - $remarks', $amount, $new_bank_balance, NOW())";
                if(!mysqli_query($conn, $bank_insert)) {
                    throw new Exception("Failed to record bank entry: " . mysqli_error($conn));
                }
            }
            // CASE 2: Bank to Cash
            elseif(strpos($from_account, 'bank') === 0 && $to_account == 'cash') {
                $bank_table = $from_account . '_transactions';
                $bank_name = $account_types[$from_account];
                
                // Get current bank balance
                $bank_bal_query = "SELECT balance FROM $bank_table ORDER BY id DESC LIMIT 1";
                $bank_bal_result = mysqli_query($conn, $bank_bal_query);
                $current_bank_balance = 0;
                if($bank_bal_result && mysqli_num_rows($bank_bal_result) > 0) {
                    $current_bank_balance = floatval(mysqli_fetch_assoc($bank_bal_result)['balance']);
                }
                $new_bank_balance = $current_bank_balance - $amount;
                
                // Bank transaction (CREDIT)
                $bank_insert = "INSERT INTO $bank_table (date, reference_type, reference_id, description, credit, balance, created_at) 
                                VALUES ('$transfer_date', 'TRANSFER_OUT', 0, 'Transfer to Cash - $remarks', $amount, $new_bank_balance, NOW())";
                if(!mysqli_query($conn, $bank_insert)) {
                    throw new Exception("Failed to record bank entry: " . mysqli_error($conn));
                }
                $transfer_id = mysqli_insert_id($conn);
                
                // Update bank reference
                mysqli_query($conn, "UPDATE $bank_table SET reference_id = $transfer_id WHERE id = $transfer_id");
                
                // Get current cash balance
                $cash_bal_query = "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1";
                $cash_bal_result = mysqli_query($conn, $cash_bal_query);
                $current_cash_balance = 0;
                if($cash_bal_result && mysqli_num_rows($cash_bal_result) > 0) {
                    $current_cash_balance = floatval(mysqli_fetch_assoc($cash_bal_result)['balance']);
                }
                $new_cash_balance = $current_cash_balance + $amount;
                
                // Cash book (DEBIT)
                $cash_insert = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, balance, created_at) 
                                VALUES ('$transfer_date', 'TRANSFER', $transfer_id, 'Transfer from $bank_name - $remarks', $amount, $new_cash_balance, NOW())";
                if(!mysqli_query($conn, $cash_insert)) {
                    throw new Exception("Failed to record cash entry: " . mysqli_error($conn));
                }
            }
            // CASE 3: Bank to Bank
            elseif(strpos($from_account, 'bank') === 0 && strpos($to_account, 'bank') === 0) {
                $from_bank_table = $from_account . '_transactions';
                $to_bank_table = $to_account . '_transactions';
                $from_bank_name = $account_types[$from_account];
                $to_bank_name = $account_types[$to_account];
                
                // Get current from bank balance
                $from_bal_query = "SELECT balance FROM $from_bank_table ORDER BY id DESC LIMIT 1";
                $from_bal_result = mysqli_query($conn, $from_bal_query);
                $current_from_balance = 0;
                if($from_bal_result && mysqli_num_rows($from_bal_result) > 0) {
                    $current_from_balance = floatval(mysqli_fetch_assoc($from_bal_result)['balance']);
                }
                $new_from_balance = $current_from_balance - $amount;
                
                // From bank transaction (CREDIT)
                $from_insert = "INSERT INTO $from_bank_table (date, reference_type, reference_id, description, credit, balance, created_at) 
                                VALUES ('$transfer_date', 'TRANSFER_OUT', 0, 'Transfer to $to_bank_name - $remarks', $amount, $new_from_balance, NOW())";
                if(!mysqli_query($conn, $from_insert)) {
                    throw new Exception("Failed to record from bank entry: " . mysqli_error($conn));
                }
                $transfer_id = mysqli_insert_id($conn);
                
                // Update from bank reference
                mysqli_query($conn, "UPDATE $from_bank_table SET reference_id = $transfer_id WHERE id = $transfer_id");
                
                // Get current to bank balance
                $to_bal_query = "SELECT balance FROM $to_bank_table ORDER BY id DESC LIMIT 1";
                $to_bal_result = mysqli_query($conn, $to_bal_query);
                $current_to_balance = 0;
                if($to_bal_result && mysqli_num_rows($to_bal_result) > 0) {
                    $current_to_balance = floatval(mysqli_fetch_assoc($to_bal_result)['balance']);
                }
                $new_to_balance = $current_to_balance + $amount;
                
                // To bank transaction (DEBIT)
                $to_insert = "INSERT INTO $to_bank_table (date, reference_type, reference_id, description, debit, balance, created_at) 
                              VALUES ('$transfer_date', 'TRANSFER_IN', $transfer_id, 'Transfer from $from_bank_name - $remarks', $amount, $new_to_balance, NOW())";
                if(!mysqli_query($conn, $to_insert)) {
                    throw new Exception("Failed to record to bank entry: " . mysqli_error($conn));
                }
            }
            else {
                throw new Exception("Invalid transfer type");
            }
            
            // Record in account_transfers table
            $transfer_insert = "INSERT INTO account_transfers (transfer_date, from_account_type, from_account_id, to_account_type, to_account_id, amount, reference_no, remarks, created_by, created_at) 
                                VALUES ('$transfer_date', '$from_account', NULL, '$to_account', NULL, $amount, '$reference_no', 'Transfer: $remarks', $user_id, NOW())";
            
            if(!mysqli_query($conn, $transfer_insert)) {
                throw new Exception("Failed to record transfer: " . mysqli_error($conn));
            }
            
            mysqli_commit($conn);
            $success = "Transfer successful! ₨ " . number_format($amount, 2) . " transferred from " . $account_types[$from_account] . " to " . $account_types[$to_account] . ".";
            
            // Refresh balances
            $cash_result = mysqli_query($conn, "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1");
            $balances['cash'] = ($cash_result && mysqli_num_rows($cash_result) > 0) ? floatval(mysqli_fetch_assoc($cash_result)['balance']) : 0;
            
            $bank1_result = mysqli_query($conn, "SELECT balance FROM bank1_transactions ORDER BY id DESC LIMIT 1");
            $balances['bank1'] = ($bank1_result && mysqli_num_rows($bank1_result) > 0) ? floatval(mysqli_fetch_assoc($bank1_result)['balance']) : 0;
            
            $bank2_result = mysqli_query($conn, "SELECT balance FROM bank2_transactions ORDER BY id DESC LIMIT 1");
            $balances['bank2'] = ($bank2_result && mysqli_num_rows($bank2_result) > 0) ? floatval(mysqli_fetch_assoc($bank2_result)['balance']) : 0;
            
            $bank3_result = mysqli_query($conn, "SELECT balance FROM bank3_transactions ORDER BY id DESC LIMIT 1");
            $balances['bank3'] = ($bank3_result && mysqli_num_rows($bank3_result) > 0) ? floatval(mysqli_fetch_assoc($bank3_result)['balance']) : 0;
            
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

$page_title = "Account Transfer";
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
        .account-balance {
            font-size: 18px;
            font-weight: bold;
        }
        .transfer-arrow {
            font-size: 24px;
            color: #1e7e34;
            margin: 0 20px;
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
                            <i class="fas fa-exchange-alt"></i> Account Transfer
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
                            <a href="transfer.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Transfer
                            </a>
                            <a href="cashbook.php" class="btn btn-success btn-sm">
                                <i class="fas fa-book"></i> View Cash Book
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!$success): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card form-card">
                                <div class="card-header-custom">
                                    <i class="fas fa-exchange-alt"></i> Transfer Funds
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="transferForm">
                                        <div class="form-group">
                                            <label class="required-field">Transfer Date</label>
                                            <input type="date" name="transfer_date" class="form-control" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label class="required-field">From Account</label>
                                                    <select name="from_account" id="from_account" class="form-control" required>
                                                        <option value="">-- Select --</option>
                                                        <option value="cash">💰 Cash Account (₨ <?php echo number_format($balances['cash'], 2); ?>)</option>
                                                        <option value="bank1">🏦 Bank 1 - HBL (₨ <?php echo number_format($balances['bank1'], 2); ?>)</option>
                                                        <option value="bank2">🏦 Bank 2 - UBL (₨ <?php echo number_format($balances['bank2'], 2); ?>)</option>
                                                        <option value="bank3">🏦 Bank 3 - MCB (₨ <?php echo number_format($balances['bank3'], 2); ?>)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <div class="transfer-arrow">
                                                    <i class="fas fa-arrow-right"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label class="required-field">To Account</label>
                                                    <select name="to_account" id="to_account" class="form-control" required>
                                                        <option value="">-- Select --</option>
                                                        <option value="cash">💰 Cash Account (₨ <?php echo number_format($balances['cash'], 2); ?>)</option>
                                                        <option value="bank1">🏦 Bank 1 - HBL (₨ <?php echo number_format($balances['bank1'], 2); ?>)</option>
                                                        <option value="bank2">🏦 Bank 2 - UBL (₨ <?php echo number_format($balances['bank2'], 2); ?>)</option>
                                                        <option value="bank3">🏦 Bank 3 - MCB (₨ <?php echo number_format($balances['bank3'], 2); ?>)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="balance-card" id="balanceInfo" style="display: none;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Available Balance</small>
                                                    <div class="account-balance" id="availableBalance"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted">After Transfer Balance</small>
                                                    <div class="account-balance" id="afterTransferBalance" style="color: #dc3545;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="required-field">Amount (PKR)</label>
                                            <input type="number" name="amount" id="amount" class="form-control" 
                                                   step="0.01" min="0.01" required placeholder="Enter amount to transfer">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Reference Number</label>
                                            <input type="text" name="reference_no" class="form-control" 
                                                   placeholder="Transaction reference number">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control" rows="3" 
                                                      placeholder="Purpose of transfer"></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-green btn-lg btn-block">
                                                <i class="fas fa-exchange-alt"></i> Process Transfer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #4e73df; color: white;">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle"></i> Transfer Types</h6>
                                </div>
                                <div class="card-body">
                                    <p><i class="fas fa-exchange-alt text-primary"></i> Supported transfers:</p>
                                    <ul>
                                        <li>💰 Cash → 🏦 Bank</li>
                                        <li>🏦 Bank → 💰 Cash</li>
                                        <li>🏦 Bank → 🏦 Bank</li>
                                    </ul>
                                    <hr>
                                    <p><strong>📊 Accounting Effect:</strong></p>
                                    <ul id="accountingEffect">
                                        <li>Select accounts to see effect</li>
                                    </ul>
                                    <hr>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong><br>
                                        Ensure sufficient balance in the source account before transfer.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #1e7e34; color: white;">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-history"></i> Recent Transfers</h6>
                                </div>
                                <div class="card-body p-0">
                                    <?php
                                    $recent_query = "SELECT * FROM account_transfers ORDER BY id DESC LIMIT 5";
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
                                                <small class="font-weight-bold">₨ <?php echo number_format($rec['amount'], 2); ?></small>
                                            </div>
                                            <div class="mt-1">
                                                <small><?php echo ucfirst(str_replace('bank', 'Bank ', $rec['from_account_type'])); ?></small>
                                                <i class="fas fa-arrow-right text-success"></i>
                                                <small><?php echo ucfirst(str_replace('bank', 'Bank ', $rec['to_account_type'])); ?></small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo !empty($rec['reference_no']) ? 'Ref: ' . htmlspecialchars($rec['reference_no']) : ''; ?>
                                            </small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-exchange-alt fa-2x text-muted mb-2 d-block"></i>
                                        <p class="text-muted mb-0">No recent transfers</p>
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
        // Account balances from PHP
        var balances = {
            cash: <?php echo $balances['cash']; ?>,
            bank1: <?php echo $balances['bank1']; ?>,
            bank2: <?php echo $balances['bank2']; ?>,
            bank3: <?php echo $balances['bank3']; ?>
        };
        
        var accountNames = {
            cash: 'Cash Account',
            bank1: 'Bank 1 - HBL',
            bank2: 'Bank 2 - UBL',
            bank3: 'Bank 3 - MCB'
        };
        
        $(document).ready(function() {
            $('#from_account, #to_account').change(function() {
                updateBalanceInfo();
                updateAccountingEffect();
            });
            
            $('#amount').on('keyup change', function() {
                updateBalanceInfo();
            });
            
            function updateBalanceInfo() {
                var fromAccount = $('#from_account').val();
                var toAccount = $('#to_account').val();
                var amount = parseFloat($('#amount').val());
                
                if(fromAccount && balances[fromAccount] !== undefined) {
                    var balance = balances[fromAccount];
                    $('#balanceInfo').show();
                    $('#availableBalance').html('₨ ' + balance.toFixed(2));
                    $('#availableBalance').css('color', balance > 0 ? '#1e7e34' : '#dc3545');
                    
                    if(!isNaN(amount) && amount > 0) {
                        var afterBalance = balance - amount;
                        $('#afterTransferBalance').html('₨ ' + afterBalance.toFixed(2));
                        $('#afterTransferBalance').css('color', afterBalance >= 0 ? '#1e7e34' : '#dc3545');
                        
                        if(afterBalance < 0) {
                            $('#amount').addClass('is-invalid');
                        } else {
                            $('#amount').removeClass('is-invalid');
                        }
                    } else {
                        $('#afterTransferBalance').html('₨ ' + balance.toFixed(2));
                    }
                } else {
                    $('#balanceInfo').hide();
                }
            }
            
            function updateAccountingEffect() {
                var fromAccount = $('#from_account').val();
                var toAccount = $('#to_account').val();
                var effectHtml = '';
                
                if(fromAccount && toAccount && fromAccount != toAccount) {
                    if(fromAccount == 'cash' && toAccount.indexOf('bank') === 0) {
                        effectHtml = '<li>Cash Book: <span class="text-danger">CREDIT (-)</span></li>';
                        effectHtml += '<li>' + accountNames[toAccount] + ': <span class="text-success">DEBIT (+)</span></li>';
                    } else if(fromAccount.indexOf('bank') === 0 && toAccount == 'cash') {
                        effectHtml = '<li>' + accountNames[fromAccount] + ': <span class="text-danger">CREDIT (-)</span></li>';
                        effectHtml += '<li>Cash Book: <span class="text-success">DEBIT (+)</span></li>';
                    } else if(fromAccount.indexOf('bank') === 0 && toAccount.indexOf('bank') === 0) {
                        effectHtml = '<li>' + accountNames[fromAccount] + ': <span class="text-danger">CREDIT (-)</span></li>';
                        effectHtml += '<li>' + accountNames[toAccount] + ': <span class="text-success">DEBIT (+)</span></li>';
                    }
                } else {
                    effectHtml = '<li>Select accounts to see effect</li>';
                }
                
                $('#accountingEffect').html(effectHtml);
            }
            
            $('#transferForm').on('submit', function(e) {
                var fromAccount = $('#from_account').val();
                var toAccount = $('#to_account').val();
                var amount = parseFloat($('#amount').val());
                var balance = balances[fromAccount];
                
                if(!fromAccount || !toAccount) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please select both From and To accounts',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(fromAccount == toAccount) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'From and To accounts cannot be the same',
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
                        text: `Available balance in ${accountNames[fromAccount]} is ₨ ${balance.toFixed(2)}`,
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Transfer',
                    html: `Are you sure you want to transfer <strong>₨ ${amount.toFixed(2)}</strong> from <strong>${accountNames[fromAccount]}</strong> to <strong>${accountNames[toAccount]}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e7e34',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, transfer!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#transferForm').off('submit').submit();
                    }
                });
                
                return false;
            });
        });
    </script>
</body>
</html>