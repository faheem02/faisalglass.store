<?php
/**
 * Print Quotation Page - Styled Matching Print Invoice
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$quotation_no = isset($_GET['quotation_no']) ? mysqli_real_escape_string($conn, $_GET['quotation_no']) : '';

if($quotation_id <= 0 && empty($quotation_no)) {
    header("Location: view_quotation.php");
    exit();
}

// Fetch quotation master
$where_clause = $quotation_id > 0 ? "q.id = $quotation_id" : "q.quotation_no = '$quotation_no'";

// Check if payment columns exist in quotation_master
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM quotation_master LIKE 'bank_account_id'");
$has_payment_cols = ($col_check && mysqli_num_rows($col_check) > 0);

if($has_payment_cols) {
    $query = "SELECT q.*, c.customer_name, c.customer_code, c.mobile, c.address, c.email, c.current_balance, b.bank_name, b.account_number 
              FROM quotation_master q
              LEFT JOIN customers c ON q.customer_id = c.id
              LEFT JOIN bank_accounts b ON q.bank_account_id = b.id
              WHERE $where_clause";
} else {
    $query = "SELECT q.*, c.customer_name, c.customer_code, c.mobile, c.address, c.email, c.current_balance
              FROM quotation_master q
              LEFT JOIN customers c ON q.customer_id = c.id
              WHERE $where_clause";
}
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: view_quotation.php");
    exit();
}

$quotation = mysqli_fetch_assoc($result);
$quotation_id = $quotation['id'];

// Balance calculations
$current_balance = floatval($quotation['current_balance'] ?? 0);
$grand_total = floatval($quotation['grand_total'] ?? 0);
$received_amount = floatval($quotation['received_amount'] ?? 0);
$remaining_amount = (isset($quotation['remaining_amount']) && $quotation['remaining_amount'] !== null && floatval($quotation['remaining_amount']) > 0) ? floatval($quotation['remaining_amount']) : max(0, $grand_total - $received_amount);
$previous_balance = $current_balance - $remaining_amount;
$new_balance = $current_balance;

// Payment method display
$payment_type = $quotation['payment_type'] ?? 'credit';
$payment_method_display = '';
switch($payment_type) {
    case 'cash': $payment_method_display = 'Cash'; break;
    case 'bank': $payment_method_display = 'Bank Transfer' . (!empty($quotation['bank_name']) ? ' (' . $quotation['bank_name'] . ')' : ''); break;
    case 'credit': $payment_method_display = 'Credit'; break;
    case 'partial': $payment_method_display = 'Partial'; break;
    default: $payment_method_display = ucfirst($payment_type);
}

// Fetch quotation details
$details_query = "SELECT qd.*, p.product_name, p.product_code 
                 FROM quotation_details qd
                 LEFT JOIN products p ON qd.product_id = p.id
                 WHERE qd.quotation_id = $quotation_id";
$details_result = mysqli_query($conn, $details_query);

$products_list = [];
while($detail = mysqli_fetch_assoc($details_result)) {
    $products_list[] = $detail;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - <?php echo $quotation['quotation_no']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .quotation-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4; margin: 12mm; }
            .quotation-table thead { display: table-header-group; }
            .quotation-table tr { page-break-inside: avoid; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #212529;
            font-size: 13px;
            line-height: 1.6;
        }
        .quotation-container {
            max-width: 1100px;
            margin: 24px auto;
            background: #fff;
            box-shadow: 0 0 24px rgba(0,0,0,0.12);
            border-radius: 6px;
            padding: 28px 34px;
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

        /* ===== Quotation Title ===== */
        .invoice-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            font-size: 17px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 4px;
            margin: 8px 0 16px;
        }
        .invoice-title .title-bar {
            flex: 0 0 70px;
            height: 3px;
            background: linear-gradient(90deg, #1e7e34, transparent);
            border-radius: 2px;
        }
        .invoice-title .title-bar-left {
            background: linear-gradient(90deg, transparent, #1e7e34);
        }

        /* ===== Client Info Grid ===== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px 16px;
            background: #f8faf9;
            border: 1px solid #d1e7dd;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .info-item { display: flex; flex-direction: column; }
        .info-label {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12.5px;
            font-weight: 600;
            color: #1f2937;
        }

        .remarks-box {
            background: #fff8e1;
            border-left: 3px solid #ffc107;
            padding: 6px 12px;
            margin-bottom: 14px;
            font-size: 12px;
            color: #495057;
            border-radius: 0 4px 4px 0;
        }

        /* ===== Table ===== */
        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .quotation-table th {
            background: #1e7e34;
            color: #fff;
            font-weight: 600;
            text-align: center;
            padding: 8px 6px;
            border: 1px solid #1e7e34;
            font-size: 11px;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .quotation-table td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .quotation-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .quotation-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .quotation-table .text-center { text-align: center; }
        .size-subheader th {
            background: #0f6bb5;
            border: 1px solid #0f6bb5;
            font-size: 10px;
            padding: 6px;
            font-weight: 500;
        }
        .table-footer td {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
        }

        /* ===== Payment Breakdown ===== */
        .payment-breakdown {
            width: 320px;
            margin-left: auto;
            border: 1px solid #d1e7dd;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .payment-head {
            background: #1e7e34;
            color: #fff;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .payment-body { padding: 8px 12px; background: #fafdfb; }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .payment-row:last-child { border-bottom: none; }
        .payment-label { color: #4b5563; }
        .payment-value { font-weight: 600; color: #111827; }
        .grand-total-row {
            border-top: 2px solid #1e7e34;
            border-bottom: 2px solid #1e7e34;
            padding: 6px 0;
            margin: 4px 0;
        }
        .grand-total-row .payment-label,
        .grand-total-row .payment-value {
            font-weight: 700;
            color: #14532d;
            font-size: 13px;
        }

        /* ===== Footer ===== */
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .signature-block { text-align: center; width: 220px; }
        .sig-line { border-bottom: 1.5px solid #374151; height: 34px; margin-bottom: 4px; }
        .sig-label { font-size: 11px; color: #6b7280; letter-spacing: 0.5px; }
        .thank-you {
            font-size: 12px;
            font-weight: 600;
            color: #1e7e34;
            letter-spacing: 0.5px;
            text-align: right;
        }

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
        .btn-pdf { background: #dc3545; }
        .btn-exit { background: #1a56db; }
    </style>
</head>
<body>
<div class="quotation-container" id="quotationContent">
    
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
    
    <!-- Quotation Title -->
    <div class="invoice-title">
        <span class="title-bar title-bar-left"></span>
        QUOTATION
        <span class="title-bar title-bar-right"></span>
    </div>
    
    <!-- Client Information Grid -->
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Customer Name</span>
            <span class="info-value"><?php echo trim(htmlspecialchars($quotation['customer_name'] ?? 'Walk-In')); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Quotation No</span>
            <span class="info-value"><?php echo $quotation['quotation_no']; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Address</span>
            <span class="info-value"><?php echo !empty($quotation['address']) ? trim(htmlspecialchars($quotation['address'])) : '-'; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($quotation['quotation_date'])); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Payment Method</span>
            <span class="info-value"><?php echo $payment_method_display; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Valid Until</span>
            <span class="info-value"><?php echo $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '-'; ?></span>
        </div>
    </div>
    
    <?php if(!empty($quotation['remarks'])): ?>
    <div class="remarks-box">
        <strong>Remarks:</strong> <?php echo htmlspecialchars($quotation['remarks']); ?>
    </div>
    <?php endif; ?>
    
    <!-- Products Table -->
    <table class="quotation-table">
        <thead>
            <tr>
                <th rowspan="2" width="5%">SR #</th>
                <th colspan="2">ACTUAL SIZE</th>
                <th rowspan="2" width="7%">QTY</th>
                <th rowspan="2" width="9%">Total Area (sq ft)</th>
                <th rowspan="2" width="15%">GLASS TYPE</th>
                <th rowspan="2" width="8%">PRICE</th>
                <th rowspan="2" width="12%">TOTAL PRICE</th>
            </tr>
            <tr class="size-subheader">
                <th width="9%">HEIGHT</th>
                <th width="9%">WIDTH</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            $total_total_area = 0;
            $total_total_price = 0;
            
            if(empty($products_list)):
            ?>
            <tr>
                <td colspan="8" class="text-center">No products found for this quotation.</td>
            </tr>
            <?php else: ?>
                <?php foreach($products_list as $detail): 
                    $client_h = floatval($detail['client_height'] ?? 0);
                    $client_w = floatval($detail['client_width'] ?? 0);
                    $qty = floatval($detail['quantity'] ?? 0);
                    $total_area = floatval($detail['area'] ?? 0);
                    if($total_area <= 0) {
                        $total_area = floatval($detail['total_area'] ?? 0);
                    }
                    $rate = floatval($detail['rate'] > 0 ? $detail['rate'] : ($detail['unit_price'] ?? 0));
                    $amount = floatval($detail['net_amount'] > 0 ? $detail['net_amount'] : ($detail['amount'] ?? 0));
                    
                    $total_total_area += $total_area;
                    $total_total_price += $amount;
                ?>
                <tr>
                    <td class="text-center"><?php echo $sr++; ?></td>
                    <td class="text-center"><?php echo $client_h > 0 ? number_format($client_h, 1) : '-'; ?></td>
                    <td class="text-center"><?php echo $client_w > 0 ? number_format($client_w, 1) : '-'; ?></td>
                    <td class="text-center"><?php echo number_format($qty, 0); ?></td>
                    <td class="text-right"><?php echo number_format($total_area, 2); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($detail['product_name']); ?></td>
                    <td class="text-right"><?php echo number_format($rate, 0); ?></td>
                    <td class="text-right"><?php echo number_format($amount, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="4" class="text-right"><strong>Totals:</strong></td>
                <td class="text-right"><strong><?php echo number_format($total_total_area, 2); ?></strong></td>
                <td></td><td></td>
                <td class="text-right"><strong><?php echo number_format($total_total_price, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Payment Breakdown Section (Matching Sales Style) -->
    <div class="payment-breakdown">
        <div class="payment-head">QUOTATION SUMMARY</div>
        <div class="payment-body">
            <div class="payment-row">
                <span class="payment-label">Previous Balance:</span>
                <span class="payment-value">Rs <?php echo number_format($previous_balance, 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Subtotal:</span>
                <span class="payment-value">Rs <?php echo number_format($quotation['subtotal'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Discount (<?php echo number_format($quotation['discount_percentage'], 2); ?>%):</span>
                <span class="payment-value">- Rs <?php echo number_format($quotation['discount_amount'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Other Charges:</span>
                <span class="payment-value">+ Rs <?php echo number_format($quotation['other_charges'], 2); ?></span>
            </div>
            <div class="payment-row grand-total-row">
                <span class="payment-label">Grand Total:</span>
                <span class="payment-value">Rs <?php echo number_format($quotation['grand_total'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Advance / Paid:</span>
                <span class="payment-value">Rs <?php echo number_format($received_amount, 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Remaining Bill:</span>
                <span class="payment-value">Rs <?php echo number_format($remaining_amount, 2); ?></span>
            </div>
            <div class="payment-row grand-total-row">
                <span class="payment-label">New Balance:</span>
                <span class="payment-value">Rs <?php echo number_format($new_balance, 2); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="invoice-footer">
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
        <div class="thank-you">Thank you for choosing us!</div>
    </div>
</div>

<div class="action-bar no-print">
    <button class="btn-action btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
    <button class="btn-action btn-pdf" id="downloadPDF"><i class="fas fa-file-pdf"></i> Download PDF</button>
    <button class="btn-action btn-exit" id="exitBtn"><i class="fas fa-sign-out-alt"></i> Exit</button>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#downloadPDF').on('click', function() {
        const element = document.getElementById('quotationContent');
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Quotation_<?php echo $quotation['quotation_no']; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, letterRendering: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
    $('#exitBtn').on('click', function() { 
        window.location.href = 'view_quotation.php'; 
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>