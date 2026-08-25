<?php
/**
 * Print Supplier Payment Receipt Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile, s.company_name, s.address,
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

$amount_in_words = number_format($payment['amount'], 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .list-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4 portrait; margin: 12mm; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #212529;
            font-size: 12px;
            line-height: 1.6;
        }
        .list-container {
            max-width: 760px;
            margin: 24px auto;
            background: #fff;
            box-shadow: 0 0 24px rgba(0,0,0,0.12);
            border-radius: 6px;
            padding: 26px 30px;
        }

        /* ===== Company Header ===== */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double #1e7e34;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand-left { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 54px;
            height: 54px;
            background: #1e7e34;
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .brand-name {
            font-size: 21px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .brand-tagline {
            font-size: 11px;
            color: #6b7280;
            letter-spacing: 0.5px;
        }
        .company-contact-info {
            text-align: right;
            font-size: 11px;
            color: #374151;
            line-height: 1.8;
        }
        .company-contact-info .contact-line { white-space: nowrap; }
        .company-contact-info i { color: #1e7e34; width: 16px; }

        /* ===== Receipt Header ===== */
        .receipt-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #1e7e34;
            border-radius: 4px;
            padding: 10px 14px;
        }
        .receipt-title {
            font-size: 15px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .receipt-no {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            text-align: right;
        }
        .receipt-no .big {
            font-size: 15px;
            color: #1e7e34;
            font-weight: 700;
        }

        /* ===== Info Grid ===== */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #1e7e34;
            border-radius: 4px;
            padding: 7px 12px;
        }
        .info-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .info-value {
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        /* ===== Amount Box ===== */
        .amount-box {
            text-align: center;
            border: 2px solid #1e7e34;
            border-radius: 8px;
            padding: 16px 10px;
            margin-bottom: 18px;
            background: #f0faf2;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .amount-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .amount-value {
            font-size: 26px;
            font-weight: 800;
            color: #1e7e34;
            margin-top: 2px;
        }
        .amount-words {
            font-size: 11px;
            color: #374151;
            margin-top: 2px;
            font-style: italic;
        }

        /* ===== Details Table ===== */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 18px;
        }
        .detail-table th {
            background: #1e7e34;
            color: #fff;
            padding: 7px 10px;
            text-align: left;
            border: 1px solid #166d2e;
            font-weight: 600;
            width: 38%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .detail-table td {
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }
        .detail-table tr:nth-child(even) td { background: #f8faf9; }

        .remarks-box {
            background: #fffbe8;
            border: 1px solid #ffe69c;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: 11px;
            color: #856404;
        }
        .remarks-box strong { color: #664d03; }

        /* ===== Footer ===== */
        .list-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .generated-info { font-size: 11px; color: #6b7280; }
        .generated-info strong { color: #374151; }
        .signature-block { text-align: center; width: 200px; }
        .sig-line { border-bottom: 1.5px solid #374151; height: 34px; margin-bottom: 4px; }
        .sig-label { font-size: 11px; color: #6b7280; letter-spacing: 0.5px; }

        .action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 12px;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 -2px 12px rgba(0,0,0,0.12);
            z-index: 1000;
        }
        .btn-action {
            padding: 10px 24px;
            margin: 0 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .btn-print { background: #1e7e34; }
        .btn-exit { background: #1a56db; }
    </style>
</head>
<body>
<div class="list-container" id="listContent">

    <div class="company-header">
        <div class="brand-left">
            <div class="brand-logo">FG</div>
            <div>
                <div class="brand-name">Faisal Glass &amp; Aluminum Centre</div>
                <div class="brand-tagline">Deals in all kind of glass local &amp; imported</div>
            </div>
        </div>
        <div class="company-contact-info">
            <div class="contact-line"><i class="fas fa-phone-alt"></i> 0321-4186775 &nbsp;&nbsp; <i class="fas fa-mobile-alt"></i> 0322-8701098</div>
            <div class="contact-line"><i class="fas fa-map-marker-alt"></i> Lajna Chowk Collage Road Township Lahore</div>
        </div>
    </div>

    <div class="receipt-head">
        <div class="receipt-title"><i class="fas fa-receipt"></i> Payment Receipt</div>
        <div class="receipt-no">
            Receipt No: <span class="big">PAY-<?php echo str_pad($payment['id'], 4, '0', STR_PAD_LEFT); ?></span>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Supplier Code</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['supplier_code']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Supplier Name</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['supplier_name']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Payment Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Payment Method</span>
            <span class="info-value">
                <?php if($payment['payment_method'] == 'cash'): ?>
                    <i class="fas fa-money-bill-wave" style="color:#28a745;"></i> Cash
                <?php else: ?>
                    <i class="fas fa-university" style="color:#0066cc;"></i> Bank Transfer / Cheque
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="amount-box">
        <div class="amount-label">Amount Received</div>
        <div class="amount-value"><?php echo formatCurrency($payment['amount']); ?></div>
        <div class="amount-words">Rupees <?php echo $amount_in_words; ?> only</div>
    </div>

    <table class="detail-table">
        <tr>
            <th>Payment ID</th>
            <td><?php echo $payment['id']; ?></td>
        </tr>
        <?php if(!empty($payment['company_name'])): ?>
        <tr>
            <th>Company</th>
            <td><?php echo htmlspecialchars($payment['company_name']); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Mobile</th>
            <td><?php echo htmlspecialchars($payment['mobile']) ?: '-'; ?></td>
        </tr>
        <?php if(!empty($payment['address'])): ?>
        <tr>
            <th>Address</th>
            <td><?php echo htmlspecialchars($payment['address']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if(!empty($payment['reference_no'])): ?>
        <tr>
            <th>Reference No</th>
            <td><?php echo htmlspecialchars($payment['reference_no']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if($payment['payment_method'] == 'bank' && !empty($payment['bank_name'])): ?>
        <tr>
            <th>Bank Details</th>
            <td>
                <?php echo htmlspecialchars($payment['bank_name']); ?>
                <?php if(!empty($payment['account_title'])): ?><br><?php echo htmlspecialchars($payment['account_title']); ?><?php endif; ?>
                <?php if(!empty($payment['account_number'])): ?> (<?php echo htmlspecialchars($payment['account_number']); ?>)<?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if(!empty($payment['remarks'])): ?>
    <div class="remarks-box">
        <strong><i class="fas fa-comment"></i> Remarks:</strong><br>
        <?php echo nl2br(htmlspecialchars($payment['remarks'])); ?>
    </div>
    <?php endif; ?>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Received By: <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Authorized Person'; ?>
        </div>
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
    </div>
</div>

<div class="action-bar no-print">
    <button class="btn-action btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
    <button class="btn-action btn-exit" id="exitBtn"><i class="fas fa-sign-out-alt"></i> Exit</button>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#exitBtn').on('click', function() { window.close(); });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
