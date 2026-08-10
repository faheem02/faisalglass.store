<?php
/**
 * Delete Payment Page
 * Faysal Glass And Aluminium Centre
 * 
 * Delete payment record and update supplier ledger
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

if(isset($_GET['id']) && isset($_GET['supplier_id']) && isset($_GET['amount'])) {
    $payment_id = intval($_GET['id']);
    $supplier_id = intval($_GET['supplier_id']);
    $amount = floatval($_GET['amount']);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from supplier_payments
        $delete_payment = "DELETE FROM supplier_payments WHERE id = $payment_id";
        if(!mysqli_query($conn, $delete_payment)) {
            throw new Exception("Failed to delete payment record");
        }
        
        // Delete from supplier_ledger
        $delete_ledger = "DELETE FROM supplier_ledger WHERE reference_type = 'PAYMENT' AND reference_id = $payment_id";
        if(!mysqli_query($conn, $delete_ledger)) {
            throw new Exception("Failed to delete ledger entry");
        }
        
        // Delete from cash_book or bank_book
        $delete_cash = "DELETE FROM cash_book WHERE reference_type = 'SUPPLIER_PAYMENT' AND reference_id = $payment_id";
        mysqli_query($conn, $delete_cash);
        
        $delete_bank = "DELETE FROM bank_book WHERE reference_type = 'SUPPLIER_PAYMENT' AND reference_id = $payment_id";
        mysqli_query($conn, $delete_bank);
        
        // Update supplier ledger balances after deletion
        // Recalculate all balances for this supplier
        $ledger_query = "SELECT id, debit, credit FROM supplier_ledger 
                         WHERE supplier_id = $supplier_id 
                         ORDER BY date ASC, id ASC";
        $ledger_result = mysqli_query($conn, $ledger_query);
        
        $running_balance = 0;
        while($entry = mysqli_fetch_assoc($ledger_result)) {
            $running_balance = $running_balance + $entry['credit'] - $entry['debit'];
            $update_balance = "UPDATE supplier_ledger SET balance = $running_balance WHERE id = {$entry['id']}";
            mysqli_query($conn, $update_balance);
        }
        
        // Update supplier current balance
        $update_supplier = "UPDATE suppliers SET current_balance = $running_balance WHERE id = $supplier_id";
        mysqli_query($conn, $update_supplier);
        
        mysqli_commit($conn);
        
        $_SESSION['success_msg'] = "Payment deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = $e->getMessage();
    }
}

header("Location: paid_amount.php");
exit();
?>