<?php
/**
 * Print Supplier Payment Receipt
 * Faysal Glass & Aluminium Centre
 */
session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile, s.company_name,
          ba.bank_name, ba.account_title, ba.account_number
          FROM supplier_payments p
          LEFT JOIN suppliers s ON p.supplier_id = s.id
          LEFT JOIN bank_accounts ba ON p.bank_account_id = ba.id
          WHERE p.id = $payment_id";
$result = mysqli_query($conn, $query);
$payment = mysqli_fetch_assoc($result);

if(!$payment) {
    header("Location: paid_amount.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - PAY-<?php echo str_pad($payment['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #dde3ea;
            font-family: 'Poppins', sans-serif;
            display: flex; flex-direction: column; align-items: center;
            justify-content: flex-start; min-height: 100vh;
            padding: 28px 16px 80px; color: #1e1e1e;
        }
        .toolbar { display: flex; gap: 12px; margin-bottom: 22px; flex-wrap: wrap; justify-content: center; }
        .toolbar button {
            padding: 9px 26px; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .btn-back  { background: #1e7e34; color: #fff; }
        .btn-print { background: #2c6e9c; color: #fff; }
        .btn-pdf   { background: #c0392b; color: #fff; }

        /* Receipt Card */
        .receipt-card {
            width: 420px; background: #fff;
            border-radius: 4px; box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            overflow: hidden;
        }

        /* Top band */
        .top-band {
            background: #0b1e2e; color: #fff;
            padding: 16px 20px 12px;
            display: flex; justify-content: space-between; align-items: flex-start;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .company-name { font-size: 18px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; line-height: 1.2; }
        .company-sub  { font-size: 9.5px; opacity: 0.8; margin-top: 2px; }
        .company-info { font-size: 9px; opacity: 0.75; margin-top: 4px; line-height: 1.6; }
        .receipt-no-box { text-align: center; background: rgba(255,255,255,0.12); padding: 7px 12px; border-radius: 4px; white-space: nowrap; }
        .receipt-no-box .rno-label { font-size: 8.5px; letter-spacing: 1.5px; opacity: 0.8; text-transform: uppercase; }
        .receipt-no-box .rno-val   { font-size: 15px; font-weight: 800; }

        /* Title ribbon */
        .title-ribbon {
            background: #f0f4f8; text-align: center; padding: 8px 0;
            font-size: 12px; font-weight: 700; letter-spacing: 5px;
            color: #0b1e2e; text-transform: uppercase;
            border-bottom: 1.5px dashed #c0c8d8;
        }

        /* Body */
        .receipt-body { padding: 14px 20px; }
        .section-head {
            font-size: 9px; font-weight: 700; letter-spacing: 2px;
            color: #8a9ab0; text-transform: uppercase;
            margin: 12px 0 6px; padding-bottom: 3px;
            border-bottom: 1px solid #e8eef4;
        }
        .detail-row {
            display: flex; justify-content: space-between; align-items: baseline;
            padding: 4px 0; font-size: 11px; border-bottom: 1px dotted #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-lbl { color: #6b7a8d; font-weight: 500; }
        .detail-val { color: #1a2535; font-weight: 600; text-align: right; }

        /* Amount box */
        .amount-box {
            background: #0b1e2e; color: #fff;
            margin: 14px 0 4px; padding: 12px 16px; border-radius: 4px;
            display: flex; justify-content: space-between; align-items: center;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .am-label { font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; opacity: 0.8; }
        .am-value { font-size: 22px; font-weight: 800; }

        /* Payment method */
        .method-row { display: flex; gap: 18px; padding: 8px 0; font-size: 10.5px; }
        .method-item { display: flex; align-items: center; gap: 5px; color: #3a4a5a; }
        .chk-box {
            width: 14px; height: 14px; border: 2px solid #0b1e2e;
            border-radius: 2px; display: inline-flex; align-items: center;
            justify-content: center; flex-shrink: 0;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .chk-box.checked { background: #0b1e2e; color: #fff; font-size: 9px; }

        /* Signature */
        .sig-row {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: 16px; padding-top: 10px;
            border-top: 1.5px solid #0b1e2e;
        }
        .co-name { font-size: 12px; font-weight: 700; color: #0b1e2e; }
        .co-sub  { font-size: 8.5px; color: #5a6a7a; }
        .sig-right { text-align: center; }
        .sig-line  { border-bottom: 1px solid #6b7a8d; height: 26px; min-width: 100px; }
        .sig-label { font-size: 9px; color: #6b7a8d; margin-top: 2px; }

        /* Footer */
        .receipt-footer {
            background: #f7f9fb; text-align: center;
            padding: 10px 16px; border-top: 1px solid #e2e8f0;
            font-size: 10px; color: #6b7a8d;
        }
        .receipt-footer .thankyou { font-size: 12px; font-weight: 700; color: #0b1e2e; }

        /* Bottom row: stamp + signature */
        .bottom-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 18px; padding-top: 12px; border-top: 1.5px solid #0b1e2e; }
        .stamp-area { width: 90px; height: 70px; /* empty — stamp lagegi */ }
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
    <button class="btn-back"  onclick="window.close()"><i class="fas fa-arrow-left"></i> Back</button>
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
            <div class="rno-label">Voucher No.</div>
            <div class="rno-val">PAY-<?php echo str_pad($payment['id'], 4, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-ribbon">PAYMENT VOUCHER</div>

    <!-- Body -->
    <div class="receipt-body">

        <div class="section-head">Paid To</div>
        <div class="detail-row">
            <span class="detail-lbl">Supplier Name</span>
            <span class="detail-val"><?php echo htmlspecialchars($payment['supplier_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Supplier Code</span>
            <span class="detail-val"><?php echo htmlspecialchars($payment['supplier_code']); ?></span>
        </div>
        <?php if(!empty($payment['mobile'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Mobile</span>
            <span class="detail-val"><?php echo htmlspecialchars($payment['mobile']); ?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($payment['company_name'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Company</span>
            <span class="detail-val"><?php echo htmlspecialchars($payment['company_name']); ?></span>
        </div>
        <?php endif; ?>

        <div class="section-head">Payment Details</div>
        <div class="detail-row">
            <span class="detail-lbl">Date</span>
            <span class="detail-val"><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></span>
        </div>
        <?php if(!empty($payment['reference_no'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Reference / Cheque No.</span>
            <span class="detail-val"><?php echo htmlspecialchars($payment['reference_no']); ?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($payment['purchase_invoice_no'])): ?>
        <div class="detail-row" style="background:#fffbea;border-radius:3px;padding:5px 4px;">
            <span class="detail-lbl" style="color:#92400e;font-weight:700;">Against Invoice</span>
            <span class="detail-val" style="color:#92400e;font-weight:700;"><?php echo htmlspecialchars($payment['purchase_invoice_no']); ?></span>
        </div>
        <?php endif; ?>
        <?php if($payment['payment_method'] == 'bank' && !empty($payment['bank_name'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Bank</span>
            <span class="detail-val">
                <?php echo htmlspecialchars($payment['bank_name']); ?>
                <?php if(!empty($payment['account_title'])): ?> – <?php echo htmlspecialchars($payment['account_title']); ?><?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if(!empty($payment['remarks'])): ?>
        <div class="detail-row">
            <span class="detail-lbl">Remarks</span>
            <span class="detail-val" style="color:#4a5a6a;"><?php echo htmlspecialchars($payment['remarks']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Amount -->
        <div class="amount-box">
            <div class="am-label">Amount Paid</div>
            <div class="am-value">Rs. <?php echo number_format($payment['amount'], 2); ?></div>
        </div>

        <!-- Method checkboxes -->
        <div class="method-row">
            <div class="method-item">
                <div class="chk-box <?php echo $payment['payment_method']=='cash' ? 'checked' : ''; ?>">
                    <?php echo $payment['payment_method']=='cash' ? '✓' : ''; ?>
                </div>
                Cash
            </div>
            <div class="method-item">
                <div class="chk-box <?php echo $payment['payment_method']=='bank' ? 'checked' : ''; ?>">
                    <?php echo $payment['payment_method']=='bank' ? '✓' : ''; ?>
                </div>
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

    <div class="receipt-footer">
        <div class="thankyou">Thank You for Your Business!</div>
        <div style="margin-top:3px;">Computer generated voucher &bull; <?php echo date('d-m-Y h:i A'); ?></div>
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
        s.onload = doExport;
        s.onerror = () => { alert('PDF library failed to load.'); btn.innerHTML = orig; btn.disabled = false; };
        document.head.appendChild(s);
    } else { doExport(); }

    function doExport() {
        const { jsPDF } = window.jspdf;
        html2canvas(receipt, { scale: 2.5, backgroundColor: '#ffffff', logging: false }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: [80, 200] });
            const w = 80, h = (canvas.height / canvas.width) * 80;
            pdf.addImage(imgData, 'PNG', 0, 0, w, h);
            pdf.save('Faisal_Glass_PAY-<?php echo str_pad($payment['id'], 4, '0', STR_PAD_LEFT); ?>.pdf');
            btn.innerHTML = orig; btn.disabled = false;
        }).catch(() => { alert('PDF generation failed.'); btn.innerHTML = orig; btn.disabled = false; });
    }
}
(function(){
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    s.async = true; document.head.appendChild(s);
})();
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
