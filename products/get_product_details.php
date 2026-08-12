<?php
/**
 * Get Product Details AJAX
 * Faysal Glass And Aluminium Centre
 * 
 * Returns product details for modal view
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p class='text-danger'>Unauthorized access!</p>";
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Fetch product details
    $query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name 
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN companies comp ON p.company_id = comp.id
              LEFT JOIN units u ON p.unit_id = u.id
              WHERE p.id = $product_id";
    
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        
        // Get current stock
        $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
        $stock_result = mysqli_query($conn, $stock_query);
        $current_stock = 0;
        if(mysqli_num_rows($stock_result) > 0) {
            $stock_row = mysqli_fetch_assoc($stock_result);
            $current_stock = $stock_row['balance_qty'];
        }
        
        // Get opening stock info
        $opening_query = "SELECT * FROM opening_stock WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
        $opening_result = mysqli_query($conn, $opening_query);
        $opening_stock = mysqli_fetch_assoc($opening_result);
        
        // Get sizes for this product
        $sizes_query = "SELECT ps.*, 
                        (SELECT COALESCE(SUM(os.pieces), 0) FROM opening_stock os WHERE os.product_size_id = ps.id) as opening_pieces
                        FROM product_sizes ps WHERE ps.product_id = $product_id ORDER BY ps.id ASC";
        $sizes_result = mysqli_query($conn, $sizes_query);
        $sizes = [];
        while($sz = mysqli_fetch_assoc($sizes_result)) {
            $sizes[] = $sz;
        }
        
        ?>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Product Code:</th>
                        <td><strong class="product-code"><?php echo $product['product_code']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Product Name:</th>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Category:</th>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Company/Brand:</th>
                        <td><?php echo htmlspecialchars($product['company_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Unit:</th>
                        <td><?php echo htmlspecialchars($product['unit_name'] . ' (' . $product['short_name'] . ')'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Purchase Price:</th>
                        <td class="text-right"><?php echo formatCurrency($product['purchase_price']); ?></td>
                    </tr>
                    <tr>
                        <th>Sale Price:</th>
                        <td class="text-right"><?php echo formatCurrency($product['sale_price']); ?></td>
                    </tr>
                    <tr>
                        <th>Current Stock:</th>
                        <td class="text-right">
                            <strong><?php echo number_format($current_stock, 2); ?> <?php echo $product['short_name']; ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <th>Stock Value:</th>
                        <td class="text-right"><?php echo formatCurrency($current_stock * $product['purchase_price']); ?></td>
                    </tr>
                    <tr>
                        <th>Min Stock Alert:</th>
                        <td class="text-right"><?php echo $product['min_stock_alert']; ?> <?php echo $product['short_name']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if($product['location_rack']): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo $product['location_rack']; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($opening_stock): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success">
                    <i class="fas fa-info-circle"></i> <strong>Opening Stock Info:</strong> 
                    Added on <?php echo date('d-m-Y', strtotime($opening_stock['date'])); ?> | 
                    Quantity: <?php echo number_format($opening_stock['quantity'], 2); ?> | 
                    Amount: <?php echo formatCurrency($opening_stock['total_amount']); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(count($sizes) > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <h6 class="text-success font-weight-bold"><i class="fas fa-arrows-alt"></i> Product Sizes</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Length (Feet)</th>
                                <th>Width (Feet)</th>
                                <th>Area (sq ft)</th>
                                <th>Opening Pieces</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sizes as $sz): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sz['size_label']); ?></strong></td>
                                <td class="text-right"><?php echo number_format($sz['length_feet'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['width_feet'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['area_sqft'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['opening_pieces'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
    } else {
        echo "<p class='text-danger'>Product not found!</p>";
    }
}

mysqli_close($conn);
?>