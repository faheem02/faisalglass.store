<?php
/**
 * Get Invoice Details AJAX
 * Faysal Glass And Aluminium Centre
 * 
 * Returns purchase invoice details for modal view
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p class='text-danger'>Unauthorized access!</p>";
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_GET['id'])) {
    $purchase_id = intval($_GET['id']);
    
    // Fetch purchase master
    $query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile, s.address, s.ntn 
              FROM purchase_master p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.id = $purchase_id";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $purchase = mysqli_fetch_assoc($result);
        
        // Fetch purchase details
        $details_query = "SELECT pd.*, pr.product_name, pr.product_code, u.short_name as unit_name
                         FROM purchase_details pd
                         LEFT JOIN products pr ON pd.product_id = pr.id
                         LEFT JOIN units u ON pr.unit_id = u.id
                         WHERE pd.purchase_id = $purchase_id";
        $details_result = mysqli_query($conn, $details_query);
        ?>
        
        <div class="container-fluid">
            <!-- Invoice Header -->
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h3 class="text-success"><?php echo $software_name; ?></h3>
                    <p><?php echo $company_address; ?><br>Phone: <?php echo $company_phone; ?> | Email: <?php echo $company_email; ?></p>
                    <h4 class="mt-3">PURCHASE INVOICE</h4>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <strong>Supplier Information</strong>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
                            <p><strong>Code:</strong> <?php echo $purchase['supplier_code']; ?></p>
                            <p><strong>Mobile:</strong> <?php echo $purchase['mobile']; ?></p>
                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($purchase['address'])); ?></p>
                            <?php if($purchase['ntn']): ?>
                            <p><strong>NTN:</strong> <?php echo $purchase['ntn']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <strong>Invoice Information</strong>
                        </div>
                        <div class="card-body">
                            <p><strong>Invoice No:</strong> <?php echo $purchase['invoice_no']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?></p>
                            <p><strong>Payment Type:</strong> <?php echo ucfirst($purchase['payment_type']); ?></p>
                            <?php if($purchase['reference_no']): ?>
                            <p><strong>Reference No:</strong> <?php echo $purchase['reference_no']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <table class="table table-bordered">
                        <thead style="background-color: #1e7e34; color: white;">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Client Size</th>
                                <th>Std H</th>
                                <th>Std W</th>
                                <th>UOM</th>
                                <th>QTY</th>
                                <th>Unit Price</th>
                                <th>Area</th>
                                <th>Amount</th>
                                <th>Dis%</th>
                                <th>Net Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while($detail = mysqli_fetch_assoc($details_result)): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($detail['product_name']); ?><br><small><?php echo $detail['product_code']; ?></small></td>
                                <td><?php echo $detail['client_height'] ? $detail['client_height'] . ' x ' . $detail['client_width'] : '-'; ?></td>
                                <td><?php echo $detail['std_height'] ?: '-'; ?></td>
                                <td><?php echo $detail['std_width'] ?: '-'; ?></td>
                                <td><?php echo $detail['uom'] ?: '-'; ?></td>
                                <td class="text-right"><?php echo number_format($detail['quantity'], 2); ?></td>
                                <td class="text-right"><?php echo formatCurrency($detail['unit_price']); ?></td>
                                <td class="text-right"><?php echo number_format($detail['area'], 2); ?> sq ft</td>
                                <td class="text-right"><?php echo formatCurrency($detail['amount']); ?></td>
                                <td class="text-right"><?php echo $detail['discount_percentage']; ?>%</td>
                                <td class="text-right"><strong><?php echo formatCurrency($detail['net_amount']); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="row">
                <div class="col-6 offset-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="60%">Subtotal:</th>
                            <td class="text-right"><?php echo formatCurrency($purchase['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <th>Discount (<?php echo $purchase['discount_percentage']; ?>%):</th>
                            <td class="text-right text-danger">- <?php echo formatCurrency($purchase['discount_amount']); ?></td>
                        </tr>
                        <tr>
                            <th>Other Charges:</th>
                            <td class="text-right">+ <?php echo formatCurrency($purchase['other_charges']); ?></td>
                        </tr>
                        <tr style="background: #e8f5e9;">
                            <th><strong>Grand Total:</strong></th>
                            <td class="text-right"><strong><?php echo formatCurrency($purchase['grand_total']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Paid Amount:</th>
                            <td class="text-right text-success"><?php echo formatCurrency($purchase['paid_amount']); ?></td>
                        </tr>
                        <tr>
                            <th>Remaining Amount:</th>
                            <td class="text-right text-danger"><?php echo formatCurrency($purchase['remaining_amount']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if($purchase['remarks']): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-secondary">
                        <strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($purchase['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="text-muted">Thank you for your business!</p>
                </div>
            </div>
        </div>
        
        <?php
    } else {
        echo "<p class='text-danger'>Invoice not found!</p>";
    }
}

mysqli_close($conn);
?>