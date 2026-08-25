<?php
/**
 * Print Product List Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

function getCurrentStock($conn, $product_id) {
    $query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['balance_qty']);
    }
    return 0;
}

$products_query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name,
                   (SELECT COUNT(*) FROM product_sizes ps WHERE ps.product_id = p.id) as size_count
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN companies comp ON p.company_id = comp.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

$sizes_map = [];
$all_sizes_query = "SELECT ps.*,
                    (SELECT COALESCE(SUM(os.pieces), 0) FROM opening_stock os WHERE os.product_size_id = ps.id) as opening_pieces
                    FROM product_sizes ps ORDER BY ps.product_id, ps.id ASC";
$all_sizes_result = mysqli_query($conn, $all_sizes_query);
if($all_sizes_result) {
    while($sz = mysqli_fetch_assoc($all_sizes_result)) {
        $sizes_map[$sz['product_id']][] = $sz;
    }
}

$rows = [];
$total_stock_value = 0;
$total_products = 0;
while($product = mysqli_fetch_assoc($products_result)) {
    $product['current_stock'] = getCurrentStock($conn, $product['id']);
    $product['stock_value'] = $product['current_stock'] * $product['purchase_price'];
    $product['product_sizes'] = $sizes_map[$product['id']] ?? [];
    $total_stock_value += $product['stock_value'];
    $total_products++;
    $rows[] = $product;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .list-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4 landscape; margin: 12mm; }
            .list-table thead { display: table-header-group; }
            .list-table tr { page-break-inside: avoid; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #212529;
            font-size: 12.5px;
            line-height: 1.6;
        }
        .list-container {
            max-width: 1280px;
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
            font-size: 17px;
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
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .list-table td {
            padding: 6px 5px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .list-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .list-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .list-table .text-center { text-align: center; }
        .product-code { font-family: monospace; font-weight: 700; color: #0f6bb5; }
        .size-badge {
            display: inline-block;
            margin: 1px 2px 1px 0;
            font-size: 9.5px;
            padding: 2px 6px;
            background: #e8f5e9;
            color: #14532d;
            border: 1px solid #a7d3a7;
            border-radius: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .st-active { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .st-inactive { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .stock-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .stock-high { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .stock-medium { background: #fff3cd; color: #856404; border: 1px solid #ffe69c; }
        .stock-low { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
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
        PRODUCT LIST
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Total Products</span>
            <span class="info-value"><?php echo $total_products; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Stock Value</span>
            <span class="info-value">Rs <?php echo number_format($total_stock_value, 2); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Generated On</span>
            <span class="info-value"><?php echo date('d-m-Y'); ?></span>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="4%">SR</th>
                <th width="9%">Product Code</th>
                <th>Product Name</th>
                <th width="11%">Category</th>
                <th width="11%">Company</th>
                <th width="6%">Unit</th>
                <th width="9%">Purchase Price</th>
                <th width="9%">Sale Price</th>
                <th width="10%">Current Stock</th>
                <th width="9%">Stock Value</th>
                <th width="8%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($rows)): ?>
            <tr><td colspan="11" class="text-center">No products found.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach($rows as $product):
                    $current_stock = $product['current_stock'];
                    if($current_stock <= 0) {
                        $stock_class = "stock-low";
                    } elseif($current_stock < 50) {
                        $stock_class = "stock-low";
                    } elseif($current_stock <= 100) {
                        $stock_class = "stock-medium";
                    } else {
                        $stock_class = "stock-high";
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $sr++; ?></td>
                    <td class="product-code text-center"><?php echo $product['product_code']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                        <?php if(!empty($product['product_sizes'])): ?>
                            <br>
                            <?php foreach($product['product_sizes'] as $sz): ?>
                                <span class="size-badge"><?php echo htmlspecialchars($sz['size_label']); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if($product['location_rack']): ?>
                            <br><small class="text-muted">Rack: <?php echo htmlspecialchars($product['location_rack']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($product['company_name'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($product['short_name'] ?? ''); ?></td>
                    <td class="text-right"><?php echo number_format($product['purchase_price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($product['sale_price'], 2); ?></td>
                    <td class="text-center">
                        <span class="stock-badge <?php echo $stock_class; ?>">
                            <?php echo number_format($current_stock, 2); ?> <?php echo htmlspecialchars($product['short_name'] ?? ''); ?>
                        </span>
                    </td>
                    <td class="text-right"><?php echo number_format($product['stock_value'], 2); ?></td>
                    <td class="text-center">
                        <?php if($product['status'] == 1): ?>
                            <span class="status-badge st-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge st-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="9" class="text-right">Total Stock Value:</td>
                <td class="text-right"><?php echo number_format($total_stock_value, 2); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            <?php echo $total_products; ?> product(s) in this list
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
