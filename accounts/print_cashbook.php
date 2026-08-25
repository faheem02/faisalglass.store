<?php
/**
 * Print Cash Book Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'today';

if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
}

$opening_query = "SELECT balance FROM cash_book 
                  WHERE date < '$from_date' 
                  ORDER BY date DESC, id DESC LIMIT 1";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if(mysqli_num_rows($opening_result) > 0) {
    $opening_balance = floatval(mysqli_fetch_assoc($opening_result)['balance']);
}

$trans_query = "SELECT * FROM cash_book 
                WHERE date BETWEEN '$from_date' AND '$to_date'
                ORDER BY date ASC, id ASC";
$trans_result = mysqli_query($conn, $trans_query);

$total_debit = 0;
$total_credit = 0;
$transactions = [];

if(mysqli_num_rows($trans_result) > 0) {
    while($row = mysqli_fetch_assoc($trans_result)) {
        $transactions[] = $row;
        $total_debit += floatval($row['debit']);
        $total_credit += floatval($row['credit']);
    }
}
$closing_balance = $opening_balance + $total_debit - $total_credit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .list-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4 portrait; margin: 12mm; }
            .list-table thead { display: table-header-group; }
            .list-table tr { page-break-inside: avoid; }
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
            max-width: 900px;
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
            margin-bottom: 16px;
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

        /* ===== Title ===== */
        .list-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            font-size: 16px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 4px;
            margin: 8px 0 16px;
        }
        .list-title .title-bar {
            flex: 0 0 70px;
            height: 3px;
            border-radius: 2px;
        }
        .title-bar-left { background: linear-gradient(to right, transparent, #1e7e34); }
        .title-bar-right { background: linear-gradient(to left, transparent, #1e7e34); }

        /* ===== Info Grid ===== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
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

        /* ===== Summary Row ===== */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .summary-item {
            text-align: center;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 8px;
        }
        .summary-item .sum-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .summary-item .sum-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }
        .sum-debit { color: #28a745; }
        .sum-credit { color: #dc3545; }

        /* ===== Table ===== */
        .list-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 4px;
        }
        .list-table th {
            background: #1e7e34;
            color: #fff;
            padding: 7px 6px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .list-table td {
            padding: 5px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .list-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .list-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .list-table .text-center { text-align: center; }
        .ref-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .rb-sale { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .rb-receipt { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .rb-expense { background: #fff3cd; color: #856404; border: 1px solid #ffe69c; }
        .rb-withdraw { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .rb-transfer { background: #e2e8f0; color: #374151; border: 1px solid #cbd5e1; }
        .debit-text { color: #28a745; font-weight: 700; }
        .credit-text { color: #dc3545; font-weight: 700; }
        .table-footer {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
        }
        .table-footer td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ===== Footer ===== */
        .list-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .generated-info { font-size: 11px; color: #6b7280; }
        .generated-info strong { color: #374151; }
        .signature-block { text-align: center; width: 200px; }
        .sig-line { border-bottom: 1.5px solid #374151; height: 32px; margin-bottom: 4px; }
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

    <div class="list-title">
        <span class="title-bar title-bar-left"></span>
        CASH BOOK
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Period</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Transactions</span>
            <span class="info-value"><?php echo count($transactions); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Generated On</span>
            <span class="info-value"><?php echo date('d-m-Y h:i A'); ?></span>
        </div>
    </div>

    <div class="summary-row">
        <div class="summary-item">
            <div class="sum-label">Opening Balance</div>
            <div class="sum-value" style="color:#111827;">Rs <?php echo number_format($opening_balance, 2); ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Cash In (Debit)</div>
            <div class="sum-value sum-debit">Rs <?php echo number_format($total_debit, 2); ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Cash Out (Credit)</div>
            <div class="sum-value sum-credit">Rs <?php echo number_format($total_credit, 2); ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Closing Balance</div>
            <div class="sum-value" style="color:#1e40af;">Rs <?php echo number_format($closing_balance, 2); ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="14%">Reference Type</th>
                <th width="11%">Reference #</th>
                <th>Description</th>
                <th width="12%">Debit (Cash In)</th>
                <th width="12%">Credit (Cash Out)</th>
                <th width="13%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#f8f9fc;font-weight:600;">
                <td class="text-center"><?php echo date('d-m-Y', strtotime($from_date)); ?></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td><strong>Opening Balance</strong></td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right"><strong>Rs <?php echo number_format($opening_balance, 2); ?></strong></td>
            </tr>
            <?php foreach($transactions as $trans):
                $badge_class = 'rb-transfer';
                $label = str_replace('_', ' ', $trans['reference_type']);
                switch($trans['reference_type']) {
                    case 'SALE': $badge_class = 'rb-sale'; $label = 'SAL-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); break;
                    case 'CUSTOMER_RECEIPT': $badge_class = 'rb-receipt'; $label = 'RCP-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); break;
                    case 'EXPENSE': $badge_class = 'rb-expense'; $label = 'EXP-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); break;
                    case 'WITHDRAW': $badge_class = 'rb-withdraw'; $label = 'WTH-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); break;
                    case 'TRANSFER': $badge_class = 'rb-transfer'; $label = 'TRF-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); break;
                }
            ?>
            <tr>
                <td class="text-center"><?php echo date('d-m-Y', strtotime($trans['date'])); ?></td>
                <td class="text-center"><?php echo str_replace('_', ' ', $trans['reference_type']); ?></td>
                <td class="text-center"><span class="ref-badge <?php echo $badge_class; ?>"><?php echo $label; ?></span></td>
                <td><?php echo htmlspecialchars($trans['description']); ?></td>
                <td class="text-right debit-text"><?php echo floatval($trans['debit']) > 0 ? 'Rs ' . number_format($trans['debit'], 2) : '-'; ?></td>
                <td class="text-right credit-text"><?php echo floatval($trans['credit']) > 0 ? 'Rs ' . number_format($trans['credit'], 2) : '-'; ?></td>
                <td class="text-right"><strong>Rs <?php echo number_format($trans['balance'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($transactions)): ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;">No transactions found in selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="4" class="text-right">Totals:</td>
                <td class="text-right">Rs <?php echo number_format($total_debit, 2); ?></td>
                <td class="text-right">Rs <?php echo number_format($total_credit, 2); ?></td>
                <td class="text-right">Rs <?php echo number_format($closing_balance, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>
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
