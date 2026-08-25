<?php
/**
 * Print Customer Detail Report Page
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
if($customer_id == 0) {
    header("Location: view_customer.php");
    exit();
}

$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = mysqli_query($conn, $query);
if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: view_customer.php");
    exit();
}
$customer = mysqli_fetch_assoc($result);

// Sales summary
$sales_query = "SELECT COUNT(*) as total_sales, COALESCE(SUM(grand_total), 0) as total_amount 
                FROM sale_master WHERE customer_id = $customer_id AND status = 1";
$sales_result = mysqli_query($conn, $sales_query);
$sales_data = ($sales_result && mysqli_num_rows($sales_result) > 0) ? mysqli_fetch_assoc($sales_result) : ['total_sales' => 0, 'total_amount' => 0];

// Payments summary
$payment_query = "SELECT COALESCE(SUM(amount), 0) as total_payments 
                  FROM customer_receipts WHERE customer_id = $customer_id";
$payment_result = mysqli_query($conn, $payment_query);
$payment_data = ($payment_result && mysqli_num_rows($payment_result) > 0) ? mysqli_fetch_assoc($payment_result) : ['total_payments' => 0];

// Purchase history (last 20)
$purchase_query = "SELECT sm.invoice_no, sm.sale_date, sm.grand_total,
                   sd.id as detail_id, sd.product_id, sd.client_size, sd.client_height, sd.client_width,
                   sd.area, sd.quantity, sd.rate, sd.amount, sd.discount_percentage,
                   p.product_name, p.product_code
                   FROM sale_master sm
                   LEFT JOIN sale_details sd ON sm.id = sd.sale_id
                   LEFT JOIN products p ON sd.product_id = p.id
                   WHERE sm.customer_id = $customer_id AND sm.status = 1
                   ORDER BY sm.sale_date DESC, sm.id DESC
                   LIMIT 20";
$purchase_result = mysqli_query($conn, $purchase_query);

// Recent transactions (last 10)
$trans_query = "SELECT * FROM customer_ledger 
                WHERE customer_id = $customer_id 
                ORDER BY date DESC, id DESC LIMIT 10";
$trans_result = mysqli_query($conn, $trans_query);

$current_balance = isset($customer['current_balance']) ? floatval($customer['current_balance']) : 0;
$balance_status = ($current_balance >= 0) ? 'Receivable' : 'Payable';
$balance_class = ($current_balance >= 0) ? 'pos' : 'neg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Detail Report</title>
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
            font-size: 11.5px;
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

        /* ===== Balance Box ===== */
        .balance-box {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .bal-item {
            text-align: center;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 8px;
        }
        .bal-item .bal-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .bal-item .bal-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }
        .bal-blue { color: #4e73df; }
        .bal-green { color: #1e7e34; }

        /* ===== Section ===== */
        .print-section {
            margin-bottom: 18px;
        }
        .section-heading {
            font-size: 12.5px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-bottom: 2px solid #1e7e34;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* ===== Table ===== */
        .list-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 4px;
        }
        .list-table th {
            background: #1e7e34;
            color: #fff;
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .list-table td {
            padding: 5px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .list-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .list-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .list-table .text-center { text-align: center; }
        .size-badge {
            display: inline-block;
            padding: 1px 7px;
            font-size: 9px;
            background: #e3f2fd;
            color: #0066cc;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .type-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tb-sale { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .tb-payment { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .tb-opening { background: #e3f2fd; color: #0066cc; border: 1px solid #bbdefb; }
        .tb-other { background: #e2e8f0; color: #374151; border: 1px solid #cbd5e1; }
        .debit-text { color: #dc3545; font-weight: 600; }
        .credit-text { color: #28a745; font-weight: 600; }

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
        CUSTOMER DETAIL REPORT
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
        <?php if(!empty($customer['company_name'])): ?>
        <div class="info-item">
            <span class="info-label">Company</span>
            <span class="info-value"><?php echo htmlspecialchars($customer['company_name']); ?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($customer['email'])): ?>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($customer['address'])): ?>
        <div class="info-item">
            <span class="info-label">Address</span>
            <span class="info-value"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="balance-box">
        <div class="bal-item">
            <div class="bal-label">Current Balance</div>
            <div class="bal-value <?php echo $balance_class; ?>">Rs <?php echo number_format(abs($current_balance), 2); ?></div>
        </div>
        <div class="bal-item">
            <div class="bal-label">Balance Type</div>
            <div class="bal-value bal-blue"><?php echo $balance_status; ?></div>
        </div>
        <div class="bal-item">
            <div class="bal-label">Total Sales</div>
            <div class="bal-value bal-green"><?php echo $sales_data['total_sales']; ?></div>
        </div>
        <div class="bal-item">
            <div class="bal-label">Total Sales Amount</div>
            <div class="bal-value bal-green">Rs <?php echo number_format($sales_data['total_amount'], 2); ?></div>
        </div>
    </div>

    <div class="print-section">
        <div class="section-heading">Customer Purchase History (Last 20)</div>
        <table class="list-table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Size (H x W)</th>
                    <th>Area</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Net Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if($purchase_result && mysqli_num_rows($purchase_result) > 0): ?>
                    <?php while($purchase = mysqli_fetch_assoc($purchase_result)): ?>
                    <tr>
                        <td class="text-center"><strong><?php echo htmlspecialchars($purchase['invoice_no']); ?></strong></td>
                        <td class="text-center"><?php echo date('d-m-Y', strtotime($purchase['sale_date'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($purchase['product_name'] ?? 'N/A'); ?></strong>
                            <?php if(!empty($purchase['product_code'])): ?>
                                <br><small><?php echo htmlspecialchars($purchase['product_code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if(!empty($purchase['client_size'])) {
                                echo '<span class="size-badge">' . htmlspecialchars($purchase['client_size']) . '</span>';
                            } elseif(floatval($purchase['client_height']) > 0 && floatval($purchase['client_width']) > 0) {
                                echo '<span class="size-badge">' . htmlspecialchars($purchase['client_height']) . ' x ' . htmlspecialchars($purchase['client_width']) . '</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="text-right"><?php echo floatval($purchase['area']) > 0 ? number_format($purchase['area'], 2) : '-'; ?></td>
                        <td class="text-right"><?php echo number_format($purchase['quantity'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($purchase['rate'], 2); ?></td>
                        <td class="text-right"><strong><?php echo number_format($purchase['amount'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center" style="padding:16px;">No purchase history found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="print-section">
        <div class="section-heading">Recent Transactions (Last 10)</div>
        <table class="list-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Debit (DR)</th>
                    <th>Credit (CR)</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if($trans_result && mysqli_num_rows($trans_result) > 0): ?>
                    <?php while($trans = mysqli_fetch_assoc($trans_result)):
                        $badge_class = 'tb-other';
                        $ref_label = ucfirst(strtolower($trans['reference_type']));
                        if($trans['reference_type'] == 'SALE') { $badge_class = 'tb-sale'; $ref_label = 'SAL-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); }
                        elseif($trans['reference_type'] == 'PAYMENT') { $badge_class = 'tb-payment'; $ref_label = 'RCP-' . str_pad($trans['reference_id'], 5, '0', STR_PAD_LEFT); }
                        elseif($trans['reference_type'] == 'OPENING') { $badge_class = 'tb-opening'; $ref_label = 'Opening'; }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo date('d-m-Y', strtotime($trans['date'])); ?></td>
                        <td class="text-center"><span class="type-badge <?php echo $badge_class; ?>"><?php echo $ref_label; ?></span></td>
                        <td><?php echo htmlspecialchars(substr($trans['description'], 0, 80)); ?></td>
                        <td class="text-right debit-text"><?php echo floatval($trans['debit']) > 0 ? 'Rs ' . number_format($trans['debit'], 2) : '-'; ?></td>
                        <td class="text-right credit-text"><?php echo floatval($trans['credit']) > 0 ? 'Rs ' . number_format($trans['credit'], 2) : '-'; ?></td>
                        <td class="text-right"><strong>Rs <?php echo number_format($trans['balance'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center" style="padding:16px;">No transactions found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Total Payments Received: Rs <?php echo number_format($payment_data['total_payments'], 2); ?>
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
