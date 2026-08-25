<?php
/**
 * Print Supplier List Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$suppliers_query = "SELECT s.*, 
                    (SELECT SUM(credit) - SUM(debit) FROM supplier_ledger WHERE supplier_id = s.id) as calculated_balance
                    FROM suppliers s 
                    ORDER BY s.id DESC";
$suppliers_result = mysqli_query($conn, $suppliers_query);

$total_suppliers = 0;
$total_payable = 0;
$total_receivable = 0;
$active_suppliers = 0;

$summary_query = "SELECT s.*, 
                  (SELECT SUM(credit) - SUM(debit) FROM supplier_ledger WHERE supplier_id = s.id) as balance
                  FROM suppliers s";
$summary_result = mysqli_query($conn, $summary_query);

while($sup = mysqli_fetch_assoc($summary_result)) {
    $total_suppliers++;
    $balance = floatval($sup['balance']);
    if($balance > 0) {
        $total_payable += $balance;
    } elseif($balance < 0) {
        $total_receivable += abs($balance);
    }
    if($sup['status'] == 1) $active_suppliers++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier List</title>
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
        .sum-payable { color: #dc3545; }
        .sum-receivable { color: #28a745; }

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
        .supplier-code {
            font-family: 'Consolas', monospace;
            font-weight: 700;
            color: #0066cc;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .status-active { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .status-inactive { background: #f8d7da; color: #721c24; border: 1px solid #f1b0b7; }
        .bal-payable { color: #dc3545; font-weight: 700; }
        .bal-receivable { color: #28a745; font-weight: 700; }
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
        SUPPLIER LIST
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="summary-row">
        <div class="summary-item">
            <div class="sum-label">Total Suppliers</div>
            <div class="sum-value" style="color:#111827;"><?php echo $total_suppliers; ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Active Suppliers</div>
            <div class="sum-value" style="color:#111827;"><?php echo $active_suppliers; ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Total Payable</div>
            <div class="sum-value sum-payable">Rs <?php echo number_format($total_payable, 2); ?></div>
        </div>
        <div class="summary-item">
            <div class="sum-label">Total Receivable</div>
            <div class="sum-value sum-receivable">Rs <?php echo number_format($total_receivable, 2); ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="9%">Code</th>
                <th>Supplier Name</th>
                <th>Company</th>
                <th>Mobile</th>
                <th width="11%">Opening Balance</th>
                <th width="13%">Current Balance</th>
                <th width="8%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $footer_payable = 0;
            $footer_receivable = 0;
            if($suppliers_result && mysqli_num_rows($suppliers_result) > 0):
                while($supplier = mysqli_fetch_assoc($suppliers_result)):
                    $balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                                      FROM supplier_ledger WHERE supplier_id = {$supplier['id']}";
                    $balance_result = mysqli_query($conn, $balance_query);
                    $balance_data = mysqli_fetch_assoc($balance_result);
                    $current_balance = floatval($balance_data['total_credit']) - floatval($balance_data['total_debit']);

                    if($current_balance > 0) {
                        $balance_display = 'Rs ' . number_format($current_balance, 2) . ' (Payable)';
                        $balance_class = 'bal-payable';
                        $footer_payable += $current_balance;
                    } elseif($current_balance < 0) {
                        $balance_display = 'Rs ' . number_format(abs($current_balance), 2) . ' (Receivable)';
                        $balance_class = 'bal-receivable';
                        $footer_receivable += abs($current_balance);
                    } else {
                        $balance_display = '0.00';
                        $balance_class = '';
                    }

                    $opening_display = '-';
                    if($supplier['balance_type'] == 'payable' && floatval($supplier['opening_balance']) > 0) {
                        $opening_display = 'Rs ' . number_format($supplier['opening_balance'], 2) . ' (Payable)';
                    } elseif($supplier['balance_type'] == 'receivable' && floatval($supplier['opening_balance']) > 0) {
                        $opening_display = 'Rs ' . number_format($supplier['opening_balance'], 2) . ' (Receivable)';
                    }
            ?>
            <tr>
                <td class="text-center supplier-code"><?php echo htmlspecialchars($supplier['supplier_code']); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                    <?php if($supplier['contact_person']): ?>
                        <br><small style="color:#6b7280;"><i class="fas fa-user"></i> <?php echo htmlspecialchars($supplier['contact_person']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                <td><?php echo htmlspecialchars($supplier['mobile']); ?></td>
                <td class="text-right"><?php echo $opening_display; ?></td>
                <td class="text-right <?php echo $balance_class; ?>"><?php echo $balance_display; ?></td>
                <td class="text-center">
                    <?php if($supplier['status'] == 1): ?>
                        <span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;">No suppliers found</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if(mysqli_num_rows($suppliers_result) > 0): ?>
        <tfoot>
            <tr class="table-footer">
                <td colspan="5" class="text-right">Totals (Payable / Receivable):</td>
                <td class="text-right">
                    Payable: Rs <?php echo number_format($footer_payable, 2); ?><br>
                    Receivable: Rs <?php echo number_format($footer_receivable, 2); ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Total Suppliers: <?php echo $total_suppliers; ?>
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
