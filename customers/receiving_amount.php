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
    $sale_invoice_no = mysqli_real_escape_string($conn, trim($_POST['sale_invoice_no'] ?? ''));
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

            $insert_receipt = "INSERT INTO customer_receipts (receipt_date, customer_id, payment_method, bank_account_id, reference_no, sale_invoice_no, amount, remarks, created_by, created_at) 
                               VALUES ('$receipt_date', $customer_id, '$payment_method', " . ($bank_account_id ? $bank_account_id : "NULL") . ", '$reference_no', '$sale_invoice_no', $amount, '$remarks', '$user_id', NOW())";
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
    <title>Receipt - RCP-<?php echo str_pad($receipt['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #dde3ea; font-family: 'Poppins', sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; min-height: 100vh; padding: 28px 16px 80px; color: #1e1e1e; }
        .toolbar { display: flex; gap: 12px; margin-bottom: 22px; flex-wrap: wrap; justify-content: center; }
        .toolbar button { padding: 9px 26px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .btn-back  { background: #1e7e34; color: #fff; }
        .btn-print { background: #2c6e9c; color: #fff; }
        .btn-pdf   { background: #c0392b; color: #fff; }

        /* ===== RECEIPT CARD ===== */
        .receipt-card {
            width: 420px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            overflow: hidden;
        }

        /* Top color band */
        .top-band {
            background: #0b1e2e;
            color: #fff;
            padding: 16px 20px 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .top-band .company-name { font-size: 18px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; line-height: 1.2; }
        .top-band .company-sub  { font-size: 9.5px; opacity: 0.8; margin-top: 2px; }
        .top-band .company-info { font-size: 9px; opacity: 0.75; margin-top: 4px; line-height: 1.6; }
        .receipt-no-box { text-align: center; background: rgba(255,255,255,0.12); padding: 7px 12px; border-radius: 4px; white-space: nowrap; }
        .receipt-no-box .rno-label { font-size: 8.5px; letter-spacing: 1.5px; opacity: 0.8; text-transform: uppercase; }
        .receipt-no-box .rno-val   { font-size: 15px; font-weight: 800; }

        /* Title ribbon */
        .title-ribbon {
            background: #f0f4f8;
            text-align: center;
            padding: 8px 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 5px;
            color: #0b1e2e;
            text-transform: uppercase;
            border-bottom: 1.5px dashed #c0c8d8;
        }

        /* Body */
        .receipt-body { padding: 14px 20px; }

        .section-head { font-size: 9px; font-weight: 700; letter-spacing: 2px; color: #8a9ab0; text-transform: uppercase; margin: 12px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #e8eef4; }

        .detail-row { display: flex; justify-content: space-between; align-items: baseline; padding: 4px 0; font-size: 11px; border-bottom: 1px dotted #e2e8f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-lbl { color: #6b7a8d; font-weight: 500; }
        .detail-val { color: #1a2535; font-weight: 600; text-align: right; }

        /* Amount box */
        .amount-box {
            background: #0b1e2e;
            color: #fff;
            margin: 14px 0 4px;
            padding: 12px 16px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .amount-box .am-label { font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; opacity: 0.8; }
        .amount-box .am-value { font-size: 22px; font-weight: 800; }

        /* Payment method checkboxes */
        .method-row { display: flex; gap: 18px; padding: 8px 0; font-size: 10.5px; }
        .method-item { display: flex; align-items: center; gap: 5px; color: #3a4a5a; }
        .method-item .chk-box { width: 14px; height: 14px; border: 2px solid #0b1e2e; border-radius: 2px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .method-item .chk-box.checked { background: #0b1e2e; color: #fff; font-size: 9px; }

        /* Signature row */
        .sig-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1.5px solid #0b1e2e;
        }
        .sig-left .co-name { font-size: 12px; font-weight: 700; color: #0b1e2e; }
        .sig-left .co-sub  { font-size: 8.5px; color: #5a6a7a; }
        .sig-right { text-align: center; }
        .sig-right .sig-line { border-bottom: 1px solid #6b7a8d; height: 26px; min-width: 100px; }
        .sig-right .sig-label { font-size: 9px; color: #6b7a8d; margin-top: 2px; }

        /* Footer strip */
        .receipt-footer {
            background: #f7f9fb;
            text-align: center;
            padding: 10px 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #6b7a8d;
        }
        .receipt-footer .thankyou { font-size: 12px; font-weight: 700; color: #0b1e2e; }

        /* Bottom row: stamp + signature */
        .bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1.5px solid #0b1e2e;
        }
        .stamp-area {
            width: 90px;
            height: 70px;
            /* empty — stamp lagegi yahan */
        }
        .stamp-label { font-size: 9px; color: #6b7a8d; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; text-align: center; }
        .sig-area { text-align: center; }
        .sig-line { border-bottom: 1px solid #374151; height: 30px; min-width: 110px; margin-bottom: 4px; }
        .sig-label { font-size: 9px; color: #6b7a8d; text-transform: uppercase; letter-spacing: 0.5px; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; display: block; }
            .toolbar { display: none !important; }
            .receipt-card { width: 100%; box-shadow: none; border-radius: 0; }
            @page { size: 80mm 200mm; margin: 4mm; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button class="btn-back"  onclick="window.location.href='receiving_amount.php'"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <button class="btn-pdf"   onclick="exportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
</div>

<div class="receipt-card" id="receipt">

    <!-- Top band -->
    <div class="top-band">
        <div>
            <div class="company-name">FAISAL GLASS</div>
            <div class="company-sub">DEALERS OF GHANI GLASS LIMITED</div>
            <div class="company-info">
                📞 0321-4186775 &nbsp;|&nbsp; 0322-8701098<br>
                📍 Lajna Chowk Collage Road Township Lahore
            </div>
        </div>
        <div class="receipt-no-box">
            <div class="rno-label">Receipt No.</div>
            <div class="rno-val">RCP-<?php echo str_pad($receipt['id'], 4, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-ribbon">PAYMENT RECEIPT</div>

    <!-- Body -->
    <div class="receipt-body">

        <!-- Received From -->
        <div class="section-head">Received From</div>
        <div class="detail-row">
            <span class="detail-lbl">Customer Name</span>
            <span class="detail-val"><?php echo htmlspecialchars($receipt['customer_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Customer Code</span>
            <span class="detail-val"><?php echo htmlspecialchars($receipt['customer_code']); ?></span>
        </div>
        <?php if(!empty($receipt['mobile'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Mobile</span>
            <span class="detail-val"><?php echo htmlspecialchars($receipt['mobile']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Payment Details -->
        <div class="section-head">Payment Details</div>
        <div class="detail-row">
            <span class="detail-lbl">Date</span>
            <span class="detail-val"><?php echo date('d-m-Y', strtotime($receipt['receipt_date'])); ?></span>
        </div>

        <?php if(!empty($receipt['reference_no'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Reference / Cheque No.</span>
            <span class="detail-val"><?php echo htmlspecialchars($receipt['reference_no']); ?></span>
        </div>
        <?php endif; ?>

        <?php if(!empty($receipt['sale_invoice_no'])): ?>
        <div class="detail-row" style="background:#fffbea;border-radius:3px;padding:5px 4px;">
            <span class="detail-lbl" style="color:#92400e;font-weight:700;">Against Invoice</span>
            <span class="detail-val" style="color:#92400e;font-weight:700;"><?php echo htmlspecialchars($receipt['sale_invoice_no']); ?></span>
        </div>
        <?php endif; ?>

        <?php if(!empty($receipt['remarks'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Remarks</span>
            <span class="detail-val" style="color:#4a5a6a;"><?php echo htmlspecialchars($receipt['remarks']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Amount -->
        <div class="amount-box">
            <div class="am-label">Amount Received</div>
            <div class="am-value">Rs. <?php echo $display_amount; ?></div>
        </div>

        <!-- Payment method -->
        <div class="method-row">
            <div class="method-item">
                <div class="chk-box <?php echo $receipt['payment_method']=='cash' ? 'checked' : ''; ?>"><?php echo $receipt['payment_method']=='cash' ? '✓' : ''; ?></div>
                Cash
            </div>
            <div class="method-item">
                <div class="chk-box <?php echo $receipt['payment_method']=='bank' ? 'checked' : ''; ?>"><?php echo $receipt['payment_method']=='bank' ? '✓' : ''; ?></div>
                Bank Transfer / Cheque
            </div>
        </div>

        <!-- Stamp + Signature -->
        <div class="bottom-row">
            <div>
                <div class="stamp-area"></div>
                <div class="stamp-label">Stamp</div>
            </div>
            <div class="sig-area">
                <div class="sig-line"></div>
                <div class="sig-label">Signature</div>
            </div>
        </div>

    </div><!-- end receipt-body -->

    <!-- Footer -->
    <div class="receipt-footer">
        <div class="thankyou">Thank You for Your Business!</div>
        <div style="margin-top:3px;">Computer generated receipt &bull; <?php echo date('d-m-Y h:i A'); ?></div>
    </div>

</div><!-- end receipt-card -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function exportPDF() {
    const receipt = document.getElementById('receipt');
    const btn = document.querySelector('.btn-pdf');
    const orig = btn.innerHTML;
    btn.innerHTML = '⏳ Generating...'; btn.disabled = true;

    if (typeof window.jspdf === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        s.onload = doExport; s.onerror = () => { alert('PDF library failed to load.'); btn.innerHTML = orig; btn.disabled = false; };
        document.head.appendChild(s);
    } else { doExport(); }

    function doExport() {
        const { jsPDF } = window.jspdf;
        html2canvas(receipt, { scale: 2.5, backgroundColor: '#ffffff', logging: false }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: [80, 200] });
            const w = 80, h = (canvas.height / canvas.width) * 80;
            pdf.addImage(imgData, 'PNG', 0, 0, w, h);
            pdf.save('Faisal_Glass_RCP-<?php echo str_pad($receipt['id'], 4, '0', STR_PAD_LEFT); ?>.pdf');
            btn.innerHTML = orig; btn.disabled = false;
        }).catch(() => { alert('PDF generation failed.'); btn.innerHTML = orig; btn.disabled = false; });
    }
}
(function(){ const s = document.createElement('script'); s.src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'; s.async=true; document.head.appendChild(s); })();
</script>
</body>
</html>
<?php
    mysqli_close($conn);
    exit();
}

// Default: Show payment form + history
$page_title = "Receive Payment";

// Payment history filters
$hist_from   = isset($_GET['from_date']) ? mysqli_real_escape_string($conn, $_GET['from_date']) : date('Y-m-01');
$hist_to     = isset($_GET['to_date'])   ? mysqli_real_escape_string($conn, $_GET['to_date'])   : date('Y-m-d');
$hist_cust   = isset($_GET['hist_customer_id']) ? intval($_GET['hist_customer_id']) : 0;

$hist_query = "SELECT cr.*, c.customer_name, c.customer_code
               FROM customer_receipts cr
               JOIN customers c ON cr.customer_id = c.id
               WHERE DATE(cr.receipt_date) BETWEEN '$hist_from' AND '$hist_to'";
if ($hist_cust > 0) {
    $hist_query .= " AND cr.customer_id = $hist_cust";
}
$hist_query .= " ORDER BY cr.receipt_date DESC, cr.id DESC";
$hist_result = mysqli_query($conn, $hist_query);

$hist_summary_q = "SELECT SUM(amount) as total,
                   SUM(CASE WHEN payment_method='cash' THEN amount ELSE 0 END) as cash_total,
                   SUM(CASE WHEN payment_method='bank' THEN amount ELSE 0 END) as bank_total,
                   COUNT(*) as total_count
                   FROM customer_receipts cr
                   WHERE DATE(cr.receipt_date) BETWEEN '$hist_from' AND '$hist_to'"
                 . ($hist_cust > 0 ? " AND cr.customer_id = $hist_cust" : "");
$hist_sum_res  = mysqli_query($conn, $hist_summary_q);
$hist_summary  = mysqli_fetch_assoc($hist_sum_res);

// Re-fetch customer list for history filter dropdown
$hist_customers_q = "SELECT id, customer_code, customer_name FROM customers WHERE status=1 ORDER BY customer_name";
$hist_customers_r = mysqli_query($conn, $hist_customers_q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                                            <label><i class="fas fa-file-invoice text-success mr-1"></i> Invoice Number (Against which payment is made)</label>
                                            <input type="text" name="sale_invoice_no" id="sale_invoice_no" class="form-control" placeholder="e.g. SAL-0001 (optional)">
                                            <small class="text-muted">Enter the sale invoice number this payment is against (if applicable)</small>
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

                <!-- ===== Payment History Section ===== -->
                <div class="mt-5">
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h4 mb-0" style="color:#1e7e34;"><i class="fas fa-history"></i> Payment Received History</h1>
                        <button class="btn btn-info btn-sm" onclick="window.open('print_customer_receipts.php?from_date=<?php echo urlencode($hist_from); ?>&to_date=<?php echo urlencode($hist_to); ?>&customer_id=<?php echo $hist_cust; ?>','_blank','width=1000,height=750')">
                            <i class="fas fa-print"></i> Print History
                        </button>
                    </div>

                    <!-- History Filter -->
                    <div class="card form-card">
                        <div class="card-header-custom"><i class="fas fa-filter mr-2"></i> Filter Payment History</div>
                        <div class="card-body">
                            <form method="GET" action="" id="histFilterForm">
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
                                            <label>Customer</label>
                                            <select name="hist_customer_id" class="form-control">
                                                <option value="0">All Customers</option>
                                                <?php while($hc = mysqli_fetch_assoc($hist_customers_r)): ?>
                                                <option value="<?php echo $hc['id']; ?>" <?php echo ($hist_cust == $hc['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($hc['customer_name'] . ' (' . $hc['customer_code'] . ')'); ?>
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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Received</div>
                                <div style="font-size:22px;font-weight:bold;color:#1e7e34;"><?php echo formatCurrency(floatval($hist_summary['total'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow text-center p-3">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cash Received</div>
                                <div style="font-size:22px;font-weight:bold;color:#28a745;"><?php echo formatCurrency(floatval($hist_summary['cash_total'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow text-center p-3">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bank Received</div>
                                <div style="font-size:22px;font-weight:bold;color:#0066cc;"><?php echo formatCurrency(floatval($hist_summary['bank_total'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow text-center p-3">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Transactions</div>
                                <div style="font-size:22px;font-weight:bold;color:#e67e22;"><?php echo intval($hist_summary['total_count']); ?></div>
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
                                            <th>Receipt #</th>
                                            <th>Date</th>
                                            <th>Customer Code</th>
                                            <th>Customer Name</th>
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
                                        $hist_total = 0;
                                        if ($hist_result && mysqli_num_rows($hist_result) > 0):
                                            while ($hr = mysqli_fetch_assoc($hist_result)):
                                                $hist_total += floatval($hr['amount']);
                                        ?>
                                        <tr>
                                            <td><strong class="text-primary">RCP-<?php echo str_pad($hr['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                            <td><?php echo date('d-m-Y', strtotime($hr['receipt_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($hr['customer_code']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($hr['customer_name']); ?></strong></td>
                                            <td>
                                                <?php if ($hr['payment_method'] == 'cash'): ?>
                                                    <span class="badge badge-success"><i class="fas fa-money-bill-wave"></i> Cash</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info"><i class="fas fa-university"></i> Bank</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($hr['reference_no']) ?: '-'; ?></td>
                                            <td>
                                                <?php if(!empty($hr['sale_invoice_no'])): ?>
                                                    <span class="font-weight-bold text-warning"><?php echo htmlspecialchars($hr['sale_invoice_no']); ?></span>
                                                <?php else: ?>-<?php endif; ?>
                                            </td>
                                            <td class="text-right text-success font-weight-bold"><?php echo formatCurrency(floatval($hr['amount'])); ?></td>
                                            <td><?php echo htmlspecialchars($hr['remarks']) ?: '-'; ?></td>
                                            <td class="text-center">
                                                <a href="receiving_amount.php?receipt_id=<?php echo $hr['id']; ?>" class="btn btn-sm btn-primary" title="View & Print Receipt">
                                                    <i class="fas fa-print"></i> Print
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr><td colspan="10" class="text-center py-4 text-muted">No payment records found for the selected period</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if ($hist_result && mysqli_num_rows($hist_result) > 0): ?>
                                    <tfoot>
                                        <tr style="background:#f8f9fc;font-weight:bold;">
                                            <td colspan="7" class="text-right"><strong>Total:</strong></td>
                                            <td class="text-right text-success"><strong><?php echo formatCurrency($hist_total); ?></strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ===== End Payment History ===== -->

            </div><!-- end container-fluid -->
            </div><!-- end #content -->
            <?php include('../includes/footer.php'); ?>
        </div><!-- end content-wrapper -->
    </div><!-- end wrapper -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

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
                if(result.isConfirmed) { $('#receiptForm').off('submit').submit(); }
            });
        });
    });
    </script>
</body>
</html>
<?php 
