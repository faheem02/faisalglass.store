<?php
/**
 * View Product List Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all products with search, filter, and pagination
 * Page: View Product List
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Product List";

// Handle Product Status Toggle (Activate/Deactivate)
if(isset($_GET['toggle_status'])) {
    $product_id = intval($_GET['toggle_status']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;
    
    $update_query = "UPDATE products SET status = $new_status WHERE id = $product_id";
    if(mysqli_query($conn, $update_query)) {
        $msg = $new_status == 1 ? "Product activated successfully!" : "Product deactivated successfully!";
        echo "<script>Swal.fire({title: 'Success!', text: '$msg', icon: 'success', confirmButtonColor: '#1e7e34'});</script>";
    } else {
        echo "<script>Swal.fire({title: 'Error!', text: 'Failed to update status!', icon: 'error', confirmButtonColor: '#1e7e34'});</script>";
    }
}

// Handle Delete Product
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if product has sales or purchases
    $check_sales = "SELECT id FROM sale_details WHERE product_id = $delete_id LIMIT 1";
    $sales_result = mysqli_query($conn, $check_sales);
    
    $check_purchases = "SELECT id FROM purchase_details WHERE product_id = $delete_id LIMIT 1";
    $purchases_result = mysqli_query($conn, $check_purchases);
    
    if(mysqli_num_rows($sales_result) > 0 || mysqli_num_rows($purchases_result) > 0) {
        echo "<script>Swal.fire({title: 'Cannot Delete!', text: 'This product has sales or purchase records!', icon: 'warning', confirmButtonColor: '#1e7e34'});</script>";
    } else {
        // Delete opening stock records first
        mysqli_query($conn, "DELETE FROM opening_stock WHERE product_id = $delete_id");
        // Delete inventory ledger records
        mysqli_query($conn, "DELETE FROM inventory_ledger WHERE product_id = $delete_id");
        // Delete product sizes
        mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $delete_id");
        // Delete product
        $delete_query = "DELETE FROM products WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            echo "<script>Swal.fire({title: 'Deleted!', text: 'Product deleted successfully!', icon: 'success', confirmButtonColor: '#1e7e34'}).then(() => { window.location.href = 'addproduct.php'; });</script>";
        } else {
            echo "<script>Swal.fire({title: 'Error!', text: 'Failed to delete product!', icon: 'error', confirmButtonColor: '#1e7e34'});</script>";
        }
    }
}

// Get current stock for a product
function getCurrentStock($conn, $product_id) {
    $query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['balance_qty']);
    }
    return 0;
}

// Fetch all products with joins
$products_query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name, 
                   (SELECT COUNT(*) FROM product_sizes ps WHERE ps.product_id = p.id) as size_count
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN companies comp ON p.company_id = comp.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

// Fetch sizes for all products (for size-wise display)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: white;
        }
        .btn-green:hover {
            background-color: #155724;
            border-color: #155724;
            color: white;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .form-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .stock-badge {
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .stock-high {
            background-color: #d4edda;
            color: #155724;
        }
        .stock-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        .stock-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        .product-code {
            font-family: monospace;
            font-weight: bold;
            color: #0066cc;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.4px;
            line-height: 1;
            border: 1px solid transparent;
        }
        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-badge-active {
            background: linear-gradient(135deg, #eafaf1, #d4f5df);
            color: #14532d;
            border-color: #b7e4c7;
            box-shadow: 0 1px 4px rgba(20, 83, 45, 0.12);
        }
        .status-badge-active .dot {
            background: #16a34a;
            box-shadow: 0 0 0 2.5px rgba(22, 163, 74, 0.2);
        }
        .status-badge-inactive {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #7f1d1d;
            border-color: #fecaca;
            box-shadow: 0 1px 4px rgba(127, 29, 29, 0.12);
        }
        .status-badge-inactive .dot {
            background: #dc2626;
            box-shadow: 0 0 0 2.5px rgba(220, 38, 38, 0.18);
        }
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .action-buttons .btn {
            margin: 2px;
        }
        .table thead th {
            background-color: #1e7e34;
            color: white;
            font-weight: 600;
        }
        .size-badge {
            margin: 2px 2px 2px 0;
            font-size: 11px;
            padding: 4px 8px;
        }
        .child-size-table {
            background: #f8f9fc;
            width: 100%;
            margin: 0;
        }
        .child-size-table th {
            background: #e3f2fd;
            color: #0d47a1;
            font-weight: 600;
            font-size: 13px;
        }
        .size-detail-card {
            background: #f8f9fc;
            border-left: 4px solid #1e7e34;
            padding: 10px 15px;
            border-radius: 6px;
        }
        @media print {
            body { background: #fff !important; }
            #wrapper { margin: 0 !important; }
            #accordionSidebar, .topbar, .sticky-footer, .scroll-to-top,
            .no-print, .modal, .modal-backdrop, .dataTables_length,
            .dataTables_filter, .dataTables_info, .dataTables_paginate,
            .dataTables_wrapper > .row:first-child, .dataTables_wrapper > .row:last-child {
                display: none !important;
            }
            .container-fluid { padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; margin-bottom: 8px !important; }
            .card-header-custom, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-list text-success mr-2"></i> View Product List
            </h1>
            <div class="no-print">
                <a href="addproducts.php" class="btn btn-green">
                    <i class="fas fa-plus-circle mr-1"></i> Add New Product
                </a>
                <button type="button" class="btn btn-outline-success ml-2" onclick="window.open('print_product_list.php', '_blank', 'width=1200,height=750')">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card no-print">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Products
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Search</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or code...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Category</label>
                            <select id="categoryFilter" class="form-control">
                                <option value="">All Categories</option>
                                <?php
                                $cat_query = "SELECT id, category_name FROM categories WHERE status = 1";
                                $cat_result = mysqli_query($conn, $cat_query);
                                while($cat = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='" . htmlspecialchars($cat['category_name']) . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Company</label>
                            <select id="companyFilter" class="form-control">
                                <option value="">All Companies</option>
                                <?php
                                $comp_query = "SELECT id, company_name FROM companies WHERE status = 1";
                                $comp_result = mysqli_query($conn, $comp_query);
                                while($comp = mysqli_fetch_assoc($comp_result)) {
                                    echo "<option value='" . htmlspecialchars($comp['company_name']) . "'>" . htmlspecialchars($comp['company_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-chart-line"></i> Stock Status</label>
                            <select id="stockFilter" class="form-control">
                                <option value="">All Stock</option>
                                <option value="high">High Stock (>100)</option>
                                <option value="medium">Medium Stock (50-100)</option>
                                <option value="low">Low Stock (<50)</option>
                                <option value="out">Out of Stock (0)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-boxes mr-2"></i> Products List
                <span class="float-right">
                    <i class="fas fa-chart-line mr-1"></i> 
                    Total Products: <strong id="totalCount">0</strong>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="productsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>ID</th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Company</th>
                                <th>Unit</th>
                                <th>Purchase Price</th>
                                <th>Sale Price</th>
                                <th>Current Stock</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_stock_value = 0;
                            while($product = mysqli_fetch_assoc($products_result)): 
                                $current_stock = getCurrentStock($conn, $product['id']);
                                $stock_value = $current_stock * $product['purchase_price'];
                                $total_stock_value += $stock_value;
                                $product_sizes = $sizes_map[$product['id']] ?? [];
                                
                                // Determine stock badge class
                                if($current_stock <= 0) {
                                    $stock_class = "stock-low";
                                    $stock_text = "Out of Stock";
                                } elseif($current_stock < 50) {
                                    $stock_class = "stock-low";
                                    $stock_text = "Low Stock";
                                } elseif($current_stock <= 100) {
                                    $stock_class = "stock-medium";
                                    $stock_text = "Medium Stock";
                                } else {
                                    $stock_class = "stock-high";
                                    $stock_text = "High Stock";
                                }
                            ?>
                            <tr class="product-row">
                                <td class="text-center">
                                    <?php if(count($product_sizes) > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success expand-row" data-product-id="<?php echo $product['id']; ?>" title="View Sizes">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['id']; ?></td>
                                <td class="product-code"><?php echo $product['product_code']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <?php if(count($product_sizes) > 0): ?>
                                        <br>
                                        <?php foreach($product_sizes as $sz): ?>
                                            <span class="badge badge-success size-badge" title="Area: <?php echo number_format($sz['area_sqft'], 2); ?> sq ft | Opening: <?php echo number_format($sz['opening_pieces'], 2); ?> pcs">
                                                <i class="fas fa-arrows-alt"></i> <?php echo htmlspecialchars($sz['size_label']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <br><small class="text-success"><i class="fas fa-boxes"></i> <?php echo count($product_sizes); ?> size(s)</small>
                                    <?php endif; ?>
                                    <?php if($product['location_rack']): ?>
                                        <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo $product['location_rack']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['short_name']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($product['purchase_price']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($product['sale_price']); ?></td>
                                <td class="text-center">
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo number_format($current_stock, 2); ?> <?php echo $product['short_name']; ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo formatCurrency($stock_value); ?></td>
                                <td class="text-center">
                                    <?php if($product['status'] == 1): ?>
                                        <span class="status-badge status-badge-active"><span class="dot"></span> Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-badge-inactive"><span class="dot"></span> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewProduct(<?php echo $product['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm <?php echo $product['status'] == 1 ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="toggleStatus(<?php echo $product['id']; ?>, <?php echo $product['status']; ?>)" 
                                            title="<?php echo $product['status'] == 1 ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas <?php echo $product['status'] == 1 ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $product['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;">
                                <td></td>
                                <td colspan="9" class="text-right"><strong>Total Stock Value:</strong></td>
                                <td class="text-right"><strong><?php echo formatCurrency($total_stock_value); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
            </div>
        </div>
    </footer>
    
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #0066cc); color: white;">
                <h5 class="modal-title"><i class="fas fa-box"></i> Product Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="productDetails">
                <!-- Product details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
var productsTable;

// Sizes data per product (product_id -> array of sizes)
var productSizesData = <?php
$ps_json = [];
foreach($sizes_map as $pid => $sizes) {
    $ps_json[$pid] = array_map(function($s) {
        return [
            'size_label' => $s['size_label'],
            'length_feet' => number_format($s['length_feet'], 2),
            'width_feet' => number_format($s['width_feet'], 2),
            'area_sqft' => number_format($s['area_sqft'], 2),
            'opening_pieces' => number_format($s['opening_pieces'], 2)
        ];
    }, $sizes);
}
echo json_encode($ps_json);
?>;

$(document).ready(function() {
    // Initialize DataTable
    productsTable = $('#productsTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No products found"
        },
        "drawCallback": function() {
            $('#totalCount').text(this.api().rows().count());
        }
    });
    
    // Update total count
    $('#totalCount').text(productsTable.rows().count());
    
    // Custom search filter
    $('#searchInput').on('keyup', function() {
        productsTable.search(this.value).draw();
    });
    
    // Category filter
    $('#categoryFilter').on('change', function() {
        productsTable.column(4).search(this.value).draw();
    });
    
    // Company filter
    $('#companyFilter').on('change', function() {
        productsTable.column(5).search(this.value).draw();
    });
    
    // Stock filter (custom)
    $('#stockFilter').on('change', function() {
        var filter = this.value;
        // Custom filtering for stock status
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if(filter === '') return true;
            
            var stockText = data[9]; // Current Stock column
            var stockMatch = stockText.match(/([\d,\.]+)/);
            var stockValue = stockMatch ? parseFloat(stockMatch[1].replace(/,/g, '')) : 0;
            
            if(filter === 'high' && stockValue > 100) return true;
            if(filter === 'medium' && stockValue >= 50 && stockValue <= 100) return true;
            if(filter === 'low' && stockValue > 0 && stockValue < 50) return true;
            if(filter === 'out' && stockValue === 0) return true;
            
            return false;
        });
        productsTable.draw();
        $.fn.dataTable.ext.search.pop();
    });
    
    // Expand/collapse sizes child row
    $(document).on('click', '.expand-row', function() {
        var btn = $(this);
        var productId = btn.data('product-id');
        var tr = btn.closest('tr');
        var row = productsTable.row(tr);
        
        if(row.child.isShown()) {
            row.child.hide();
            btn.html('<i class="fas fa-chevron-down"></i>');
        } else {
            row.child(renderSizeDetail(productId)).show();
            btn.html('<i class="fas fa-chevron-up"></i>');
        }
    });
});

// Render child row with size details
function renderSizeDetail(productId) {
    var sizes = productSizesData[productId] || [];
    if(sizes.length === 0) return '<div class="size-detail-card"><em>No sizes found for this product.</em></div>';
    
    var html = '<div class="size-detail-card">';
    html += '<h6 class="text-success font-weight-bold"><i class="fas fa-arrows-alt"></i> Product Sizes</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-bordered table-sm child-size-table">';
    html += '<thead><tr><th>Size</th><th>Length (Feet)</th><th>Width (Feet)</th><th>Area (sq ft)</th><th>Opening Pieces</th></tr></thead>';
    html += '<tbody>';
    $.each(sizes, function(i, sz) {
        html += '<tr>';
        html += '<td><strong>' + sz.size_label + '</strong></td>';
        html += '<td class="text-right">' + sz.length_feet + '</td>';
        html += '<td class="text-right">' + sz.width_feet + '</td>';
        html += '<td class="text-right">' + sz.area_sqft + '</td>';
        html += '<td class="text-right">' + sz.opening_pieces + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

// View Product Details
function viewProduct(id) {
    $.ajax({
        url: 'get_product_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            $('#productDetails').html(response);
            $('#viewProductModal').modal('show');
        },
        error: function() {
            Swal.fire({ title: 'Error!', text: 'Failed to load product details!', icon: 'error', confirmButtonColor: '#1e7e34' });
        }
    });
}

// Edit Product
function editProduct(id) {
    window.location.href = 'edit_product.php?id=' + id;
}

// Toggle Status
function toggleStatus(id, currentStatus) {
    var action = currentStatus == 1 ? 'deactivate' : 'activate';
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to " + action + " this product!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, ' + action + ' it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'addproduct.php?toggle_status=' + id + '&current_status=' + currentStatus;
        }
    });
}

// Confirm Delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This product will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'addproduct.php?delete_id=' + id;
        }
    });
}
</script>

</body>
</html>

<?php mysqli_close($conn); ?>