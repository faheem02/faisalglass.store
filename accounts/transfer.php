<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

$error = '';
$success = '';

// Fetch banks dynamically
$bank_list_query = "SELECT id, bank_name, account_number, current_balance FROM bank_accounts WHERE status = 1 ORDER BY bank_name ASC";
$bank_list_result = mysqli_query($conn, $bank_list_query);
$banks = [];
if($bank_list_result && mysqli_num_rows($bank_list_result) > 0) {
    while($b = mysqli_fetch_assoc($bank_list_result)) {
        $banks[$b['id']] = $b;
    }
}

// Get cash balance
$cash_query = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
$cash_result = mysqli_query($conn, $cash_query);
$cash_balance = 0;
if($cash_result && $row = mysqli_fetch_assoc($cash_result)) {
    $cash_balance = floatval($row['balance']);
}

// Process transfer
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transfer_date = mysqli_real_escape_string($conn, $_POST['transfer_date']);
    $from_type = mysqli_real_escape_string($conn, $_POST['from_type']);
    $from_id = intval($_POST['from_id']);
    $to_type = mysqli_real_escape_string($conn, $_POST['to_type']);
    $to_id = intval($_POST['to_id']);
    $amount = floatval($_POST['amount']);
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $from_label = ($from_type == 'cash') ? 'Cash' : ($banks[$from_id]['bank_name'] ?? 'Unknown');
    $to_label = ($to_type == 'cash') ? 'Cash' : ($banks[$to_id]['bank_name'] ?? 'Unknown');
    $from_balance = ($from_type == 'cash') ? $cash_balance : floatval($banks[$from_id]['current_balance'] ?? 0);

    if($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif($from_type == $to_type && $from_id == $to_id) {
        $error = "From and To accounts cannot be the same";
    } elseif($amount > $from_balance) {
        $error = "Insufficient balance in $from_label. Available: " . formatCurrency($from_balance);
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Get current cash balance
            $cash_bal_q = "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book";
            $cash_bal_r = mysqli_query($conn, $cash_bal_q);
            $current_cash = 0;
            if($cash_bal_r && $rw = mysqli_fetch_assoc($cash_bal_r)) $current_cash = floatval($rw['balance']);

            $transfer_id = null;

            // Cash to Bank
            if($from_type == 'cash' && $to_type == 'bank') {
                $new_cash = $current_cash - $amount;
                $cash_q = "INSERT INTO cash_book (date, reference_type, reference_id, description, credit, balance, created_at)
                           VALUES ('$transfer_date', 'TRANSFER', 0, 'Transfer to {$banks[$to_id]['bank_name']} - $remarks', $amount, $new_cash, NOW())";
                if(!mysqli_query($conn, $cash_q)) throw new Exception("Cash entry failed");
                $transfer_id = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE cash_book SET reference_id = $transfer_id WHERE id = $transfer_id");

                $bank_bal_q = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $to_id";
                $bank_bal_r = mysqli_query($conn, $bank_bal_q);
                $current_bank = $bank_bal_r ? floatval(mysqli_fetch_assoc($bank_bal_r)['balance']) : 0;
                $new_bank = $current_bank + $amount;

                $bank_q = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, balance, created_at)
                           VALUES ('$transfer_date', $to_id, 'TRANSFER_IN', $transfer_id, 'Transfer from Cash - $remarks', $amount, $new_bank, NOW())";
                if(!mysqli_query($conn, $bank_q)) throw new Exception("Bank entry failed");
                mysqli_query($conn, "UPDATE bank_accounts SET current_balance = $new_bank WHERE id = $to_id");
            }
            // Bank to Cash
            elseif($from_type == 'bank' && $to_type == 'cash') {
                $bank_bal_q = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $from_id";
                $bank_bal_r = mysqli_query($conn, $bank_bal_q);
                $current_bank = $bank_bal_r ? floatval(mysqli_fetch_assoc($bank_bal_r)['balance']) : 0;
                $new_bank = $current_bank - $amount;

                $bank_q = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, credit, balance, created_at)
                           VALUES ('$transfer_date', $from_id, 'TRANSFER_OUT', 0, 'Transfer to Cash - $remarks', $amount, $new_bank, NOW())";
                if(!mysqli_query($conn, $bank_q)) throw new Exception("Bank entry failed");
                $transfer_id = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE bank_book SET reference_id = $transfer_id WHERE id = $transfer_id");
                mysqli_query($conn, "UPDATE bank_accounts SET current_balance = $new_bank WHERE id = $from_id");

                $new_cash = $current_cash + $amount;
                $cash_q = "INSERT INTO cash_book (date, reference_type, reference_id, description, debit, balance, created_at)
                           VALUES ('$transfer_date', 'TRANSFER', $transfer_id, 'Transfer from {$banks[$from_id]['bank_name']} - $remarks', $amount, $new_cash, NOW())";
                if(!mysqli_query($conn, $cash_q)) throw new Exception("Cash entry failed");
            }
            // Bank to Bank
            elseif($from_type == 'bank' && $to_type == 'bank') {
                $from_bal_q = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $from_id";
                $from_bal_r = mysqli_query($conn, $from_bal_q);
                $current_from = $from_bal_r ? floatval(mysqli_fetch_assoc($from_bal_r)['balance']) : 0;
                $new_from = $current_from - $amount;

                $from_q = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, credit, balance, created_at)
                           VALUES ('$transfer_date', $from_id, 'TRANSFER_OUT', 0, 'Transfer to {$banks[$to_id]['bank_name']} - $remarks', $amount, $new_from, NOW())";
                if(!mysqli_query($conn, $from_q)) throw new Exception("From bank entry failed");
                $transfer_id = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE bank_book SET reference_id = $transfer_id WHERE id = $transfer_id");
                mysqli_query($conn, "UPDATE bank_accounts SET current_balance = $new_from WHERE id = $from_id");

                $to_bal_q = "SELECT SUM(debit) - SUM(credit) as balance FROM bank_book WHERE bank_account_id = $to_id";
                $to_bal_r = mysqli_query($conn, $to_bal_q);
                $current_to = $to_bal_r ? floatval(mysqli_fetch_assoc($to_bal_r)['balance']) : 0;
                $new_to = $current_to + $amount;

                $to_q = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, balance, created_at)
                         VALUES ('$transfer_date', $to_id, 'TRANSFER_IN', $transfer_id, 'Transfer from {$banks[$from_id]['bank_name']} - $remarks', $amount, $new_to, NOW())";
                if(!mysqli_query($conn, $to_q)) throw new Exception("To bank entry failed");
                mysqli_query($conn, "UPDATE bank_accounts SET current_balance = $new_to WHERE id = $to_id");
            } else {
                throw new Exception("Invalid transfer type");
            }

            // Record in account_transfers
            $transfer_insert = "INSERT INTO account_transfers (transfer_date, from_account_type, from_account_id, to_account_type, to_account_id, amount, reference_no, remarks, created_by, created_at)
                                VALUES ('$transfer_date', '$from_type', $from_id, '$to_type', $to_id, $amount, '$reference_no', 'Transfer: $remarks', $user_id, NOW())";
            mysqli_query($conn, $transfer_insert);

            mysqli_commit($conn);
            $success = "Transfer successful! " . formatCurrency($amount) . " transferred from $from_label to $to_label.";

            // Refresh balances
            $cash_result = mysqli_query($conn, "SELECT SUM(debit) - SUM(credit) as balance FROM cash_book");
            $cash_balance = ($cash_result && $rw = mysqli_fetch_assoc($cash_result)) ? floatval($rw['balance']) : 0;

            $bank_list_result = mysqli_query($conn, "SELECT id, bank_name, account_number, current_balance FROM bank_accounts WHERE status = 1 ORDER BY bank_name ASC");
            $banks = [];
            while($b = mysqli_fetch_assoc($bank_list_result)) {
                $banks[$b['id']] = $b;
            }
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
        .topbar { height: 60px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
        .account-balance { font-size: 18px; font-weight: bold; }
        .transfer-arrow { font-size: 24px; color: #1e7e34; margin: 0 20px; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34;"><i class="fas fa-store"></i> <?php echo $software_name; ?></div>
                    <div class="user-info">
                        <span style="color: #4e73df;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)</span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none; margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
                        <h1 class="h3 mb-0" style="color: #1e7e34;"><i class="fas fa-exchange-alt"></i> Account Transfer</h1>
                        <a href="cashbook.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Cash Book</a>
                    </div>
                    <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <div class="text-center mt-3">
                        <a href="transfer.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Transfer</a>
                        <a href="cashbook.php" class="btn btn-success btn-sm"><i class="fas fa-book"></i> View Cash Book</a>
                    </div>
                    <?php endif; ?>
                    <?php if(!$success): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card form-card">
                                <div class="card-header-custom"><i class="fas fa-exchange-alt"></i> Transfer Funds</div>
                                <div class="card-body">
                                    <form method="POST" action="" id="transferForm">
                                        <div class="form-group">
                                            <label class="required-field">Transfer Date</label>
                                            <input type="date" name="transfer_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label class="required-field">From Account</label>
                                                    <select name="from_type" id="from_type" class="form-control" required>
                                                        <option value="">-- Type --</option>
                                                        <option value="cash">Cash Account</option>
                                                        <option value="bank">Bank Account</option>
                                                    </select>
                                                    <select name="from_id" id="from_id" class="form-control mt-2" style="display:none;">
                                                        <option value="">-- Select Account --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <div class="transfer-arrow"><i class="fas fa-arrow-right"></i></div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label class="required-field">To Account</label>
                                                    <select name="to_type" id="to_type" class="form-control" required>
                                                        <option value="">-- Type --</option>
                                                        <option value="cash">Cash Account</option>
                                                        <option value="bank">Bank Account</option>
                                                    </select>
                                                    <select name="to_id" id="to_id" class="form-control mt-2" style="display:none;">
                                                        <option value="">-- Select Account --</option>
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
                                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required placeholder="Enter amount to transfer">
                                        </div>
                                        <div class="form-group">
                                            <label>Reference Number</label>
                                            <input type="text" name="reference_no" class="form-control" placeholder="Transaction reference number">
                                        </div>
                                        <div class="form-group">
                                            <label>Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control" rows="3" placeholder="Purpose of transfer"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-green btn-lg btn-block"><i class="fas fa-exchange-alt"></i> Process Transfer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #4e73df; color: white;"><h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle"></i> Transfer Types</h6></div>
                                <div class="card-body">
                                    <p><i class="fas fa-exchange-alt text-primary"></i> Supported transfers:</p>
                                    <ul>
                                        <li>Cash &rarr; Bank</li>
                                        <li>Bank &rarr; Cash</li>
                                        <li>Bank &rarr; Bank</li>
                                    </ul>
                                    <hr>
                                    <p><strong>Accounting Effect:</strong></p>
                                    <ul id="accountingEffect"><li>Select accounts to see effect</li></ul>
                                    <hr>
                                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Ensure sufficient balance in the source account.</div>
                                </div>
                            </div>
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #1e7e34; color: white;"><h6 class="m-0 font-weight-bold"><i class="fas fa-history"></i> Recent Transfers</h6></div>
                                <div class="card-body p-0">
                                    <?php
                                    $recent_query = "SELECT at.*, 
                                        CASE WHEN at.from_account_type = 'bank' THEN (SELECT bank_name FROM bank_accounts WHERE id = at.from_account_id) ELSE 'Cash' END as from_name,
                                        CASE WHEN at.to_account_type = 'bank' THEN (SELECT bank_name FROM bank_accounts WHERE id = at.to_account_id) ELSE 'Cash' END as to_name
                                        FROM account_transfers at ORDER BY at.id DESC LIMIT 5";
                                    $recent_result = mysqli_query($conn, $recent_query);
                                    if(mysqli_num_rows($recent_result) > 0):
                                    ?>
                                    <div class="list-group list-group-flush">
                                        <?php while($rec = mysqli_fetch_assoc($recent_result)): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <small class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d-m-Y', strtotime($rec['transfer_date'])); ?></small>
                                                <small class="font-weight-bold"><?php echo formatCurrency($rec['amount']); ?></small>
                                            </div>
                                            <div class="mt-1">
                                                <small><?php echo htmlspecialchars($rec['from_name']); ?></small>
                                                <i class="fas fa-arrow-right text-success"></i>
                                                <small><?php echo htmlspecialchars($rec['to_name']); ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo !empty($rec['reference_no']) ? 'Ref: ' . htmlspecialchars($rec['reference_no']) : ''; ?></small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4"><i class="fas fa-exchange-alt fa-2x text-muted mb-2 d-block"></i><p class="text-muted mb-0">No recent transfers</p></div>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script>
        var banks = <?php echo json_encode(array_values($banks)); ?>;
        var cashBalance = <?php echo $cash_balance; ?>;

        function getBalance(type, id) {
            if(type === 'cash') return cashBalance;
            var bank = banks.find(function(b) { return b.id == id; });
            return bank ? parseFloat(bank.current_balance) : 0;
        }

        function getName(type, id) {
            if(type === 'cash') return 'Cash Account';
            var bank = banks.find(function(b) { return b.id == id; });
            return bank ? bank.bank_name : 'Unknown';
        }

        function populateBankDropdown(selectId, selectedType) {
            var $select = $('#' + selectId);
            $select.empty().append('<option value="">-- Select Account --</option>');
            if(selectedType === 'cash') {
                $select.append('<option value="0">Cash Account (Balance: ' + cashBalance.toFixed(2) + ')</option>');
                $select.show();
            } else if(selectedType === 'bank') {
                banks.forEach(function(b) {
                    $select.append('<option value="' + b.id + '">' + b.bank_name + ' (Balance: ' + parseFloat(b.current_balance).toFixed(2) + ')</option>');
                });
                $select.show();
            } else {
                $select.hide();
            }
        }

        function updateBalanceInfo() {
            var fromType = $('#from_type').val();
            var fromId = $('#from_id').val();
            var toType = $('#to_type').val();
            var toId = $('#to_id').val();
            var amount = parseFloat($('#amount').val());

            if(fromType && fromId) {
                var balance = (fromType === 'cash') ? cashBalance : getBalance(fromType, fromId);
                $('#balanceInfo').show();
                $('#availableBalance').html('PKR ' + balance.toFixed(2));
                $('#availableBalance').css('color', balance > 0 ? '#1e7e34' : '#dc3545');

                if(!isNaN(amount) && amount > 0) {
                    var afterBalance = balance - amount;
                    $('#afterTransferBalance').html('PKR ' + afterBalance.toFixed(2));
                    $('#afterTransferBalance').css('color', afterBalance >= 0 ? '#1e7e34' : '#dc3545');
                    if(afterBalance < 0) $('#amount').addClass('is-invalid');
                    else $('#amount').removeClass('is-invalid');
                } else {
                    $('#afterTransferBalance').html('PKR ' + balance.toFixed(2));
                }
            } else {
                $('#balanceInfo').hide();
            }
        }

        function updateAccountingEffect() {
            var fromType = $('#from_type').val();
            var fromId = $('#from_id').val();
            var toType = $('#to_type').val();
            var toId = $('#to_id').val();
            var fromName = getName(fromType, fromId);
            var toName = getName(toType, toId);
            var html = '';

            if(fromType && toType && fromId && toId && !(fromType === toType && fromId == toId)) {
                if(fromType === 'cash' && toType === 'bank') {
                    html = '<li>Cash Book: <span class="text-danger">CREDIT (-)</span></li><li>' + toName + ': <span class="text-success">DEBIT (+)</span></li>';
                } else if(fromType === 'bank' && toType === 'cash') {
                    html = '<li>' + fromName + ': <span class="text-danger">CREDIT (-)</span></li><li>Cash Book: <span class="text-success">DEBIT (+)</span></li>';
                } else if(fromType === 'bank' && toType === 'bank') {
                    html = '<li>' + fromName + ': <span class="text-danger">CREDIT (-)</span></li><li>' + toName + ': <span class="text-success">DEBIT (+)</span></li>';
                }
            } else {
                html = '<li>Select accounts to see effect</li>';
            }
            $('#accountingEffect').html(html);
        }

        $(document).ready(function() {
            $('#from_type').change(function() {
                populateBankDropdown('from_id', $(this).val());
                updateBalanceInfo();
                updateAccountingEffect();
            });
            $('#to_type').change(function() {
                populateBankDropdown('to_id', $(this).val());
                updateAccountingEffect();
            });
            $('#from_id, #to_id').change(function() {
                updateBalanceInfo();
                updateAccountingEffect();
            });
            $('#amount').on('keyup change', function() { updateBalanceInfo(); });

            $('#transferForm').on('submit', function(e) {
                var fromType = $('#from_type').val();
                var fromId = $('#from_id').val();
                var toType = $('#to_type').val();
                var toId = $('#to_id').val();
                var amount = parseFloat($('#amount').val());
                var balance = (fromType === 'cash') ? cashBalance : getBalance(fromType, fromId);
                var fromName = getName(fromType, fromId);
                var toName = getName(toType, toId);

                if(!fromType || !toType || !fromId || !toId) {
                    e.preventDefault();
                    Swal.fire({ title: 'Error!', text: 'Please select both From and To accounts', icon: 'error', confirmButtonColor: '#1e7e34' });
                    return false;
                }
                if(fromType === toType && fromId == toId) {
                    e.preventDefault();
                    Swal.fire({ title: 'Error!', text: 'From and To accounts cannot be the same', icon: 'error', confirmButtonColor: '#1e7e34' });
                    return false;
                }
                if(isNaN(amount) || amount <= 0) {
                    e.preventDefault();
                    Swal.fire({ title: 'Error!', text: 'Please enter a valid amount', icon: 'error', confirmButtonColor: '#1e7e34' });
                    return false;
                }
                if(amount > balance) {
                    e.preventDefault();
                    Swal.fire({ title: 'Insufficient Balance!', text: 'Available balance in ' + fromName + ' is PKR ' + balance.toFixed(2), icon: 'error', confirmButtonColor: '#1e7e34' });
                    return false;
                }

                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Transfer',
                    html: 'Transfer <strong>PKR ' + amount.toFixed(2) + '</strong> from <strong>' + fromName + '</strong> to <strong>' + toName + '</strong>?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e7e34',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, transfer!'
                }).then(function(result) {
                    if(result.isConfirmed) {
                        // Set hidden inputs for submission
                        $('<input>').attr({type: 'hidden', name: 'from_id', value: fromId}).appendTo('#transferForm');
                        $('<input>').attr({type: 'hidden', name: 'to_id', value: toId}).appendTo('#transferForm');
                        $('#transferForm').off('submit').submit();
                    }
                });
                return false;
            });
        });
    </script>
</body>
</html>
