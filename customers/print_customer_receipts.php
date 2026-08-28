<?php
/**
 * Print Customer Payment History
 * Faysal Glass & Aluminium Centre
 */
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$from_date   = isset($_GET['from_date'])   ? mysqli_real_escape_string($conn, $_GET['from_date'])   : date('Y-m-01');
$to_date     = isset($_GET['to_date'])     ? mysqli_real_escape_string($conn, $_GET['to_date'])     : date('Y-m-d');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

$query = "SELECT cr.*, c.customer_name, c.customer_code, c.mobile
          FROM customer_receipts cr
          JOIN customers c ON cr.customer_id = c.id
          WHERE DATE(cr.receipt_date) BETWEEN '$from_date' AND '$to_date'";
if ($customer_id > 0) {
    $query .= " AND cr.customer_id = $customer_id";
}
$query .= " ORDER BY cr.receipt_date ASC, cr.id ASC";
$result = mysqli_query($conn, $query);

$sum_q = "SELECT SUM(amount) as total,
           SUM(CASE WHEN payment_method='cash' THEN amount ELSE 0 END) as cash_total,
           SUM(CASE WHEN payment_method='bank' THEN amount ELSE 0 END) as bank_total,
           COUNT(*) as cnt
           FROM customer_receipts cr
           WHERE DATE(cr.receipt_date) BETWEEN '$from_date' AND '$to_date'"
       . ($customer_id > 0 ? " AND cr.customer_id = $customer_id" : "");
$sum_r = mysqli_query($conn, $sum_q);
$summary = mysqli_fetch_assoc($sum_r);

$cust_label = "All Customers";
if ($customer_id > 0) {
    $cr = mysqli_query($conn, "SELECT customer_name, customer_code FROM customers WHERE id=$customer_id");
    if ($cr && $row = mysqli_fetch_assoc($cr)) {
        $cust_label = htmlspecialchars($row['customer_name'] . ' (' . $row['customer_code'] . ')');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History Print</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #eef1f5; color: #1e1e1e; font-size: 12px; }
        .wrapper { max-width: 900px; margin: 24px auto; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.12); border-radius: 6px; padding: 28px 32px; }
        .company-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px double #0b1e2e; padding-bottom: 14px; margin-bottom: 18px; }
        .brand-logo { width: 52px; height: 52px; background: #0b1e2e; color: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; letter-spacing: 1px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .brand-name { font-size: 20px; font-weight: 800; color: #0b1e2e; text-transform: uppercase; letter-spacing: 1px; }
        .brand-sub { font-size: 11px; color: #5a6a7a; }
        .contact-info { text-align: right; font-size: 11px; color: #374151; line-height: 1.8; }
        .contact-info i { color: #0b1e2e; width: 16px; }
        .report-title { text-align: center; margin: 10px 0 16px; }
        .report-title h2 { font-size: 16px; font-weight: 700; color: #0b1e2e; letter-spacing: 2px; text-transform: uppercase; }
        .report-title p { font-size: 11px; color: #5a6a7a; }
        .summary-bar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .sum-box { flex: 1; min-width: 120px; text-align: center; padding: 10px; border: 1px solid #d0d4dc; border-radius: 6px; border-top: 3px solid #0b1e2e; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sum-box .lbl { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .sum-box .val { font-size: 16px; font-weight: 700; color: #0b1e2e; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead th { background: #0b1e2e; color: #fff; padding: 8px 10px; text-align: left; font-weight: 600; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) td { background: #f8faf9; }
        .badge-cash { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .badge-bank { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        tfoot td { background: #f8faf9; font-weight: 700; padding: 8px 10px; border-top: 2px solid #0b1e2e; }
        .print-footer { margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; }
        .sig-line { border-bottom: 1px solid #374151; height: 30px; margin-bottom: 4px; min-width: 120px; }
        .action-bar { text-align: center; padding: 14px; background: rgba(255,255,255,0.96); box-shadow: 0 -2px 12px rgba(0,0,0,0.1); position: fixed; bottom: 0; left: 0; right: 0; z-index: 100; }
        .btn-action { padding: 9px 24px; margin: 0 6px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; color: #fff; }
        .btn-print { background: #0b1e2e; }
        .btn-exit { background: #1a56db; }
        @media print {
            body { background: #fff; }
            .action-bar { display: none !important; }
            .wrapper { box-shadow: none; margin: 0; border-radius: 0; padding: 14px 16px; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="company-header">
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="brand-logo">FG</div>
            <div>
                <div class="brand-name">Faisal Glass &amp; Aluminum Centre</div>
                <div class="brand-sub">Dealers of Ghani Glass Limited</div>
            </div>
        </div>
        <div class="contact-info">
            <div><i class="fas fa-phone-alt"></i> 0321-4186775 &nbsp; <i class="fas fa-mobile-alt"></i> 0322-8701098</div>
            <div><i class="fas fa-map-marker-alt"></i> Lajna Chowk Collage Road Township Lahore</div>
        </div>
    </div>

    <div class="report-title">
        <h2>Customer Payment Receipt History</h2>
        <p>Period: <strong><?php echo date('d-m-Y', strtotime($from_date)); ?></strong> to <strong><?php echo date('d-m-Y', strtotime($to_date)); ?></strong>
        &nbsp;&nbsp;|&nbsp;&nbsp; Customer: <strong><?php echo $cust_label; ?></strong>
        &nbsp;&nbsp;|&nbsp;&nbsp; Printed: <?php echo date('d-m-Y h:i A'); ?></p>
    </div>

    <div class="summary-bar">
        <div class="sum-box">
            <div class="lbl">Total Received</div>
            <div class="val"><?php echo formatCurrency(floatval($summary['total'])); ?></div>
        </div>
        <div class="sum-box">
            <div class="lbl">Cash</div>
            <div class="val"><?php echo formatCurrency(floatval($summary['cash_total'])); ?></div>
        </div>
        <div class="sum-box">
            <div class="lbl">Bank</div>
            <div class="val"><?php echo formatCurrency(floatval($summary['bank_total'])); ?></div>
        </div>
        <div class="sum-box">
            <div class="lbl">Transactions</div>
            <div class="val"><?php echo intval($summary['cnt']); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Receipt #</th>
                <th>Date</th>
                <th>Customer Code</th>
                <th>Customer Name</th>
                <th>Mobile</th>
                <th>Method</th>
                <th>Reference No</th>
                <th>Invoice No</th>
                <th style="text-align:right;">Amount (Rs.)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sr = 0;
            $grand_total = 0;
            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    $sr++;
                    $grand_total += floatval($row['amount']);
            ?>
            <tr>
                <td><?php echo $sr; ?></td>
                <td><strong>RCP-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($row['receipt_date'])); ?></td>
                <td><?php echo htmlspecialchars($row['customer_code']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['mobile'] ?? '-'); ?></td>
                <td>
                    <?php if ($row['payment_method'] == 'cash'): ?>
                        <span class="badge-cash">Cash</span>
                    <?php else: ?>
                        <span class="badge-bank">Bank</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['reference_no']) ?: '-'; ?></td>
                <td style="font-weight:600;color:#92400e;"><?php echo htmlspecialchars($row['sale_invoice_no']) ?: '-'; ?></td>
                <td style="text-align:right;font-weight:600;"><?php echo number_format(floatval($row['amount']), 2); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']) ?: '-'; ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="11" style="text-align:center;padding:20px;color:#888;">No records found</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9" style="text-align:right;">Grand Total:</td>
                <td style="text-align:right;color:#0b1e2e;"><?php echo number_format($grand_total, 2); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="print-footer">
        <div>Generated by: <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></strong></div>
        <div style="text-align:center;">
            <div class="sig-line"></div>
            <div>Authorized Signature</div>
        </div>
        <div style="text-align:right;">Faisal Glass &amp; Aluminum Centre</div>
    </div>
</div>

<div class="action-bar">
    <button class="btn-action btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <button class="btn-action btn-exit" onclick="window.close()"><i class="fas fa-times"></i> Close</button>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
