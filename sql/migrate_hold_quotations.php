<?php
/**
 * One-time migration: move legacy status='hold' quotations into hold_quotations_* tables.
 * For holds that were posted (stock + customer ledger), reverse the posting
 * (a hold must never affect stock or ledger). Run once via CLI:
 *   php sql/migrate_hold_quotations.php
 */
include(__DIR__ . '/../includes/database.php');

mysqli_begin_transaction($conn);
try {
    $rows = mysqli_query($conn, "SELECT * FROM quotation_master WHERE status = 'hold' ORDER BY id");
    $migrated = 0;
    $reversed = 0;

    while ($q = mysqli_fetch_assoc($rows)) {
        $qid = intval($q['id']);

        // 1. Insert into hold_quotations_master
        $hold_no = 'HOLDQ-' . str_pad($qid, 5, '0', STR_PAD_LEFT);
        $stmt = mysqli_prepare($conn, "INSERT INTO hold_quotations_master
            (hold_no, hold_date, customer_id, valid_until, reference_no, subtotal, discount_percentage,
             discount_amount, other_charges, grand_total, remarks, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'hold', ?, ?)");
        $valid_until = $q['valid_until'] !== null ? $q['valid_until'] : null;
        $reference_no = $q['reference_no'] !== null ? $q['reference_no'] : '';
        $created_at = $q['created_at'] ? $q['created_at'] : date('Y-m-d H:i:s');
        $quotation_date = $q['quotation_date'];
        $customer_id_v = intval($q['customer_id']);
        $subtotal_v = $q['subtotal'];
        $discount_percentage_v = $q['discount_percentage'];
        $discount_amount_v = $q['discount_amount'];
        $other_charges_v = $q['other_charges'];
        $grand_total_v = $q['grand_total'];
        $remarks_v = $q['remarks'];
        $created_by_v = intval($q['created_by']);
        mysqli_stmt_bind_param($stmt, "ssissddddddsi",
            $hold_no, $quotation_date, $customer_id_v, $valid_until, $reference_no,
            $subtotal_v, $discount_percentage_v, $discount_amount_v, $other_charges_v,
            $grand_total_v, $remarks_v, $created_by_v, $created_at);
        mysqli_stmt_execute($stmt);
        $hold_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // 2. Copy details
        $det_q = mysqli_query($conn, "SELECT * FROM quotation_details WHERE quotation_id = $qid");
        while ($d = mysqli_fetch_assoc($det_q)) {
            $stmt_d = mysqli_prepare($conn, "INSERT INTO hold_quotations_details
                (hold_id, product_id, client_height, client_width, std_height, std_width, uom,
                 quantity, unit_price, area, amount, discount_percentage, discount_amount, net_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $std_h = $d['std_height'] !== null ? $d['std_height'] : '';
            $std_w = $d['std_width'] !== null ? $d['std_width'] : '';
            $d_product_id = intval($d['product_id']);
            $d_client_height = $d['client_height'];
            $d_client_width = $d['client_width'];
            $d_uom = $d['uom'];
            $d_quantity = $d['quantity'];
            $d_unit_price = $d['unit_price'];
            $d_area = $d['area'];
            $d_amount = $d['amount'];
            $d_discount_percentage = $d['discount_percentage'];
            $d_discount_amount = $d['discount_amount'];
            $d_net_amount = $d['net_amount'];
            mysqli_stmt_bind_param($stmt_d, "iidddssddddddd",
                $hold_id, $d_product_id, $d_client_height, $d_client_width,
                $std_h, $std_w, $d_uom, $d_quantity, $d_unit_price, $d_area,
                $d_amount, $d_discount_percentage, $d_discount_amount, $d_net_amount);
            mysqli_stmt_execute($stmt_d);
            mysqli_stmt_close($stmt_d);
        }

        // 3. Reverse any posted stock/ledger for this quotation
        $inv_q = mysqli_query($conn, "SELECT product_id, qty_out FROM inventory_ledger
                                      WHERE reference_type = 'QUOTATION' AND reference_id = $qid");
        $has_posting = false;
        while ($inv = mysqli_fetch_assoc($inv_q)) {
            $has_posting = true;
            $pid = intval($inv['product_id']);
            $area = floatval($inv['qty_out']);
            $bal_q = mysqli_query($conn, "SELECT balance_qty FROM inventory_ledger WHERE product_id = $pid ORDER BY id DESC LIMIT 1");
            $cur = 0;
            if ($bal_q && mysqli_num_rows($bal_q) > 0) $cur = floatval(mysqli_fetch_assoc($bal_q)['balance_qty']);
            $new_stock = $cur + $area;
            if (!mysqli_query($conn, "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks)
                                      VALUES (CURDATE(), $pid, 'ADJUSTMENT', $qid, $area, 0, $new_stock, 0, 0, 'Hold Quotation Migration - Stock Restore')")) {
                throw new Exception("Failed to restore stock for product $pid: " . mysqli_error($conn));
            }
            if (!mysqli_query($conn, "DELETE FROM inventory_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $qid AND product_id = $pid")) {
                throw new Exception("Failed to clean quotation stock entry: " . mysqli_error($conn));
            }
        }

        $led_q = mysqli_query($conn, "SELECT DISTINCT customer_id FROM customer_ledger
                                      WHERE reference_type = 'QUOTATION' AND reference_id = $qid");
        while ($l = mysqli_fetch_assoc($led_q)) {
            $has_posting = true;
            $cid = intval($l['customer_id']);
            if (!mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $qid")) {
                throw new Exception("Failed to remove ledger entry: " . mysqli_error($conn));
            }
            $bal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM customer_ledger WHERE customer_id = $cid"))['balance'];
            if (!mysqli_query($conn, "UPDATE customers SET current_balance = $bal WHERE id = $cid")) {
                throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
            }
        }

        if ($has_posting) $reversed++;

        // 4. Remove the legacy quotation
        if (!mysqli_query($conn, "DELETE FROM quotation_details WHERE quotation_id = $qid") ||
            !mysqli_query($conn, "DELETE FROM quotation_master WHERE id = $qid")) {
            throw new Exception("Failed to delete legacy quotation $qid: " . mysqli_error($conn));
        }

        $migrated++;
    }

    mysqli_commit($conn);
    echo "Migration complete: $migrated hold quotation(s) migrated, $reversed had posting reversed.\n";
} catch (Throwable $e) {
    mysqli_rollback($conn);
    echo "Migration FAILED (rolled back): " . $e->getMessage() . "\n";
    exit(1);
}
