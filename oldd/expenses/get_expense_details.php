<?php
/**
 * Get Expense Details AJAX
 * Faysal Glass And Aluminium Centre
 * 
 * Returns expense details for modal view
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p class='text-danger'>Unauthorized access!</p>";
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_GET['id'])) {
    $expense_id = intval($_GET['id']);
    
    $query = "SELECT e.*, eh.head_name, ba.bank_name, ba.account_title, ba.account_number
              FROM expenses e
              LEFT JOIN expense_heads eh ON e.head_id = eh.id
              LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
              WHERE e.id = $expense_id";
    
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $expense = mysqli_fetch_assoc($result);
        ?>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Expense ID:</th>
                        <td><?php echo $expense['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td><?php echo date('d-m-Y', strtotime($expense['expense_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>Expense Head:</th>
                        <td><strong><?php echo htmlspecialchars($expense['head_name']); ?></strong></td>
                    </tr>
                    <?php if($expense['reference_no']): ?>
                    <tr>
                        <th>Reference No:</th>
                        <td><?php echo htmlspecialchars($expense['reference_no']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Amount:</th>
                        <td class="text-danger font-weight-bold"><?php echo formatCurrency($expense['amount']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="40%">Payment Method:</th>
                        <td>
                            <?php if($expense['payment_method'] == 'cash'): ?>
                                <span class="badge-cash">Cash</span>
                            <?php else: ?>
                                <span class="badge-bank">Bank Transfer / Cheque</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if($expense['payment_method'] == 'bank'): ?>
                    <tr>
                        <th>Bank:</th>
                        <td><?php echo htmlspecialchars($expense['bank_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Account:</th>
                        <td><?php echo htmlspecialchars($expense['account_title']); ?> (<?php echo $expense['account_number']; ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Created By:</th>
                        <td><?php echo $_SESSION['username']; ?> (<?php echo date('d-m-Y H:i:s', strtotime($expense['created_at'])); ?>)</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if($expense['remarks']): ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-secondary">
                    <i class="fas fa-comment"></i> <strong>Remarks:</strong><br>
                    <?php echo nl2br(htmlspecialchars($expense['remarks'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
    } else {
        echo "<p class='text-danger'>Expense record not found!</p>";
    }
}

mysqli_close($conn);
?>