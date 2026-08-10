<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
$error = '';
$success = '';

// Get bank accounts
$bank_query = "SELECT * FROM bank_accounts WHERE status = 1 ORDER BY bank_name";
$bank_result = mysqli_query($conn, $bank_query);

// Get all customers
$all_customers_query = "SELECT id, customer_code, customer_name, current_balance FROM customers WHERE status = 1 ORDER BY customer_name";
$all_customers_result = mysqli_query($conn, $all_customers_query);

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_receipt'])) {
    $receipt_date = mysqli_real_escape_string($conn, $_POST['receipt_date']);
    $customer_id = intval($_POST['customer_id']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account_id = ($payment_method == 'bank' && isset($_POST['bank_account_id'])) ? intval($_POST['bank_account_id']) : NULL;
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
    $amount = floatval($_POST['amount']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));

    if($amount <= 0) {
        $error = "Please enter a valid amount greater than 0";
    } elseif($payment_method == 'bank' && empty($bank_account_id)) {
        $error = "Please select a bank account";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $bal_query = "SELECT current_balance FROM customers WHERE id = $customer_id";
            $bal_result = mysqli_query($conn, $bal_query);
            $current_balance = floatval(mysqli_fetch_assoc($bal_result)['current_balance']);

            $insert_receipt = "INSERT INTO customer_receipts (receipt_date, customer_id, payment_method, bank_account_id, reference_no, amount, remarks, created_by, created_at) 
                               VALUES ('$receipt_date', $customer_id, '$payment_method', " . ($bank_account_id ? $bank_account_id : "NULL") . ", '$reference_no', $amount, '$remarks', '$user_id', NOW())";
            if(!mysqli_query($conn, $insert_receipt)) {
                throw new Exception("Failed to save receipt: " . mysqli_error($conn));
            }
            $receipt_id = mysqli_insert_id($conn);

            $new_balance = $current_balance - $amount;
            $ledger_query = "INSERT INTO customer_ledger (date, customer_id, reference_type, reference_id, description, credit, balance, created_at) 
                             VALUES ('$receipt_date', $customer_id, 'PAYMENT', $receipt_id, 'Payment received - $remarks', $amount, $new_balance, NOW())";
            if(!mysqli_query($conn, $ledger_query)) {
                throw new Exception("Failed to update ledger: " . mysqli_error($conn));
            }

            $update_customer = "UPDATE customers SET current_balance = $new_balance WHERE id = $customer_id";
            if(!mysqli_query($conn, $update_customer)) {
                throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
            }

            if($payment_method == 'cash') {
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
                $update_bank = "UPDATE bank_accounts SET current_balance = current_balance + $amount WHERE id = $bank_account_id";
                mysqli_query($conn, $update_bank);
            }

            mysqli_commit($conn);
            header("Location: receiving_amount.php?receipt_id=$receipt_id&amount=" . urlencode(number_format($amount, 2)));
            exit();
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Show receipt if receipt_id is provided
if(isset($_GET['receipt_id'])) {
    $receipt_id = intval($_GET['receipt_id']);
    $receipt_query = "SELECT cr.*, c.customer_name, c.customer_code, c.mobile, c.address 
                      FROM customer_receipts cr 
                      JOIN customers c ON cr.customer_id = c.id 
                      WHERE cr.id = $receipt_id";
    $receipt_result = mysqli_query($conn, $receipt_query);
    if(!$receipt_result || mysqli_num_rows($receipt_result) == 0) {
        echo "<script>window.location.href='receiving_amount.php';</script>";
        exit();
    }
    $receipt = mysqli_fetch_assoc($receipt_result);
    $display_amount = isset($_GET['amount']) ? $_GET['amount'] : number_format($receipt['amount'], 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Faisal Glass - Receipt</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #e6e9ef; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; font-size: 16px; font-family: 'Poppins', sans-serif; padding: 20px; }
        .toolbar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; justify-content: center; }
        .toolbar button { padding: 10px 28px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.2s; background: #1a2a3a; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .toolbar button:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.2); }
        .toolbar button.pdf-btn { background: #c0392b; }
        .toolbar button.print-btn { background: #2c6e9c; }
        .toolbar button.back-btn { background: #1e7e34; }
        #receipt-wrapper { background: #ffffff; box-shadow: 0 12px 40px rgba(0,0,0,0.18); border-radius: 4px; padding: 12px; }
        #receipt { width: 148mm; min-height: 210mm; background: #ffffff; padding: 10mm 8mm; font-size: 10.5px; line-height: 1.5; color: #1e1e1e; display: flex; flex-direction: column; position: relative; border: 1px solid #d0d4dc; border-radius: 2px; }
        .brand-name { font-size: 28px; font-weight: 800; letter-spacing: 1.5px; color: #0b1e2e; text-transform: uppercase; line-height: 1.1; }
        .brand-sub { font-size: 13px; font-weight: 600; color: #2c3e50; letter-spacing: 0.5px; margin-top: 1px; }
        .brand-contact { font-size: 10.5px; color: #34495e; margin-top: 2px; }
        .brand-address { font-size: 10px; color: #4a5a6a; margin-top: 1px; }
        .section-title { font-size: 14px; font-weight: 700; color: #0b1e2e; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #0b1e2e; padding-bottom: 3px; margin: 12px 0 8px 0; }
        .section-title:first-of-type { margin-top: 8px; }
        .field-label { font-weight: 600; color: #1e2f3f; display: inline-block; min-width: 120px; }
        .field-value { font-weight: 400; color: #1e1e1e; }
        .field-row { margin: 3px 0; display: flex; align-items: baseline; flex-wrap: wrap; }
        .field-row .field-label { min-width: 120px; }
        .method-group { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 2px; align-items: center; }
        .method-item { display: flex; align-items: center; gap: 4px; font-size: 10.5px; color: #1e2f3f; }
        .method-item input[type="checkbox"] { width: 14px; height: 14px; accent-color: #0b1e2e; cursor: default; pointer-events: none; margin: 0; }
        .received-by { margin-top: 10px; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 6px; border-top: 2px solid #0b1e2e; }
        .received-left .company-name { font-size: 14px; font-weight: 700; color: #0b1e2e; }
        .received-left .company-sub { font-size: 10px; color: #2c3e50; }
        .signature-area { text-align: center; min-width: 100px; }
        .signature-area .sig-label { font-size: 10px; color: #4a5a6a; border-top: 1px solid #4a5a6a; padding-top: 2px; min-width: 100px; }
        .signature-area .sig-company { font-size: 10px; font-weight: 600; color: #0b1e2e; margin-top: 2px; }
        .footer { margin-top: auto; padding-top: 10px; text-align: center; border-top: 1px solid #d0d4dc; }
        .footer .thankyou { font-size: 13px; font-weight: 700; color: #0b1e2e; letter-spacing: 0.5px; }
        .footer .generated { font-size: 9px; color: #6a7a8a; margin-top: 2px; font-style: italic; }
        @media print { body { background: #fff; padding: 0; margin: 0; display: block; } .toolbar { display: none !important; } #receipt-wrapper { box-shadow: none; border-radius: 0; padding: 0; margin: 0; } #receipt { width: 148mm; min-height: 210mm; padding: 10mm 8mm; border: none; border-radius: 0; box-shadow: none; margin: 0 auto; } .method-item input[type="checkbox"] { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .brand-name, .section-title, .received-left .company-name, .footer .thankyou { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="back-btn" onclick="window.location.href='receiving_amount.php'">← Back to Payments</button>
        <button class="print-btn" onclick="window.print()">🖨️ Print</button>
        <button class="pdf-btn" onclick="exportPDF()">📄 Download PDF</button>
    </div>

    <div id="receipt-wrapper">
        <div id="receipt">
            <div>
                <div class="brand-name">FAISAL GLASS</div>
                <div class="brand-sub">DEALERS OF GHANI GLASS LIMITED</div>
                <div class="brand-contact">📞 0321-4186775</div>
                <div class="brand-address">📍 Lajna Chowk Collage Road Township Lahore</div>
            </div>

            <div class="section-title">RECEIVED FROM</div>
            <div class="field-row">
                <span class="field-label">Customer Name</span>
                <span class="field-value"><strong><?php echo htmlspecialchars($receipt['customer_name']); ?></strong></span>
            </div>
            <div class="field-row">
                <span class="field-label">Customer Code</span>
                <span class="field-value"><?php echo htmlspecialchars($receipt['customer_code']); ?></span>
            </div>

            <div class="section-title">PAYMENT DETAILS</div>
            <div class="field-row">
                <span class="field-label">Receipt Date</span>
                <span class="field-value"><?php echo date('d-m-Y', strtotime($receipt['receipt_date'])); ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Receipt #</span>
                <span class="field-value">RCP-<?php echo str_pad($receipt['id'], 4, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Payment Method</span>
                <span class="field-value" style="font-weight:600;text-transform:uppercase;"><?php echo htmlspecialchars($receipt['payment_method']); ?></span>
            </div>
            <?php if(!empty($receipt['reference_no'])): ?>
            <div class="field-row">
                <span class="field-label">Reference No.</span>
                <span class="field-value"><?php echo htmlspecialchars($receipt['reference_no']); ?></span>
            </div>
            <?php endif; ?>
            <div class="field-row">
                <span class="field-label">Amount Received</span>
                <span class="field-value" style="font-size:15px; font-weight:700; color:#0b1e2e;">Rs. <?php echo $display_amount; ?></span>
            </div>
            <?php if(!empty($receipt['remarks'])): ?>
            <div class="field-row">
                <span class="field-label">Remarks</span>
                <span class="field-value" style="color:#3a4a5a;"><?php echo htmlspecialchars($receipt['remarks']); ?></span>
            </div>
            <?php endif; ?>

            <div class="received-by">
                <div class="received-left">
                    <div class="company-name">FAISAL GLASS</div>
                    <div class="company-sub">DEALERS OF GHANI GLASS LIMITED</div>
                </div>
                <div class="signature-area">
                    <div class="sig-label">Authorized Signature</div>
                    <div class="sig-company">GHANI GLASS LIMITED</div>
                </div>
            </div>

            <div class="footer">
                <div class="thankyou">Thank You for Your Business!</div>
                <div class="generated">This is a computer generated receipt and does not require a physical signature.</div>
            </div>
        </div>
    </div>

    <script>
    function exportPDF() {
        const receipt = document.getElementById('receipt');
        const btn = document.querySelector('.pdf-btn');
        const originalText = btn.textContent;
        btn.textContent = '⏳ Generating…';
        btn.disabled = true;

        if(typeof window.jspdf === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = function() { doExport(); };
            script.onerror = function() { alert('Failed to load PDF library.'); btn.textContent = originalText; btn.disabled = false; };
            document.head.appendChild(script);
        } else { doExport(); }

        function doExport() {
            const { jsPDF } = window.jspdf;
            const scale = 2.5;
            const rect = receipt.getBoundingClientRect();
            const width = rect.width;
            const height = rect.height;

            html2canvas(receipt, {
                scale: scale, useCORS: true, allowTaint: false, backgroundColor: '#ffffff',
                logging: false, width: width, height: height,
            }).then((canvas) => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });
                const cAspect = canvas.width / canvas.height;
                const pAspect = 148 / 210;
                let fw = 148, fh = 210;
                if(cAspect > pAspect) fh = 148 / cAspect; else fw = 210 * cAspect;
                pdf.addImage(imgData, 'PNG', (148 - fw) / 2, (210 - fh) / 2, fw, fh);
                pdf.save('Faisal_Glass_Receipt_RCP-' + <?php echo json_encode(str_pad($receipt['id'], 4, '0', STR_PAD_LEFT)); ?> + '.pdf');
                btn.textContent = originalText; btn.disabled = false;
            }).catch(() => { alert('Could not generate PDF.'); btn.textContent = originalText; btn.disabled = false; });
        }
    }
    (function preloadJSPDF() {
        if(typeof window.jspdf === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            s.async = true; document.head.appendChild(s);
        }
    })();
    </script>
</body>
</html>
<?php
    mysqli_close($conn);
    exit();
}

// Default: Show payment form
$page_title = "Receive Payment";
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
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .required-field::after { content: " *"; color: red; }
        .customer-info { background: #f8f9fc; border-left: 4px solid #1e7e34; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .balance-amount { font-size: 24px; font-weight: bold; color: #1e7e34; }
        .topbar { height: 60px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar">
                    <div class="welcome-text" style="color: #1e7e34;"><i class="fas fa-store"></i> <?php echo $software_name; ?></div>
                    <div class="user-info">
                        <span style="color: #4e73df;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                        <h1 class="h3 mb-0" style="color: #1e7e34;"><i class="fas fa-money-bill-wave"></i> Receive Payment from Customer</h1>
                        <a href="view_customer.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Customers</a>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card form-card">
                                <div class="card-header-custom"><i class="fas fa-hand-holding-usd"></i> Payment Receipt Form</div>
                                <div class="card-body">
                                    <form method="POST" action="" id="receiptForm">
                                        <div class="form-group">
                                            <label class="required-field">Receipt Date</label>
                                            <input type="date" name="receipt_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
                                                        data-balance="<?php echo $c['current_balance']; ?>"
                                                        data-name="<?php echo htmlspecialchars($c['customer_name']); ?>"
                                                        data-code="<?php echo htmlspecialchars($c['customer_code']); ?>">
                                                        <?php echo htmlspecialchars($c['customer_code'] . ' - ' . $c['customer_name']); ?>
                                                        (Balance: <?php echo number_format(abs($c['current_balance']), 2); ?> <?php echo $c['current_balance'] >= 0 ? 'DR' : 'CR'; ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="customer-info" id="customerInfo" style="display: none;">
                                            <div class="row">
                                                <div class="col-md-6"><small class="text-muted">Customer Code</small><div><strong id="displayCode"></strong></div></div>
                                                <div class="col-md-6"><small class="text-muted">Customer Name</small><div><strong id="displayName"></strong></div></div>
                                                <div class="col-md-12 mt-2"><small class="text-muted">Current Balance</small><div class="balance-amount" id="displayBalance"></div></div>
                                            </div>
                                        </div>

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
                                            <input type="text" name="reference_no" class="form-control" placeholder="Cheque number, transaction ID, or reference">
                                        </div>

                                        <div class="form-group">
                                            <label class="required-field">Amount (PKR)</label>
                                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required placeholder="Enter amount">
                                        </div>

                                        <div class="form-group">
                                            <label>Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control" rows="3" placeholder="Any additional notes about this payment"></textarea>
                                        </div>

                                        <div class="form-group">
                                            <input type="hidden" name="save_receipt" value="1">
                                            <button type="submit" class="btn btn-green btn-lg btn-block"><i class="fas fa-save"></i> Receive Payment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #4e73df; color: white;"><h6 class="m-0 font-weight-bold">Payment Instructions</h6></div>
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
                                    <div class="alert alert-info"><i class="fas fa-lightbulb"></i> <strong>Note:</strong><br>After receiving payment, the customer's receivable balance will decrease.</div>
                                </div>
                            </div>
                            <div class="card shadow mb-4">
                                <div class="card-header" style="background: #1e7e34; color: white;"><h6 class="m-0 font-weight-bold">Recent Payments</h6></div>
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
                                        <a href="receiving_amount.php?receipt_id=<?php echo $rec['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <small class="text-muted"><?php echo date('d-m-Y', strtotime($rec['receipt_date'])); ?></small>
                                                <small class="text-success">₨ <?php echo number_format($rec['amount'], 2); ?></small>
                                            </div>
                                            <div class="mt-1"><strong><?php echo htmlspecialchars($rec['customer_name']); ?></strong></div>
                                            <small class="text-muted"><?php echo ucfirst($rec['payment_method']); ?><?php echo !empty($rec['reference_no']) ? ' - Ref: ' . htmlspecialchars($rec['reference_no']) : ''; ?></small>
                                        </a>
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
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#payment_method').change(function() {
            if($(this).val() == 'bank') { $('#bank_account_div').show(); $('select[name="bank_account_id"]').prop('required', true); }
            else { $('#bank_account_div').hide(); $('select[name="bank_account_id"]').prop('required', false); }
        });

        $('#customer_id').change(function() {
            var opt = $(this).find('option:selected');
            var balance = opt.data('balance');
            var name = opt.data('name');
            var code = opt.data('code');
            var id = $(this).val();
            if(id) {
                $('#customerInfo').show();
                $('#displayCode').text(code);
                $('#displayName').text(name);
                var b = parseFloat(balance);
                var txt = '₨ ' + Math.abs(b).toFixed(2) + (b >= 0 ? ' DR (Receivable)' : ' CR (Payable)');
                $('#displayBalance').html(txt).css('color', b >= 0 ? '#28a745' : '#dc3545');
            } else { $('#customerInfo').hide(); }
        });

        $('#receiptForm').on('submit', function(e) {
            e.preventDefault();
            var amount = parseFloat($('#amount').val());
            var customerId = $('#customer_id').val();
            var paymentMethod = $('#payment_method').val();

            if(!customerId) return Swal.fire({ title: 'Error!', text: 'Please select a customer', icon: 'error', confirmButtonColor: '#1e7e34' }), false;
            if(!amount || amount <= 0) return Swal.fire({ title: 'Error!', text: 'Please enter a valid amount greater than 0', icon: 'error', confirmButtonColor: '#1e7e34' }), false;
            if(!paymentMethod) return Swal.fire({ title: 'Error!', text: 'Please select a payment method', icon: 'error', confirmButtonColor: '#1e7e34' }), false;
            if(paymentMethod == 'bank' && !$('select[name="bank_account_id"]').val()) return Swal.fire({ title: 'Error!', text: 'Please select a bank account', icon: 'error', confirmButtonColor: '#1e7e34' }), false;

            Swal.fire({
                title: 'Confirm Payment',
                text: 'Are you sure you want to receive ₨ ' + amount.toFixed(2) + ' from this customer?',
                icon: 'question', showCancelButton: true, confirmButtonColor: '#1e7e34',
                cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, receive payment!'
            }).then((result) => {
                if(result.isConfirmed) {
                    $('#receiptForm').off('submit').submit();
                }
            });
        });
    });
    </script>
</body>
</html>
<?php
?>
