<?php
/**
 * Print Quotation Page - Clean Totals Outside Table
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

if($quotation_id <= 0) {
    header("Location: view_quotation.php");
    exit();
}

// Fetch quotation master
$query = "SELECT q.*, c.customer_name, c.customer_code, c.mobile, c.address, c.email 
          FROM quotation_master q
          LEFT JOIN customers c ON q.customer_id = c.id
          WHERE q.id = $quotation_id";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: view_quotation.php");
    exit();
}

$quotation = mysqli_fetch_assoc($result);

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
            border-radius: 2px;
        }
        .title-bar-left { background: linear-gradient(to right, transparent, #1e7e34); }
        .title-bar-right { background: linear-gradient(to left, transparent, #1e7e34); }

        /* ===== Info Grid ===== */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }
        .info-item {
            display: flex;
            align-items: baseline;
            gap: 8px;
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
            min-width: 92px;
            text-transform: uppercase;
        }
        .info-value {
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        /* ===== Products Table ===== */
        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-top: 4px;
        }
        .quotation-table th {
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

        /* ===== Totals ===== */
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding: 8px 15px;
            background: #e8f5e9;
            border: 1px solid #a7d3a7;
            border-radius: 5px;
            font-weight: 600;
            font-size: 12.5px;
        }
        .grand-total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding: 11px 16px;
            background: #1e7e34;
            color: #fff;
            border-radius: 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .grand-total { font-size: 16px; font-weight: 700; }
        .grand-total-section .remarks-text { font-size: 12px; }

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
            <span class="info-label">Client Name</span>
            <span class="info-value"><?php echo trim(htmlspecialchars($quotation['customer_name'])); ?></span>
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
            <span class="info-label">Mobile</span>
            <span class="info-value"><?php echo $quotation['mobile']; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Valid Until</span>
            <span class="info-value"><?php echo $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '-'; ?></span>
        </div>
    </div>
    
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
                <td colspan="9" class="text-center">No products found for this quotation.</td>
            </tr>
            <?php else: ?>
                <?php foreach($products_list as $detail): 
                    $client_h = floatval($detail['client_height'] ?? 0);
                    $client_w = floatval($detail['client_width'] ?? 0);
                    $qty = floatval($detail['quantity'] ?? 0);
                    $unit_area = floatval($detail['area'] ?? 0);
                    $total_area = $unit_area * $qty;
                    $unit_price = floatval($detail['unit_price'] ?? 0);
                    $amount = floatval($detail['net_amount'] ?? 0);
                    
                    $total_total_area += $total_area;
                    $total_total_price += $amount;
                ?>
                <tr>
                    <td class="text-center"><?php echo $sr++; ?></td>
                    <td class="text-center"><?php echo number_format($client_h, 1); ?></td>
                    <td class="text-center"><?php echo number_format($client_w, 1); ?></td>
                    <td class="text-center"><?php echo number_format($qty, 0); ?></td>
                    <td class="text-right"><?php echo number_format($total_area, 2); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($detail['product_name']); ?></td>
                    <td class="text-right"><?php echo number_format($unit_price, 0); ?></td>
                    <td class="text-right"><?php echo number_format($amount, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Totals Row (outside table) -->
    <div class="totals-row">
        <span>Total Area: <?php echo number_format($total_total_area, 2); ?> sq ft</span>
        <span>Total Price: <?php echo number_format($total_total_price, 2); ?> PKR</span>
    </div>
    
    <!-- Grand Total and Remarks -->
    <div class="grand-total-section">
        <div class="grand-total">Grand Total: <?php echo number_format($quotation['grand_total'], 2); ?> PKR</div>
        <?php if(!empty($quotation['remarks'])): ?>
        <div class="remarks-text"><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($quotation['remarks'])); ?></div>
        <?php endif; ?>
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