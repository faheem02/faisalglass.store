<?php
/**
 * Print / PDF Hold Bill Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include(__DIR__ . '/../includes/database.php');
include(__DIR__ . '/../includes/txt.php');

$hold_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hold_no = isset($_GET['hold_no']) ? mysqli_real_escape_string($conn, trim($_GET['hold_no'])) : '';

if($hold_id <= 0 && empty($hold_no)) {
    header("Location: add_sale.php");
    exit();
}

$where = $hold_id > 0 ? "h.id = $hold_id" : "h.hold_no = '$hold_no'";

// Fetch hold master
$query = "SELECT h.*, c.customer_name, c.customer_code, c.mobile, c.address, u.full_name as created_by_name
          FROM hold_sales_master h
          LEFT JOIN customers c ON h.customer_id = c.id
          LEFT JOIN users u ON h.created_by = u.id
          WHERE $where LIMIT 1";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    die("<div style='text-align:center;padding:50px;font-family:sans-serif;'><h3>Hold Bill not found or has been deleted.</h3><a href='add_sale.php'>Go Back to Add Sale</a></div>");
}

$hold = mysqli_fetch_assoc($result);

// Fetch hold details
$details_query = "SELECT hd.*, p.product_name, p.product_code 
                  FROM hold_sales_details hd
                  LEFT JOIN products p ON hd.product_id = p.id
                  WHERE hd.hold_id = {$hold['id']}";
$details_result = mysqli_query($conn, $details_query);

$products_list = [];
if($details_result) {
    while($detail = mysqli_fetch_assoc($details_result)) {
        $products_list[] = $detail;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hold Bill - <?php echo htmlspecialchars($hold['hold_no']); ?></title>
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

        /* ===== Meta Section ===== */
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
        .meta-customer-details {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.6;
        }
        .meta-invoice-box {
            flex: 0 0 auto;
            min-width: 280px;
            display: grid;
            grid-template-columns: auto auto;
            gap: 5px 14px;
            background: #f8faf9;
            border: 1px solid #d1e7dd;
            border-left: 3px solid #1e7e34;
            border-radius: 4px;
            padding: 10px 14px;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-value {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            text-align: right;
        }
        .meta-value.invoice-number {
            font-size: 13px;
            font-weight: 700;
            color: #1e7e34;
        }

        /* ===== Table ===== */
        .invoice-table-wrapper { margin-bottom: 16px; }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .invoice-table thead th {
            background-color: #1e7e34;
            color: #fff;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #155724;
            padding: 7px 6px;
        }
        .invoice-table .size-header { border-bottom: none; }
        .invoice-table .size-subheader th {
            background-color: #155724;
            font-size: 10px;
            padding: 4px 6px;
            border-top: none;
        }
        .invoice-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .product-group-row td {
            background-color: #eaf5eb !important;
            border-top: 2px solid #1e7e34 !important;
            border-bottom: 1px solid #c3e6cb !important;
            padding: 8px 12px;
        }
        .product-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            color: #155724;
        }
        .product-group-badge {
            background: #1e7e34;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-right: 6px;
            display: inline-block;
        }
        .product-subtotal-row td {
            background-color: #f8faf9 !important;
            border-top: 1px solid #d1e7dd !important;
            border-bottom: 2px solid #cbd5e1 !important;
            font-weight: 700;
            color: #1b4332;
            padding: 7px 8px;
            font-size: 12px;
        }
        .table-footer td, .table-footer {
            background-color: #e8f5e9 !important;
            border-top: 2px solid #1e7e34 !important;
            font-weight: 700;
            font-size: 13px;
            color: #155724;
            padding: 8px 8px;
        }

        /* ===== Totals ===== */
        .invoice-bottom-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-top: 12px;
        }
        .bottom-left-notes {
            flex: 1;
            font-size: 11px;
            color: #4b5563;
        }
        .notes-box {
            background: #fdf8e2;
            border-left: 3px solid #f59e0b;
            padding: 8px 12px;
            border-radius: 3px;
            margin-bottom: 8px;
        }
        .bottom-right-payments {
            flex: 0 0 320px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            font-size: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .payment-row:last-child { border-bottom: none; }
        .payment-row.grand-total-row {
            background: #1e7e34;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .payment-label { font-weight: 500; }
        .payment-value { font-weight: 700; }

        /* ===== Footer ===== */
        .invoice-footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-block { text-align: center; }
        .sig-line { width: 160px; border-bottom: 1px solid #374151; margin-bottom: 4px; }
        .sig-label { font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: 600; }
        .thank-you { font-size: 12px; font-weight: 600; color: #1e7e34; }

        /* ===== Action Bar ===== */
        .action-bar {
            max-width: 1100px;
            margin: 16px auto;
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .btn-action {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-print { background: #1e7e34; color: #fff; }
        .btn-print:hover { background: #155724; }
        .btn-pdf { background: #dc3545; color: #fff; }
        .btn-pdf:hover { background: #b02a37; }
        .btn-exit { background: #6c757d; color: #fff; }
        .btn-exit:hover { background: #5a6268; }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <button class="btn-action btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
    <button class="btn-action btn-pdf" id="downloadPDF"><i class="fas fa-file-pdf"></i> Download PDF</button>
    <button class="btn-action btn-exit" onclick="window.close();"><i class="fas fa-times"></i> Close</button>
</div>

<div class="invoice-container" id="invoiceContent">
    <!-- Company Header -->
    <div class="company-header">
        <div class="brand-left">
            <div class="brand-logo">FG</div>
            <div>
                <div class="brand-name"><?php echo $software_name; ?></div>
                <div class="brand-tagline">Quality Glass & Aluminium Works & Solutions</div>
            </div>
        </div>
        <div class="company-contact-info">
            <div class="contact-line"><i class="fas fa-phone-alt"></i> 0300-1234567 / 0321-7654321</div>
            <div class="contact-line"><i class="fas fa-map-marker-alt"></i> Main Market, Glass Bazaar, City</div>
            <div class="contact-line"><i class="fas fa-envelope"></i> info@faisalglass.store</div>
        </div>
    </div>

    <!-- Title -->
    <div class="invoice-title">
        <div class="title-bar title-bar-left"></div>
        <span>HOLD BILL / ESTIMATE</span>
        <div class="title-bar title-bar-right"></div>
    </div>

    <!-- Meta Section -->
    <div class="invoice-meta-container">
        <div class="meta-customer-box">
            <div class="meta-sub-header">Customer Details</div>
            <div class="meta-customer-name"><?php if(!empty($hold['walk_in_customer_name'])): ?><?php echo htmlspecialchars($hold['walk_in_customer_name']); ?><?php else: ?><?php echo !empty($hold['customer_name']) ? htmlspecialchars($hold['customer_name']) : 'Walk-In Customer'; ?><?php endif; ?></div>
            <div class="meta-customer-details">
                <?php if(empty($hold['walk_in_customer_name']) && !empty($hold['customer_code'])): ?><div><strong>Code:</strong> <?php echo htmlspecialchars($hold['customer_code']); ?></div><?php endif; ?>
                <?php if(!empty($hold['walk_in_customer_phone'])): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($hold['walk_in_customer_phone']); ?></div><?php elseif(empty($hold['walk_in_customer_name']) && !empty($hold['mobile'])): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($hold['mobile']); ?></div><?php endif; ?>
                <?php if(!empty($hold['address'])): ?><div><strong>Address:</strong> <?php echo htmlspecialchars($hold['address']); ?></div><?php endif; ?>
            </div>
        </div>
        <div class="meta-invoice-box">
            <div class="meta-label">Hold Bill No:</div>
            <div class="meta-value invoice-number"><?php echo htmlspecialchars($hold['hold_no']); ?></div>
            <div class="meta-label">Date:</div>
            <div class="meta-value"><?php echo date('d-m-Y', strtotime($hold['hold_date'])); ?></div>
            <div class="meta-label">Status:</div>
            <div class="meta-value text-warning font-weight-bold" style="text-transform: uppercase;"><?php echo htmlspecialchars($hold['status']); ?></div>
            <?php if(!empty($hold['created_by_name'])): ?>
            <div class="meta-label">Created By:</div>
            <div class="meta-value"><?php echo htmlspecialchars($hold['created_by_name']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="invoice-table-wrapper">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">SR</th>
                    <th colspan="2" class="size-header">CLIENT SIZE (INCH)</th>
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
                    $grouped_products[$pid]['subtotal_area'] += floatval($detail['total_area'] ?? ($detail['area'] * ($detail['quantity'] ?? 1)));
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

                <?php foreach($group['items'] as $detail):
                    $client_h = floatval($detail['client_height']);
                    $client_w = floatval($detail['client_width']);
                    $qty = floatval($detail['quantity']);
                    $total_area = floatval($detail['total_area'] ?? ($detail['area'] * $qty));
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
    </div>

    <!-- Totals -->
    <div class="invoice-bottom-grid">
        <div class="bottom-left-notes">
            <?php if(!empty($hold['remarks'])): ?>
            <div class="notes-box">
                <strong>Remarks / Notes:</strong> <?php echo nl2br(htmlspecialchars($hold['remarks'])); ?>
            </div>
            <?php endif; ?>
            <div style="font-size: 11px; color: #6b7280; font-style: italic;">
                * Note: This is an unfinalized estimate / hold bill. Prices and availability are subject to final confirmation.
            </div>
        </div>
        <div class="bottom-right-payments">
            <div class="payment-row">
                <span class="payment-label">Subtotal:</span>
                <span class="payment-value">Rs <?php echo number_format($hold['subtotal'], 2); ?></span>
            </div>
            <?php if($hold['discount_amount'] > 0): ?>
            <div class="payment-row">
                <span class="payment-label">Discount (<?php echo $hold['discount_percentage']; ?>%):</span>
                <span class="payment-value text-danger">- Rs <?php echo number_format($hold['discount_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if($hold['other_charges'] > 0): ?>
            <div class="payment-row">
                <span class="payment-label">Other Charges:</span>
                <span class="payment-value">+ Rs <?php echo number_format($hold['other_charges'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="payment-row grand-total-row">
                <span class="payment-label">Grand Total:</span>
                <span class="payment-value">Rs <?php echo number_format($hold['grand_total'], 2); ?></span>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#downloadPDF').on('click', function() {
        const element = document.getElementById('invoiceContent');
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Hold_Bill_<?php echo $hold['hold_no']; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, letterRendering: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });

    <?php if(isset($_GET['download']) && $_GET['download'] == 1): ?>
    setTimeout(function() {
        $('#downloadPDF').trigger('click');
    }, 600);
    <?php endif; ?>
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
