<?php
/**
 * Print Sale Invoice Page - WITH REMOVED REMAINING FROM TOP SECTION
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$invoice_no = isset($_GET['invoice_no']) ? mysqli_real_escape_string($conn, $_GET['invoice_no']) : '';

if(empty($invoice_no)) {
    header("Location: view_invoice.php");
    exit();
}

// Fetch sale master
$query = "SELECT s.*, c.customer_name, c.customer_code, c.mobile, c.address, c.cnic, c.current_balance 
          FROM sale_master s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE s.invoice_no = '$invoice_no'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: view_invoice.php");
    exit();
}

$sale = mysqli_fetch_assoc($result);

// Calculate previous balance
$current_balance = floatval($sale['current_balance']);
$remaining_amount = floatval($sale['remaining_amount']);
$previous_balance = $current_balance - $remaining_amount;
$new_balance = $current_balance;

// Payment method display
$payment_method_display = '';
switch($sale['payment_type']) {
    case 'cash': $payment_method_display = 'Cash'; break;
    case 'bank': $payment_method_display = 'Bank Transfer'; break;
    case 'credit': $payment_method_display = 'Credit'; break;
    case 'partial': $payment_method_display = 'Partial'; break;
    default: $payment_method_display = ucfirst($sale['payment_type']);
}

// Fetch sale details
$details_query = "SELECT sd.*, p.product_name, p.product_code 
                 FROM sale_details sd
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE sd.sale_id = {$sale['id']}";
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
    <title>Sale Invoice - <?php echo $invoice_no; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .invoice-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4; margin: 12mm; }
            .invoice-table thead { display: table-header-group; }
            .invoice-table tr { page-break-inside: avoid; }
            .product-group-row td { background-color: #eaf5eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .product-subtotal-row td { background-color: #f8faf9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .table-footer td, .table-footer { background-color: #e8f5e9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #212529;
            font-size: 13px;
            line-height: 1.6;
        }
        .invoice-container {
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

        /* ===== Invoice Title ===== */
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
            border-radius: 2px;
        }
        .title-bar-left { background: linear-gradient(to right, transparent, #1e7e34); }
        .title-bar-right { background: linear-gradient(to left, transparent, #1e7e34); }

        /* ===== Professional Invoice Meta Section ===== */
        .invoice-meta-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding: 10px 4px 14px 4px;
            margin-bottom: 14px;
            border-bottom: 1.5px solid #d1e7dd;
        }
        .meta-customer-box {
            flex: 1;
            max-width: 58%;
        }
        .meta-sub-header {
            font-size: 10px;
            font-weight: 700;
            color: #1e7e34;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .meta-customer-name {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .meta-customer-code {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .meta-customer-detail {
            font-size: 12px;
            color: #374151;
            line-height: 1.6;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .meta-customer-detail i {
            color: #1e7e34;
            width: 14px;
            font-size: 11px;
        }
        .meta-invoice-box {
            min-width: 270px;
        }
        .meta-invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .meta-invoice-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f0f3f2;
        }
        .meta-invoice-table tr:last-child td {
            border-bottom: none;
        }
        .meta-invoice-table .meta-label {
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: 0.5px;
            width: 45%;
        }
        .meta-invoice-table .meta-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .remarks-box {
            margin-bottom: 12px;
            padding: 9px 14px;
            background: #fff7e6;
            border: 1px solid #ffd591;
            border-left: 4px solid #fa8c16;
            border-radius: 4px;
            font-size: 12px;
        }

        /* ===== Products Table ===== */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-top: 4px;
        }
        .invoice-table th {
            background: #1e7e34;
            color: #fff;
            padding: 9px 6px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-table td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .invoice-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .invoice-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .invoice-table .text-center { text-align: center; }
        .size-subheader th {
            background: #0f6bb5;
            border: 1px solid #0f6bb5;
            font-size: 10px;
            padding: 6px;
            font-weight: 500;
        }
        .table-footer {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
        }
        .product-group-row td {
            background: #eaf5eb !important;
            border-top: 2px solid #1e7e34 !important;
            border-bottom: 1px solid #c3e6cb !important;
            padding: 8px 10px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .product-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            color: #155724;
        }
        .product-group-badge {
            background: #1e7e34;
            color: #fff;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 6px;
            display: inline-block;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .product-subtotal-row td {
            background: #f8faf9 !important;
            border-top: 1px solid #d1e7dd !important;
            border-bottom: 2px solid #cbd5e1 !important;
            font-weight: 700;
            font-size: 11.5px;
            color: #1b4332;
            padding: 6px 8px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ===== Payment Summary ===== */
        .payment-breakdown {
            width: 380px;
            margin: 22px 0 10px auto;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
        }
        .payment-head {
            background: #1e7e34;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 8px 14px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .payment-body { padding: 4px 14px 8px; }
        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 6px 0;
            border-bottom: 1px dotted #cbd5e1;
            font-size: 12px;
        }
        .payment-label { font-weight: 600; color: #374151; }
        .payment-value { font-variant-numeric: tabular-nums; }
        .payment-row:last-child { border-bottom: none; }
        .grand-total-row { font-weight: 700; }
        .grand-total-row .payment-value { color: #1e7e34; font-size: 13.5px; }

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
<div class="invoice-container" id="invoiceContent">
    
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
    
    <!-- Invoice Title -->
    <div class="invoice-title">
        <span class="title-bar title-bar-left"></span>
        SALE INVOICE
        <span class="title-bar title-bar-right"></span>
    </div>
    
    <!-- Professional Customer and Invoice Meta Section -->
    <div class="invoice-meta-container">
        <div class="meta-customer-box">
            <div class="meta-sub-header">BILL TO</div>
            <div class="meta-customer-name"><?php if(!empty($sale['walk_in_customer_name'])): ?><?php echo htmlspecialchars($sale['walk_in_customer_name']); ?><?php else: ?><?php echo trim(htmlspecialchars($sale['customer_name'])); ?><?php endif; ?></div>
            <?php if(!empty($sale['walk_in_customer_name'])): ?>
                <div class="meta-customer-code">Walk-In <?php echo !empty($sale['walk_in_customer_phone']) ? ' - ' . htmlspecialchars($sale['walk_in_customer_phone']) : ''; ?></div>
            <?php elseif(!empty($sale['customer_code'])): ?>
                <div class="meta-customer-code">Customer ID: <?php echo htmlspecialchars($sale['customer_code']); ?></div>
            <?php endif; ?>
            <?php if(!empty($sale['walk_in_customer_name'])): ?>
                <?php if(!empty($sale['walk_in_customer_phone'])): ?>
                <div class="meta-customer-detail"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($sale['walk_in_customer_phone']); ?></div>
                <?php endif; ?>
            <?php elseif(!empty($sale['mobile'])): ?>
                <div class="meta-customer-detail"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($sale['mobile']); ?></div>
            <?php endif; ?>
            <?php if(!empty($sale['address'])): ?>
                <div class="meta-customer-detail"><i class="fas fa-map-marker-alt"></i> <?php echo trim(htmlspecialchars($sale['address'])); ?></div>
            <?php endif; ?>
            <?php if(!empty($sale['cnic'])): ?>
                <div class="meta-customer-detail"><i class="fas fa-id-card"></i> CNIC: <?php echo htmlspecialchars($sale['cnic']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="meta-invoice-box">
            <table class="meta-invoice-table">
                <tr>
                    <td class="meta-label">Invoice No:</td>
                    <td class="meta-value font-weight-bold text-success" style="font-size: 14px;"><?php echo $sale['invoice_no']; ?></td>
                </tr>
                <tr>
                    <td class="meta-label">Invoice Date:</td>
                    <td class="meta-value"><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></td>
                </tr>
                <tr>
                    <td class="meta-label">Payment Method:</td>
                    <td class="meta-value"><?php echo $payment_method_display; ?></td>
                </tr>
                <?php if(!empty($sale['reference_no'])): ?>
                <tr>
                    <td class="meta-label">Reference No:</td>
                    <td class="meta-value"><?php echo htmlspecialchars($sale['reference_no']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <?php if(!empty($sale['remarks'])): ?>
    <div class="remarks-box">
        <strong>Remarks:</strong> <?php echo htmlspecialchars($sale['remarks']); ?>
    </div>
    <?php endif; ?>
    
    <!-- Products Table with Product-Wise Grouping -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 6%;">SR #</th>
                <th colspan="2">ACTUAL SIZE (INCH)</th>
                <th rowspan="2" style="width: 8%;">QTY</th>
                <th rowspan="2" style="width: 14%;">Total Area (sq ft)</th>
                <th rowspan="2" style="width: 12%;">RATE (₨)</th>
                <th rowspan="2" style="width: 16%;">TOTAL AMOUNT (₨)</th>
            </tr>
            <tr class="size-subheader">
                <th style="width: 11%;">HEIGHT</th>
                <th style="width: 11%;">WIDTH</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Group products by product_id
            $grouped_products = [];
            foreach($products_list as $detail) {
                $pid = !empty($detail['product_id']) ? intval($detail['product_id']) : ($detail['product_name'] ?? 'general');
                if(!isset($grouped_products[$pid])) {
                    $grouped_products[$pid] = [
                        'product_name' => !empty($detail['product_name']) ? $detail['product_name'] : 'General Product',
                        'product_code' => $detail['product_code'] ?? '',
                        'items' => [],
                        'subtotal_qty' => 0,
                        'subtotal_area' => 0,
                        'subtotal_amount' => 0
                    ];
                }
                $grouped_products[$pid]['items'][] = $detail;
                $grouped_products[$pid]['subtotal_qty'] += floatval($detail['quantity'] ?? 0);
                $grouped_products[$pid]['subtotal_area'] += floatval($detail['area'] ?? 0);
                $grouped_products[$pid]['subtotal_amount'] += floatval($detail['amount'] ?? 0);
            }

            $sr = 1;
            $total_total_qty = 0;
            $total_total_area = 0;
            $total_total_price = 0;
            
            foreach($grouped_products as $group):
                $total_total_qty += $group['subtotal_qty'];
                $total_total_area += $group['subtotal_area'];
                $total_total_price += $group['subtotal_amount'];
            ?>
            <!-- Product Header Row -->
            <tr class="product-group-row">
                <td colspan="7">
                    <div class="product-group-title">
                        <span>
                            <span class="product-group-badge">Product</span>
                            <strong><?php echo htmlspecialchars($group['product_name']); ?></strong>
                            <?php if(!empty($group['product_code'])): ?>
                                <small class="text-muted" style="font-weight: normal;">(<?php echo htmlspecialchars($group['product_code']); ?>)</small>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 11px; font-weight: normal; color: #155724;">
                            <?php echo count($group['items']); ?> <?php echo count($group['items']) === 1 ? 'size' : 'sizes'; ?>
                        </span>
                    </div>
                </td>
            </tr>

            <!-- Product Size Entries -->
            <?php foreach($group['items'] as $detail):
                $client_h = floatval($detail['client_height']);
                $client_w = floatval($detail['client_width']);
                $qty = floatval($detail['quantity']);
                $total_area = floatval($detail['area']);
                $rate = floatval($detail['rate']);
                $amount = floatval($detail['amount']);
            ?>
            <tr>
                <td class="text-center"><?php echo $sr++; ?></td>
                <td class="text-center"><?php echo $client_h > 0 ? number_format($client_h, 1) : '-'; ?></td>
                <td class="text-center"><?php echo $client_w > 0 ? number_format($client_w, 1) : '-'; ?></td>
                <td class="text-center"><?php echo number_format($qty, 0); ?></td>
                <td class="text-right"><?php echo number_format($total_area, 2); ?></td>
                <td class="text-right"><?php echo number_format($rate, 2); ?></td>
                <td class="text-right"><?php echo number_format($amount, 2); ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- Product Subtotal Row -->
            <tr class="product-subtotal-row">
                <td colspan="3" class="text-right">
                    <strong>Total (<?php echo htmlspecialchars($group['product_name']); ?>):</strong>
                </td>
                <td class="text-center"><strong><?php echo number_format($group['subtotal_qty'], 0); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($group['subtotal_area'], 2); ?> sq ft</strong></td>
                <td></td>
                <td class="text-right"><strong>Rs <?php echo number_format($group['subtotal_amount'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="3" class="text-right"><strong>Grand Totals:</strong></td>
                <td class="text-center"><strong><?php echo number_format($total_total_qty, 0); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($total_total_area, 2); ?> sq ft</strong></td>
                <td></td>
                <td class="text-right"><strong>Rs <?php echo number_format($total_total_price, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Payment Breakdown Section -->
    <div class="payment-breakdown">
        <div class="payment-head">PAYMENT SUMMARY</div>
        <div class="payment-body">
            <div class="payment-row">
                <span class="payment-label">Previous Balance:</span>
                <span class="payment-value">Rs <?php echo number_format($previous_balance, 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Subtotal:</span>
                <span class="payment-value">Rs <?php echo number_format($sale['subtotal'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Discount (<?php echo number_format($sale['discount_percentage'], 2); ?>%):</span>
                <span class="payment-value">- Rs <?php echo number_format($sale['discount_amount'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Other Charges:</span>
                <span class="payment-value">+ Rs <?php echo number_format($sale['other_charges'], 2); ?></span>
            </div>
            <div class="payment-row grand-total-row">
                <span class="payment-label">Grand Total:</span>
                <span class="payment-value">Rs <?php echo number_format($sale['grand_total'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Paid Amount:</span>
                <span class="payment-value">Rs <?php echo number_format($sale['received_amount'], 2); ?></span>
            </div>
            <div class="payment-row">
                <span class="payment-label">Remaining:</span>
                <span class="payment-value">Rs <?php echo number_format($sale['remaining_amount'], 2); ?></span>
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
        <div class="thank-you">Thank you for your business!</div>
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
        const element = document.getElementById('invoiceContent');
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Sale_Invoice_<?php echo $invoice_no; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, letterRendering: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
    $('#exitBtn').on('click', function() { 
        window.location.href = 'view_invoice.php'; 
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>