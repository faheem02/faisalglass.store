<?php
/**
 * Print Customer Ledger Statement Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

if($customer_id == 0) {
    header("Location: view_customer.php");
    exit();
}

$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = mysqli_query($conn, $query);
$customer = mysqli_fetch_assoc($result);

if(!$customer) {
    header("Location: view_customer.php");
    exit();
}

// Opening balance (before from_date)
$opening_query = "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance 
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND date < '$from_date'";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if($opening_result && mysqli_num_rows($opening_result) > 0) {
    $opening_data = mysqli_fetch_assoc($opening_result);
    $opening_balance = floatval($opening_data['balance']);
}

// Ledger rows
$ledger_query = "SELECT cl.*, s.invoice_no
                 FROM customer_ledger cl
                 LEFT JOIN sale_master s ON cl.reference_type = 'SALE' AND cl.reference_id = s.id
                 WHERE cl.customer_id = $customer_id 
                 AND cl.date BETWEEN '$from_date' AND '$to_date'
                 ORDER BY cl.date ASC, cl.id ASC";
$ledger_result = mysqli_query($conn, $ledger_query);

// Summary
$summary_query = "SELECT 
                    COALESCE(SUM(debit), 0) as total_debit,
                    COALESCE(SUM(credit), 0) as total_credit
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_debit = floatval($summary['total_debit']);
$total_credit = floatval($summary['total_credit']);
$closing_balance = $opening_balance + $total_debit - $total_credit;

$current_balance = floatval($customer['current_balance']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ledger Statement</title>
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
        .info-value.pos { color: #dc3545; }
        .info-value.neg { color: #28a745; }

        /* ===== Balance Row ===== */
        .balance-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .balance-item {
            text-align: center;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 8px;
        }
        .balance-item .bal-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .balance-item .bal-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }
        .bal-debit { color: #dc3545; }
        .bal-credit { color: #28a745; }
        .bal-neutral { color: #111827; }

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
        .type-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tb-sale { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .tb-payment { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .tb-quotation { background: #e2e8f0; color: #374151; border: 1px solid #cbd5e1; }
        .tb-opening { background: #e3f2fd; color: #0066cc; border: 1px solid #bbdefb; }
        .tb-adjustment { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .tb-other { background: #e2e8f0; color: #374151; border: 1px solid #cbd5e1; }
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
        CUSTOMER LEDGER STATEMENT
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Customer Code</span>
            <span class="info-value"><?php echo htmlspecialchars($customer['customer_code']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Customer Name</span>
            <span class="info-value"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Mobile</span>
            <span class="info-value"><?php echo htmlspecialchars($customer['mobile']); ?></span>
        </div>
    </div>

    <div class="balance-row">
        <div class="balance-item">
            <div class="bal-label">Opening Balance</div>
            <div class="bal-value <?php echo $opening_balance > 0 ? 'bal-debit' : ($opening_balance < 0 ? 'bal-credit' : 'bal-neutral'); ?>">
                Rs <?php echo number_format(abs($opening_balance), 2); ?>
                <small><?php echo $opening_balance > 0 ? ' DR' : ($opening_balance < 0 ? ' CR' : ''); ?></small>
            </div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Debits</div>
            <div class="bal-value bal-debit">Rs <?php echo number_format($total_debit, 2); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Credits</div>
            <div class="bal-value bal-credit">Rs <?php echo number_format($total_credit, 2); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Closing Balance</div>
            <div class="bal-value <?php echo $closing_balance > 0 ? 'bal-debit' : ($closing_balance < 0 ? 'bal-credit' : 'bal-neutral'); ?>">
                Rs <?php echo number_format(abs($closing_balance), 2); ?>
                <small><?php echo $closing_balance > 0 ? ' DR' : ($closing_balance < 0 ? ' CR' : ''); ?></small>
            </div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="12%">Reference Type</th>
                <th width="10%">Invoice #</th>
                <th>Description</th>
                <th width="12%">Debit (Sale)</th>
                <th width="12%">Credit (Payment)</th>
                <th width="13%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#f8f9fc;font-weight:600;">
                <td class="text-center"><?php echo date('d-m-Y', strtotime($from_date)); ?></td>
                <td class="text-center"><span class="type-badge tb-opening">Opening</span></td>
                <td class="text-center">-</td>
                <td><strong>Opening Balance</strong></td>
                <td class="text-right"><?php echo $opening_balance > 0 ? number_format($opening_balance, 2) : '-'; ?></td>
                <td class="text-right"><?php echo $opening_balance < 0 ? number_format(abs($opening_balance), 2) : '-'; ?></td>
                <td class="text-right"><strong><?php echo number_format(abs($opening_balance), 2); ?> <?php echo $opening_balance >= 0 ? 'DR' : 'CR'; ?></strong></td>
            </tr>
            <?php
            $running_balance = $opening_balance;
            if($ledger_result && mysqli_num_rows($ledger_result) > 0):
                while($row = mysqli_fetch_assoc($ledger_result)):
                    $debit = floatval($row['debit']);
                    $credit = floatval($row['credit']);
                    $running_balance = floatval($row['balance']);

                    $badge_class = 'tb-other';
                    $type_label = $row['reference_type'];
                    switch($row['reference_type']) {
                        case 'OPENING': $badge_class = 'tb-opening'; $type_label = 'Opening'; break;
                        case 'SALE': $badge_class = 'tb-sale'; $type_label = 'Sale'; break;
                        case 'PAYMENT': $badge_class = 'tb-payment'; $type_label = 'Payment'; break;
                        case 'QUOTATION': $badge_class = 'tb-quotation'; $type_label = 'Quotation'; break;
                        case 'ADJUSTMENT': $badge_class = 'tb-adjustment'; $type_label = 'Adjustment'; break;
                        case 'MANUAL': $badge_class = 'tb-other'; $type_label = 'Manual'; break;
                        default: $type_label = $row['reference_type']; break;
                    }
            ?>
            <tr>
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                <td class="text-center"><span class="type-badge <?php echo $badge_class; ?>"><?php echo $type_label; ?></span></td>
                <td class="text-center">
                    <?php
                    if($row['reference_type'] == 'SALE' && !empty($row['invoice_no'])) {
                        echo htmlspecialchars($row['invoice_no']);
                    } elseif($row['reference_type'] == 'PAYMENT') {
                        echo 'RCP-' . str_pad($row['reference_id'], 4, '0', STR_PAD_LEFT);
                    } elseif($row['reference_type'] == 'QUOTATION') {
                        echo 'QTN-' . str_pad($row['reference_id'], 4, '0', STR_PAD_LEFT);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
                <td class="text-right"><?php echo $debit > 0 ? number_format($debit, 2) : '-'; ?></td>
                <td class="text-right"><?php echo $credit > 0 ? number_format($credit, 2) : '-'; ?></td>
                <td class="text-right">
                    <?php
                    if($running_balance > 0) echo number_format($running_balance, 2) . ' DR';
                    elseif($running_balance < 0) echo number_format(abs($running_balance), 2) . ' CR';
                    else echo '0.00';
                    ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;">No ledger entries found for the selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="4" class="text-right">Totals:</td>
                <td class="text-right"><?php echo number_format($total_debit, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_credit, 2); ?></td>
                <td class="text-right">
                    <?php
                    if($closing_balance > 0) echo number_format($closing_balance, 2) . ' DR';
                    elseif($closing_balance < 0) echo number_format(abs($closing_balance), 2) . ' CR';
                    else echo '0.00';
                    ?>
                </td>
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
