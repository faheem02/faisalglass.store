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
            .invoice-container { margin: 0; box-shadow: none; }
            @page { size: A4; margin: 0.5cm; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif;
            background: #e9ecef; 
            font-size: 14px;
        }
        .invoice-container { 
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
        
        /* Invoice Title */
        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        /* Client Info Grid - No remaining line */
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
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        .invoice-table th {
            background: #1e7e34;
            color: white;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
        }
        .invoice-table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .invoice-table .text-right {
            text-align: right;
        }
        .invoice-table .text-center {
            text-align: center;
        }
        
        /* Size sub-header */
        .size-subheader th {
            background: #0066cc;
            font-size: 10px;
            padding: 5px;
        }
        
        /* Table footer for totals */
        .table-footer {
            background: #e8f5e9;
            font-weight: bold;
        }
        
        /* PAYMENT BREAKDOWN SECTION */
        .payment-breakdown {
            margin-top: 20px;
            margin-bottom: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            width: 350px;
            margin-left: auto;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .payment-label {
            font-weight: bold;
        }
        .payment-value {
            font-weight: normal;
        }
        .grand-total-row {
            font-weight: bold;
            border-top: 1px solid #ddd;
            margin-top: 5px;
            padding-top: 5px;
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
<div class="invoice-container" id="invoiceContent">
    
    <div class="company-header">
        <div class="company-name" style="font-weight: bold;">Faisal Glass & Aluminum Centre</div>
        <div class="company-tagline" style="font-weight: bold;">Deals in all kind of glass local & imported</div>
        <div class="company-contacts" style="font-weight: bold;">
            <span><i class="fas fa-phone-alt"></i> 0321-4186775</span>
            <span><i class="fas fa-mobile-alt"></i> 0322-8701098</span>
        </div>
        <div class="company-address" style="font-weight: bold;">Lajna Chowk Collage Road Township Lahore</div>
    </div>
    
    <!-- Invoice Title -->
    <div class="invoice-title">SALE INVOICE</div>
    
    <!-- Client Information Grid - Remaining line removed -->
    <div class="info-grid">
        <div class="info-label">CLIENT DETAIL:-</div>
        <div class="info-value"><?php echo trim(htmlspecialchars($sale['customer_name'])); ?></div>
        <div class="info-label">SALE AMOUNT:-</div>
        <div class="info-value">Rs <?php echo number_format($sale['grand_total'], 2); ?></div>
        
        <div class="info-label">ADDRESS:-</div>
        <div class="info-value"><?php echo !empty($sale['address']) ? trim(htmlspecialchars($sale['address'])) : '-'; ?></div>
        <div class="info-label">PAYMENT METHOD:-</div>
        <div class="info-value"><?php echo $payment_method_display; ?></div>
        
        <div class="info-label">DATE:-</div>
        <div class="info-value"><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></div>
        <div class="info-label">BILL NO:-</div>
        <div class="info-value"><?php echo $sale['invoice_no']; ?></div>
    </div>
    
    <?php if(!empty($sale['remarks'])): ?>
    <div style="margin-bottom: 12px; padding: 8px 12px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
        <strong>Remarks:</strong> <?php echo htmlspecialchars($sale['remarks']); ?>
    </div>
    <?php endif; ?>
    
    <!-- Products Table with Area & Total Area Columns -->
    <table class="invoice-table">
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
            <tr>
        </thead>
        <tbody>
            <?php 
            $sr = 1;
            $total_total_area = 0;
            $total_total_price = 0;
            
            // Display only glass products
            foreach($products_list as $detail):
                if($detail['product_id'] > 0):
                    $client_h = floatval($detail['client_height']);
                    $client_w = floatval($detail['client_width']);
                    $qty = floatval($detail['quantity']);
                    $total_area = floatval($detail['area']);
                    $rate = floatval($detail['rate']);
                    $amount = floatval($detail['amount']);
                    
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
                <td class="text-right"><?php echo number_format($rate, 0); ?></td>
                <td class="text-right"><?php echo number_format($amount, 2); ?></td>
            </tr>
            <?php 
                endif;
            endforeach; 
            ?>
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
    
    <!-- Payment Breakdown Section -->
    <div class="payment-breakdown">
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
    
    <!-- Footer -->
    <div class="footer">
        Thank you for your business!
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