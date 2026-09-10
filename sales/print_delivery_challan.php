<?php
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

$query = "SELECT s.*, c.customer_name, c.customer_code, c.mobile, c.address
          FROM sale_master s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE s.invoice_no = '$invoice_no'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: view_invoice.php");
    exit();
}

$sale = mysqli_fetch_assoc($result);

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
    <title>Work Sheet - <?php echo $invoice_no; ?></title>
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
            .product-group-row td { background-color: #eaf5eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .product-subtotal-row td { background-color: #f8faf9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .table-footer td, .table-footer { background-color: #e8f5e9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #1a1a1a;
            font-size: 17px;
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
            width: 62px; height: 62px; background: #1e7e34; color: #fff;
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 800; letter-spacing: 1px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .brand-name { font-size: 25px; font-weight: 700; color: #14532d; letter-spacing: 0.3px; text-transform: uppercase; line-height: 1.2; }
        .brand-tagline { font-size: 14px; color: #374151; letter-spacing: 0.5px; }
        .company-contact-info { text-align: right; font-size: 14px; color: #1f2937; line-height: 1.8; }
        .company-contact-info .contact-line { white-space: nowrap; }
        .company-contact-info i { color: #1e7e34; width: 18px; }
        .invoice-title {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            font-size: 22px; font-weight: 800; color: #14532d; letter-spacing: 4px; margin: 8px 0 16px;
        }
        .invoice-title .title-bar { flex: 0 0 70px; height: 3px; border-radius: 2px; }
        .title-bar-left { background: linear-gradient(to right, transparent, #1e7e34); }
        .title-bar-right { background: linear-gradient(to left, transparent, #1e7e34); }
        .invoice-meta-container {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 24px; padding: 10px 4px 14px 4px; margin-bottom: 14px; border-bottom: 1.5px solid #d1e7dd;
        }
        .meta-customer-box { flex: 1; max-width: 58%; }
        .meta-sub-header { font-size: 14px; font-weight: 700; color: #1e7e34; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 4px; }
        .meta-customer-name { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 3px; line-height: 1.3; }
        .meta-customer-code { font-size: 14px; color: #4b5563; margin-bottom: 5px; font-weight: 600; }
        .meta-customer-detail { font-size: 15px; color: #1f2937; line-height: 1.6; display: flex; align-items: baseline; gap: 8px; }
        .meta-customer-detail i { color: #1e7e34; width: 18px; font-size: 14px; }
        .meta-invoice-box { min-width: 270px; }
        .meta-invoice-table { width: 100%; border-collapse: collapse; font-size: 15px; }
        .meta-invoice-table td { padding: 5px 8px; border-bottom: 1px solid #f0f3f2; }
        .meta-invoice-table tr:last-child td { border-bottom: none; }
        .meta-invoice-table .meta-label { font-weight: 600; color: #4b5563; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; width: 45%; }
        .meta-invoice-table .meta-value { font-weight: 700; color: #111827; text-align: right; }
        .invoice-table { width: 100%; border-collapse: collapse; font-size: 15px; margin-top: 4px; }
        .invoice-table th {
            background: #1e7e34; color: #fff; padding: 10px 8px; text-align: center;
            border: 1px solid #166d2e; font-weight: 700; letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .invoice-table td { padding: 10px 8px; border: 1px solid #d1d5db; vertical-align: middle; }
        .invoice-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .invoice-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .invoice-table .text-center { text-align: center; }
        .size-subheader th { background: #0f6bb5; border: 1px solid #0f6bb5; font-size: 13px; padding: 8px; font-weight: 600; }
        .table-footer { background: #e8f5e9; font-weight: 700; border-top: 2px solid #1e7e34; font-size: 15px; }
        .product-group-row td {
            background: #eaf5eb !important; border-top: 2px solid #1e7e34 !important;
            border-bottom: 1px solid #c3e6cb !important; padding: 10px 12px !important;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .product-group-title { display: flex; align-items: center; justify-content: space-between; font-size: 15px; font-weight: 700; color: #155724; }
        .product-group-badge {
            background: #1e7e34; color: #fff; padding: 3px 9px; border-radius: 4px;
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
            margin-right: 8px; display: inline-block;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .product-subtotal-row td {
            background: #f8faf9 !important; border-top: 1px solid #d1e7dd !important;
            border-bottom: 2px solid #cbd5e1 !important; font-weight: 700; font-size: 14px;
            color: #1b4332; padding: 8px 10px !important;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .invoice-footer {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: 26px; padding-top: 14px; border-top: 1px solid #e5e7eb;
        }
        .signature-block { text-align: center; width: 240px; }
        .sig-line { border-bottom: 2px solid #1f2937; height: 38px; margin-bottom: 4px; }
        .sig-label { font-size: 14px; color: #374151; letter-spacing: 0.5px; font-weight: 600; }
        .thank-you { font-size: 15px; font-weight: 700; color: #1e7e34; letter-spacing: 0.5px; text-align: right; }
        .action-bar {
            position: fixed; bottom: 0; left: 0; right: 0; text-align: center;
            padding: 12px; background: rgba(255,255,255,0.96);
            box-shadow: 0 -2px 12px rgba(0,0,0,0.12); z-index: 1000;
        }
        .btn-action {
            padding: 10px 24px; margin: 0 8px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 600; font-size: 14px; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15);
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

    <div class="invoice-title">
        <span class="title-bar title-bar-left"></span>
        WORK SHEET
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="invoice-meta-container">
        <div class="meta-customer-box">
            <div class="meta-sub-header">DELIVER TO</div>
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
        </div>

        <div class="meta-invoice-box">
            <table class="meta-invoice-table">
                <tr>
                    <td class="meta-label">Invoice No:</td>
                    <td class="meta-value font-weight-bold text-success" style="font-size: 17px;"><?php echo $sale['invoice_no']; ?></td>
                </tr>
                <tr>
                    <td class="meta-label">Date:</td>
                    <td class="meta-value"><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 6%;">SR #</th>
                <th colspan="2">ACTUAL SIZE (INCH)</th>
                <th rowspan="2" style="width: 10%;">QTY</th>
                <th rowspan="2" style="width: 16%;">Total Area (sq ft)</th>
            </tr>
            <tr class="size-subheader">
                <th style="width: 14%;">HEIGHT</th>
                <th style="width: 14%;">WIDTH</th>
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
                        'subtotal_area' => 0
                    ];
                }
                $grouped_products[$pid]['items'][] = $detail;
                $grouped_products[$pid]['subtotal_qty'] += floatval($detail['quantity'] ?? 0);
                $grouped_products[$pid]['subtotal_area'] += floatval($detail['area'] ?? 0);
            }

            $sr = 1;
            $total_total_qty = 0;
            $total_total_area = 0;

            foreach($grouped_products as $group):
                $total_total_qty += $group['subtotal_qty'];
                $total_total_area += $group['subtotal_area'];
            ?>
            <tr class="product-group-row">
                <td colspan="5">
                    <div class="product-group-title">
                        <span>
                            <span class="product-group-badge">Product</span>
                            <strong><?php echo htmlspecialchars($group['product_name']); ?></strong>
                            <?php if(!empty($group['product_code'])): ?>
                                <small class="text-muted" style="font-weight: normal;">(<?php echo htmlspecialchars($group['product_code']); ?>)</small>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 13px; font-weight: normal; color: #155724;">
                            <?php echo count($group['items']); ?> <?php echo count($group['items']) === 1 ? 'size' : 'sizes'; ?>
                        </span>
                    </div>
                </td>
            </tr>
            <?php foreach($group['items'] as $detail):
                $client_h = floatval($detail['client_height']);
                $client_w = floatval($detail['client_width']);
                $qty = floatval($detail['quantity']);
                $total_area = floatval($detail['area']);
            ?>
            <tr>
                <td class="text-center"><?php echo $sr++; ?></td>
                <td class="text-center"><?php echo $client_h > 0 ? number_format($client_h, 1) : '-'; ?></td>
                <td class="text-center"><?php echo $client_w > 0 ? number_format($client_w, 1) : '-'; ?></td>
                <td class="text-center"><?php echo number_format($qty, 0); ?></td>
                <td class="text-right"><?php echo number_format($total_area, 2); ?></td>
            </tr>
            <?php endforeach; ?>

            <tr class="product-subtotal-row">
                <td colspan="3" class="text-right">
                    <strong>Total (<?php echo htmlspecialchars($group['product_name']); ?>):</strong>
                </td>
                <td class="text-center"><strong><?php echo number_format($group['subtotal_qty'], 0); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($group['subtotal_area'], 2); ?> sq ft</strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="3" class="text-right"><strong>Grand Totals:</strong></td>
                <td class="text-center"><strong><?php echo number_format($total_total_qty, 0); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($total_total_area, 2); ?> sq ft</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="invoice-footer">
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Receiver's Signature</div>
        </div>
        <div class="thank-you">Faisal Glass &amp; Aluminum Centre</div>
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
            filename: 'Work_Sheet_<?php echo $invoice_no; ?>.pdf',
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