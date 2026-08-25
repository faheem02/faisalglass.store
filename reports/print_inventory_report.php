<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$company_filter = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$product_search = isset($_GET['product_search']) ? $_GET['product_search'] : '';

if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
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

$products_query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN companies comp ON p.company_id = comp.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1";

if($category_filter > 0) {
    $products_query .= " AND p.category_id = $category_filter";
}
if($company_filter > 0) {
    $products_query .= " AND p.company_id = $company_filter";
}
if(!empty($product_search)) {
    $products_query .= " AND (p.product_name LIKE '%$product_search%' OR p.product_code LIKE '%$product_search%')";
}

$products_query .= " ORDER BY p.product_name ASC";
$products_result = mysqli_query($conn, $products_query);

$inventory_data = [];
$total_stock_value = 0;
$total_products = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;
$total_opening = 0;
$total_purchased = 0;
$total_sold = 0;
$total_current = 0;

while($product = mysqli_fetch_assoc($products_result)) {
    $product_id = $product['id'];

    $sizes_query = "SELECT * FROM product_sizes WHERE product_id = $product_id ORDER BY id ASC";
    $sizes_result = mysqli_query($conn, $sizes_query);
    $sizes_list = [];
    while($sz = mysqli_fetch_assoc($sizes_result)) {
        $sizes_list[] = $sz;
    }

    $opening_query = "SELECT COALESCE(SUM(qty_in) - SUM(qty_out), 0) as opening_stock
                      FROM inventory_ledger 
                      WHERE product_id = $product_id AND date < '$from_date'";
    $opening_result = mysqli_query($conn, $opening_query);
    $opening_stock = floatval(mysqli_fetch_assoc($opening_result)['opening_stock']);

    $purchase_query = "SELECT COALESCE(SUM(qty_in), 0) as purchased_qty
                       FROM inventory_ledger 
                       WHERE product_id = $product_id 
                       AND reference_type = 'PURCHASE'
                       AND date BETWEEN '$from_date' AND '$to_date'";
    $purchase_result = mysqli_query($conn, $purchase_query);
    $purchased_qty = floatval(mysqli_fetch_assoc($purchase_result)['purchased_qty']);

    $sale_query = "SELECT COALESCE(SUM(qty_out), 0) as sold_qty
                   FROM inventory_ledger 
                   WHERE product_id = $product_id 
                   AND reference_type IN ('SALE', 'QUOTATION')
                   AND date BETWEEN '$from_date' AND '$to_date'";
    $sale_result = mysqli_query($conn, $sale_query);
    $sold_qty = floatval(mysqli_fetch_assoc($sale_result)['sold_qty']);

    $current_stock_query = "SELECT COALESCE(SUM(qty_in) - SUM(qty_out), 0) as current_stock
                            FROM inventory_ledger 
                            WHERE product_id = $product_id";
    $current_stock_result = mysqli_query($conn, $current_stock_query);
    $current_stock = floatval(mysqli_fetch_assoc($current_stock_result)['current_stock']);

    $stock_value = $current_stock * floatval($product['purchase_price']);
    $total_stock_value += $stock_value;
    $total_products++;

    if($current_stock <= 0) {
        $out_of_stock_count++;
    } elseif($current_stock <= $product['min_stock_alert']) {
        $low_stock_count++;
    }

    $base_row = [
        'id' => $product_id,
        'product_code' => $product['product_code'],
        'product_name' => $product['product_name'],
        'category_name' => $product['category_name'],
        'company_name' => $product['company_name'],
        'unit_name' => $product['unit_name'] ?? 'Pcs',
        'purchase_price' => floatval($product['purchase_price']),
        'sale_price' => floatval($product['sale_price']),
        'min_stock_alert' => $product['min_stock_alert'],
        'purchased_qty' => $purchased_qty,
        'sold_qty' => $sold_qty,
        'current_stock' => $current_stock,
        'stock_value' => $stock_value,
        'is_size_row' => false,
        'size_label' => null,
        'size_pieces' => null,
        'size_area' => null
    ];

    if(count($sizes_list) > 1) {
        foreach($sizes_list as $sz) {
            $sz_opening_query = "SELECT COALESCE(SUM(quantity),0) as area, COALESCE(SUM(pieces),0) as pieces
                                 FROM opening_stock 
                                 WHERE product_id = $product_id AND product_size_id = " . $sz['id'];
            $sz_opening_result = mysqli_query($conn, $sz_opening_query);
            $sz_open = mysqli_fetch_assoc($sz_opening_result);

            $sz_pieces_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                                FROM opening_stock 
                                WHERE product_id = $product_id AND product_size_id = " . $sz['id'];
            $sz_pieces_result = mysqli_query($conn, $sz_pieces_query);
            $sz_pieces = mysqli_fetch_assoc($sz_pieces_result);

            $row = $base_row;
            $row['is_size_row'] = true;
            $row['size_label'] = $sz['size_label'];
            $row['size_pieces'] = floatval($sz_pieces['pieces']);
            $row['size_area'] = floatval($sz_open['area']);
            $row['opening_stock'] = $row['size_area'];
            $sz_rate = floatval($sz['purchase_rate'] ?? 0);
            if($sz_rate > 0) $row['purchase_price'] = $sz_rate;
            $inventory_data[] = $row;
            $total_opening += $row['opening_stock'];
            $total_purchased += $row['purchased_qty'];
            $total_sold += $row['sold_qty'];
            $total_current += $row['current_stock'];
        }
    } else {
        $row = $base_row;
        if(count($sizes_list) == 1) {
            $row['size_label'] = $sizes_list[0]['size_label'];
            $sz_opening_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                                 FROM opening_stock 
                                 WHERE product_id = $product_id AND product_size_id = " . $sizes_list[0]['id'];
            $sz_opening_result = mysqli_query($conn, $sz_opening_query);
            $sz_open = mysqli_fetch_assoc($sz_opening_result);
            $row['size_pieces'] = floatval($sz_open['pieces']);
        } else {
            if(floatval($product['length_feet']) > 0 && floatval($product['width_feet']) > 0) {
                $row['size_label'] = rtrim(rtrim(number_format($product['length_feet'], 2), '0'), '.')
                                   . ' x ' . rtrim(rtrim(number_format($product['width_feet'], 2), '0'), '.') . ' ft';
            }
            $os_query = "SELECT COALESCE(SUM(pieces),0) as pieces
                         FROM opening_stock 
                         WHERE product_id = $product_id AND product_size_id IS NULL";
            $os_result = mysqli_query($conn, $os_query);
            $os_row = mysqli_fetch_assoc($os_result);
            $row['size_pieces'] = floatval($os_row['pieces']);
        }
        $row['opening_stock'] = $opening_stock;
        $inventory_data[] = $row;
        $total_opening += $row['opening_stock'];
        $total_purchased += $row['purchased_qty'];
        $total_sold += $row['sold_qty'];
        $total_current += $row['current_stock'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
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
        INVENTORY REPORT
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
            <span class="info-label">Generated By</span>
            <span class="info-value"><?php echo htmlspecialchars($user_name); ?></span>
        </div>
    </div>

    <div class="balance-row">
        <div class="balance-item">
            <div class="bal-label">Total Products</div>
            <div class="bal-value" style="color: #1e7e34;"><?php echo $total_products; ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Stock Value</div>
            <div class="bal-value" style="color: #4e73df;"><?php echo formatCurrency($total_stock_value); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Low Stock Products</div>
            <div class="bal-value" style="color: #f6c23e;"><?php echo $low_stock_count; ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Out of Stock</div>
            <div class="bal-value" style="color: #e74a3b;"><?php echo $out_of_stock_count; ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="9%">Product Code</th>
                <th width="13%">Product Name</th>
                <th width="9%">Size</th>
                <th width="9%">Category</th>
                <th width="9%">Company</th>
                <th width="7%" class="text-right">Opening</th>
                <th width="7%" class="text-right">Purchased</th>
                <th width="7%" class="text-right">Sold</th>
                <th width="7%" class="text-right">Current</th>
                <th width="8%" class="text-right">Unit Price</th>
                <th width="8%" class="text-right">Stock Value</th>
                <th width="7%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($inventory_data)): ?>
                <?php foreach($inventory_data as $item): ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($item['product_code']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            <small style="color: #6b7280; display: block;"><?php echo htmlspecialchars($item['unit_name']); ?></small>
                        </td>
                        <td class="text-center">
                            <?php if($item['size_label']): ?>
                                <span class="type-badge tb-opening"><?php echo htmlspecialchars($item['size_label']); ?></span>
                                <?php if($item['size_pieces'] !== null): ?>
                                    <small style="color: #6b7280; display: block;"><?php echo number_format($item['size_pieces'], 0); ?> pcs</small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['company_name'] ?? 'N/A'); ?></td>
                        <td class="text-right"><?php echo number_format($item['opening_stock'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['purchased_qty'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['sold_qty'], 2); ?></td>
                        <td class="text-right"><strong><?php echo number_format($item['current_stock'], 2); ?></strong></td>
                        <td class="text-right"><?php echo formatCurrency($item['purchase_price']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($item['stock_value']); ?></td>
                        <td class="text-center">
                            <?php if($item['current_stock'] <= 0): ?>
                                <span class="type-badge" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">Out</span>
                            <?php elseif($item['current_stock'] <= $item['min_stock_alert']): ?>
                                <span class="type-badge tb-adjustment">Low</span>
                            <?php else: ?>
                                <span class="type-badge tb-payment">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12" class="text-center" style="padding:20px;">No products found</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="5" class="text-right">Totals:</td>
                <td class="text-right"><?php echo number_format($total_opening, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_purchased, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_sold, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_current, 2); ?></td>
                <td class="text-right">-</td>
                <td class="text-right"><?php echo formatCurrency($total_stock_value); ?></td>
                <td></td>
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
