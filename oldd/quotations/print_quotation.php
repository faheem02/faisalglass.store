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
            .quotation-container { margin: 0; box-shadow: none; }
            @page { size: A4; margin: 0.5cm; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif;
            background: #e9ecef; 
            font-size: 14px;
        }
        .quotation-container { 
            max-width: 1100px; 
            margin: 20px auto; 
            background: white; 
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        /* Company Header */
        .company-header {
            text-align: center;
            border-bottom: 2px solid #1e7e34;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1e7e34;
        }
        .company-tagline {
            font-size: 12px;
            color: #666;
        }
        .company-contacts {
            font-size: 11px;
            margin-top: 5px;
        }
        .company-contacts span {
            margin: 0 10px;
        }
        .company-address {
            font-size: 10px;
            color: #555;
        }
        
        /* Quotation Title */
        .quotation-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        /* Client Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: auto 1fr auto 1fr;
            gap: 8px 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            align-items: baseline;
        }
        .info-label {
            font-weight: bold;
            white-space: nowrap;
        }
        .info-value {
            font-weight: normal;
            word-break: break-word;
        }
        
        /* Table Styles */
        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        .quotation-table th {
            background: #1e7e34;
            color: white;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
        }
        .quotation-table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .quotation-table .text-right {
            text-align: right;
        }
        .quotation-table .text-center {
            text-align: center;
        }
        
        /* Size sub-header */
        .size-subheader th {
            background: #0066cc;
            font-size: 10px;
            padding: 5px;
        }
        
        /* Totals row outside table */
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding: 8px 15px;
            background: #e8f5e9;
            border-radius: 5px;
            font-weight: bold;
        }
        
        /* Grand Total Section */
        .grand-total-section {
            margin-top: 15px;
            text-align: right;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
        }
        
        .action-bar {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            z-index: 1000;
        }
        .btn-action {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print { background: #1e7e34; color: white; }
        .btn-pdf { background: #dc3545; color: white; }
        .btn-exit { background: #0066cc; color: white; }
    </style>
</head>
<body>
<div class="quotation-container" id="quotationContent">
    
    <div class="company-header">
    <div class="company-name" style="font-weight: bold;">Faisal Glass & Aluminum Centre</div>
    <div class="company-tagline" style="font-weight: bold;">Deals in all kind of glass local & imported</div>
    <div class="company-contacts" style="font-weight: bold;">
        <span><i class="fas fa-phone-alt"></i> 0321-4186775</span>
        <span><i class="fas fa-mobile-alt"></i> 0322-8701098</span>
    </div>
    <div class="company-address" style="font-weight: bold;">Lajna Chowk Collage Road Township Lahore</div>
</div>
    
    <!-- Quotation Title -->
    <div class="quotation-title">QUOTATION</div>
    
    <!-- Client Information Grid -->
    <div class="info-grid">
        <div class="info-label">CLIENT DETAIL:-</div>
        <div class="info-value"><?php echo trim(htmlspecialchars($quotation['customer_name'])); ?></div>
        <div class="info-label">QUOTATION NO:-</div>
        <div class="info-value"><?php echo $quotation['quotation_no']; ?></div>
        
        <div class="info-label">ADDRESS:-</div>
        <div class="info-value"><?php echo !empty($quotation['address']) ? trim(htmlspecialchars($quotation['address'])) : '-'; ?></div>
        <div class="info-label">DATE:-</div>
        <div class="info-value"><?php echo date('d-m-Y', strtotime($quotation['quotation_date'])); ?></div>
        
        <div class="info-label">MOBILE:-</div>
        <div class="info-value"><?php echo $quotation['mobile']; ?></div>
        <div class="info-label">VALID UNTIL:-</div>
        <div class="info-value"><?php echo $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '-'; ?></div>
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
        <div class="grand-total"><strong>Grand Total:</strong> <?php echo number_format($quotation['grand_total'], 2); ?> PKR</div>
        <?php if(!empty($quotation['remarks'])): ?>
        <div style="margin-top: 10px;"><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($quotation['remarks'])); ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        Thank you for choosing us!
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