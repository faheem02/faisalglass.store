<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$customer_filter = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

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

$customer_where = "";
if($customer_filter > 0) {
    $customer_where = " AND id = $customer_filter";
}

$customers_data_query = "SELECT id, customer_code, customer_name, company_name, mobile, email, 
                         opening_balance, balance_type, current_balance, status, created_at
                         FROM customers 
                         WHERE status = 1 $customer_where
                         ORDER BY customer_name ASC";
$customers_result_data = mysqli_query($conn, $customers_data_query);

$total_customers = 0;
$total_receivable = 0;
$total_payable = 0;
$customer_data = [];
$selected_customer_name = '';

while($customer = mysqli_fetch_assoc($customers_result_data)) {
    $customer_id = $customer['id'];
    $current_balance = floatval($customer['current_balance']);
    
    $sales_query = "SELECT COALESCE(SUM(grand_total), 0) as sales_amount 
                    FROM sale_master 
                    WHERE customer_id = $customer_id 
                    AND sale_date BETWEEN '$from_date' AND '$to_date'";
    $sales_result = mysqli_query($conn, $sales_query);
    $sales_amount = floatval(mysqli_fetch_assoc($sales_result)['sales_amount']);
    
    $received_query = "SELECT COALESCE(SUM(amount), 0) as received_amount 
                       FROM customer_receipts 
                       WHERE customer_id = $customer_id 
                       AND receipt_date BETWEEN '$from_date' AND '$to_date'";
    $received_result = mysqli_query($conn, $received_query);
    $received_amount = floatval(mysqli_fetch_assoc($received_result)['received_amount']);
    
    $opening_balance = floatval($customer['opening_balance']);
    if($customer['balance_type'] == 'payable') {
        $opening_balance = -$opening_balance;
    }
    
    $customer_data[] = [
        'id' => $customer_id,
        'customer_code' => $customer['customer_code'],
        'customer_name' => $customer['customer_name'],
        'company_name' => $customer['company_name'],
        'mobile' => $customer['mobile'],
        'email' => $customer['email'],
        'opening_balance' => $opening_balance,
        'sales_amount' => $sales_amount,
        'received_amount' => $received_amount,
        'current_balance' => $current_balance,
        'status' => $customer['status'],
        'created_at' => $customer['created_at']
    ];
    
    $total_customers++;
    if($current_balance > 0) {
        $total_receivable += $current_balance;
    } elseif($current_balance < 0) {
        $total_payable += abs($current_balance);
    }
    
    if($customer_filter > 0) {
        $selected_customer_name = $customer['customer_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Report</title>
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
        CUSTOMER REPORT
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
            <span class="info-label">Customer</span>
            <span class="info-value"><?php echo $customer_filter > 0 ? htmlspecialchars($selected_customer_name) : 'All Customers'; ?></span>
        </div>
    </div>

    <div class="balance-row" style="grid-template-columns: repeat(3, 1fr);">
        <div class="balance-item">
            <div class="bal-label">Total Customers</div>
            <div class="bal-value bal-neutral"><?php echo $total_customers; ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Receivable</div>
            <div class="bal-value bal-receivable"><?php echo formatCurrency($total_receivable); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Payable</div>
            <div class="bal-value bal-payable"><?php echo formatCurrency($total_payable); ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="10%">Customer Code</th>
                <th width="18%">Customer Name</th>
                <th width="12%">Company</th>
                <th width="10%">Mobile</th>
                <th width="10%">Opening</th>
                <th width="10%">Sales</th>
                <th width="10%">Received</th>
                <th width="20%">Current Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($customer_data)): ?>
                <?php foreach($customer_data as $customer): ?>
                <tr>
                    <td class="text-center"><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                        <?php if(!empty($customer['email'])): ?>
                            <br><small style="color:#6b7280;"><?php echo htmlspecialchars($customer['email']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo htmlspecialchars($customer['company_name'] ?? '—'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($customer['mobile']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($customer['opening_balance']); ?></td>
                    <td class="text-right" style="color:#28a745;font-weight:600;"><?php echo formatCurrency($customer['sales_amount']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($customer['received_amount']); ?></td>
                    <td class="text-center">
                        <?php if($customer['current_balance'] > 0): ?>
                            <span class="type-badge" style="background:#d4edda;color:#155724;border:1px solid #a3d9a5;">
                                <?php echo formatCurrency($customer['current_balance']); ?> DR
                            </span>
                        <?php elseif($customer['current_balance'] < 0): ?>
                            <span class="type-badge" style="background:#f8d7da;color:#842029;border:1px solid #f5c6cb;">
                                <?php echo formatCurrency(abs($customer['current_balance'])); ?> CR
                            </span>
                        <?php else: ?>
                            <span class="type-badge tb-other"><?php echo formatCurrency(0); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center" style="padding:20px;">No customers found for the selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="4" class="text-right">Totals:</td>
                <td class="text-right">-</td>
                <td class="text-right"><?php echo formatCurrency(array_sum(array_column($customer_data, 'sales_amount'))); ?></td>
                <td class="text-right"><?php echo formatCurrency(array_sum(array_column($customer_data, 'received_amount'))); ?></td>
                <td class="text-right"><?php echo formatCurrency($total_receivable - $total_payable); ?></td>
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
