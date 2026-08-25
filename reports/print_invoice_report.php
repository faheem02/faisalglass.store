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

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$invoice_type = isset($_GET['invoice_type']) ? $_GET['invoice_type'] : 'all';
$invoice_search = isset($_GET['invoice_search']) ? $_GET['invoice_search'] : '';

if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'yesterday') {
    $from_date = date('Y-m-d', strtotime('-1 day'));
    $to_date = date('Y-m-d', strtotime('-1 day'));
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'year') {
    $from_date = date('Y-01-01');
    $to_date = date('Y-m-d');
}

$sales_query = "SELECT 
                    'SALE' as invoice_type,
                    id,
                    invoice_no,
                    sale_date as invoice_date,
                    customer_id as party_id,
                    COALESCE((SELECT customer_name FROM customers WHERE id = sale_master.customer_id), 'Walk-In Customer') as party_name,
                    COALESCE((SELECT customer_code FROM customers WHERE id = sale_master.customer_id), 'CUS-0000') as party_code,
                    grand_total as total_amount,
                    payment_type
                FROM sale_master 
                WHERE sale_date BETWEEN '$from_date' AND '$to_date'";

$purchase_query = "SELECT 
                    'PURCHASE' as invoice_type,
                    id,
                    invoice_no,
                    purchase_date as invoice_date,
                    supplier_id as party_id,
                    COALESCE((SELECT supplier_name FROM suppliers WHERE id = purchase_master.supplier_id), 'N/A') as party_name,
                    COALESCE((SELECT supplier_code FROM suppliers WHERE id = purchase_master.supplier_id), 'SUP-0000') as party_code,
                    grand_total as total_amount,
                    payment_type
                FROM purchase_master 
                WHERE purchase_date BETWEEN '$from_date' AND '$to_date'";

$final_query = "";
if($invoice_type == 'sale') {
    $final_query = $sales_query;
} elseif($invoice_type == 'purchase') {
    $final_query = $purchase_query;
} else {
    $final_query = $sales_query . " UNION ALL " . $purchase_query;
}

if(!empty($invoice_search)) {
    $final_query .= " AND invoice_no LIKE '%$invoice_search%'";
}

$final_query .= " ORDER BY invoice_date DESC, id DESC";
$result = mysqli_query($conn, $final_query);

$total_sales_amount = 0;
$total_purchase_amount = 0;
$total_invoices = 0;
$invoice_data = [];
$sales_count = 0;
$purchase_count = 0;

while($row = mysqli_fetch_assoc($result)) {
    $row['total_amount'] = floatval($row['total_amount']);
    $invoice_data[] = $row;
    $total_invoices++;
    
    if($row['invoice_type'] == 'SALE') {
        $total_sales_amount += $row['total_amount'];
        $sales_count++;
    } else {
        $total_purchase_amount += $row['total_amount'];
        $purchase_count++;
    }
}

$page_title = "Invoice Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Report</title>
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
        .bal-payable { color: #dc3545; }
        .bal-receivable { color: #28a745; }
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
        .tb-purchase { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .tb-payment { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .tb-opening { background: #e3f2fd; color: #0066cc; border: 1px solid #bbdefb; }
        .tb-adjustment { background: #fff3cd; color: #856404; border: 1px solid #ffe69c; }
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
        INVOICE REPORT
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">From Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($from_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">To Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($to_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Invoice Type</span>
            <span class="info-value"><?php echo $invoice_type == 'sale' ? 'Sales Invoices' : ($invoice_type == 'purchase' ? 'Purchase Invoices' : 'All Invoices'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Search Invoice</span>
            <span class="info-value"><?php echo !empty($invoice_search) ? htmlspecialchars($invoice_search) : 'All'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Sales Invoices</span>
            <span class="info-value"><?php echo $sales_count; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Purchase Invoices</span>
            <span class="info-value"><?php echo $purchase_count; ?></span>
        </div>
    </div>

    <div class="balance-row">
        <div class="balance-item">
            <div class="bal-label">Total Invoices</div>
            <div class="bal-value bal-neutral"><?php echo $total_invoices; ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Sales Amount</div>
            <div class="bal-value bal-receivable"><?php echo formatCurrency($total_sales_amount); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Purchase Amount</div>
            <div class="bal-value bal-payable"><?php echo formatCurrency($total_purchase_amount); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Grand Total</div>
            <div class="bal-value bal-neutral"><?php echo formatCurrency($total_sales_amount + $total_purchase_amount); ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="14%">Invoice #</th>
                <th width="11%">Date</th>
                <th width="9%">Type</th>
                <th width="22%">Party Name</th>
                <th width="13%">Payment Type</th>
                <th width="15%">Total Amount</th>
                <th width="11%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($invoice_data)): ?>
                <?php foreach($invoice_data as $invoice): ?>
                <tr>
                    <td class="text-center">
                        <span style="font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($invoice['invoice_no']); ?></span>
                    </td>
                    <td class="text-center"><?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?></td>
                    <td class="text-center">
                        <?php if($invoice['invoice_type'] == 'SALE'): ?>
                            <span class="type-badge tb-payment">Sale</span>
                        <?php else: ?>
                            <span class="type-badge tb-purchase">Purchase</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($invoice['party_name']); ?>
                        <br><small style="color:#6b7280;font-family:monospace;"><?php echo htmlspecialchars($invoice['party_code']); ?></small>
                    </td>
                    <td class="text-center">
                        <?php
                        $payment_badge_class = 'tb-other';
                        $payment_text = 'Partial';
                        if($invoice['payment_type'] == 'cash') {
                            $payment_badge_class = 'tb-payment';
                            $payment_text = 'Cash';
                        } elseif($invoice['payment_type'] == 'bank') {
                            $payment_badge_class = 'tb-purchase';
                            $payment_text = 'Bank';
                        } elseif($invoice['payment_type'] == 'credit') {
                            $payment_badge_class = 'tb-adjustment';
                            $payment_text = 'Credit';
                        }
                        ?>
                        <span class="type-badge <?php echo $payment_badge_class; ?>"><?php echo $payment_text; ?></span>
                    </td>
                    <td class="text-right"><?php echo formatCurrency($invoice['total_amount']); ?></td>
                    <td class="text-center">
                        <?php if($invoice['invoice_type'] == 'SALE'): ?>
                            <span class="type-badge tb-payment">Completed</span>
                        <?php else: ?>
                            <span class="type-badge tb-purchase">Recorded</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center" style="padding:20px;">No invoices found for the selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if(!empty($invoice_data)): ?>
        <tfoot>
            <tr class="table-footer">
                <td colspan="5" class="text-right">GRAND TOTAL:</td>
                <td class="text-right"><?php echo formatCurrency($total_sales_amount + $total_purchase_amount); ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
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
