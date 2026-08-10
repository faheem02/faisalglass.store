<?php
/**
 * Get Payment Details AJAX
 * Faysal Glass And Aluminium Centre
 * 
 * Returns payment details for modal view
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p class='text-danger'>Unauthorized access!</p>";
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);
    
    $query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile, s.company_name,
              ba.bank_name, ba.account_title, ba.account_number
              FROM supplier_payments p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              LEFT JOIN bank_accounts ba ON p.bank_account_id = ba.id
              WHERE p.id = $payment_id";
    
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $payment = mysqli_fetch_assoc($result);
        ?>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Payment ID:</th>
                        <td><?php echo $payment['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Payment Date:</th>
                        <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Method:</th>
                        <td>
                            <?php if($payment['payment_method'] == 'cash'): ?>
                                <span class="badge-cash">Cash</span>
                            <?php else: ?>
                                <span class="badge-bank">Bank Transfer / Cheque</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if($payment['reference_no']): ?>
                    <tr>
                        <th>Reference No:</th>
                        <td><?php echo htmlspecialchars($payment['reference_no']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Amount:</th>
                        <td class="text-danger font-weight-bold"><?php echo formatCurrency($payment['amount']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Supplier Code:</th>
                        <td><?php echo $payment['supplier_code']; ?></td>
                    </tr>
                    <tr>
                        <th>Supplier Name:</th>
                        <td><?php echo htmlspecialchars($payment['supplier_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Company:</th>
                        <td><?php echo htmlspecialchars($payment['company_name']) ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Mobile:</th>
                        <td><?php echo $payment['mobile']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if($payment['payment_method'] == 'bank'): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-university"></i> <strong>Bank Details:</strong><br>
                    Bank: <?php echo htmlspecialchars($payment['bank_name']); ?><br>
                    Account: <?php echo htmlspecialchars($payment['account_title']); ?> (<?php echo $payment['account_number']; ?>)
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($payment['remarks']): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-secondary">
                    <i class="fas fa-comment"></i> <strong>Remarks:</strong><br>
                    <?php echo nl2br(htmlspecialchars($payment['remarks'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
    } else {
        echo "<p class='text-danger'>Payment record not found!</p>";
    }
}

mysqli_close($conn);
?>