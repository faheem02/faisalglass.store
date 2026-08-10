<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Manage Bank Accounts";
$success_msg = '';
$error_msg = '';

// Handle Add/Edit Bank
if(isset($_POST['save_bank'])) {
    $bank_name = mysqli_real_escape_string($conn, trim($_POST['bank_name']));
    $account_title = mysqli_real_escape_string($conn, trim($_POST['account_title']));
    $account_number = mysqli_real_escape_string($conn, trim($_POST['account_number']));
    $opening_balance = floatval($_POST['opening_balance']);
    $bank_id = isset($_POST['bank_id']) ? intval($_POST['bank_id']) : 0;

    if(empty($bank_name)) {
        $error_msg = "Bank name is required!";
    } elseif($bank_id > 0) {
        // Get old bank data to compare opening balance
        $old_q = mysqli_query($conn, "SELECT opening_balance, current_balance FROM bank_accounts WHERE id = $bank_id");
        $old = mysqli_fetch_assoc($old_q);
        $old_opening = $old['opening_balance'] ?? 0;
        $diff = $opening_balance - $old_opening;

        $update = "UPDATE bank_accounts SET bank_name='$bank_name', account_title='$account_title', account_number='$account_number',
                   opening_balance = $opening_balance, current_balance = current_balance + $diff WHERE id=$bank_id";
        if(mysqli_query($conn, $update)) {
            // Update bank_book opening entry
            $ob_q = mysqli_query($conn, "SELECT id, balance FROM bank_book WHERE bank_account_id = $bank_id AND reference_type = 'OPENING' LIMIT 1");
            if(mysqli_num_rows($ob_q) > 0) {
                $ob = mysqli_fetch_assoc($ob_q);
                mysqli_query($conn, "UPDATE bank_book SET debit = $opening_balance, balance = $opening_balance WHERE id = {$ob['id']}");
            } elseif($opening_balance > 0) {
                $desc = "Opening Balance - $bank_name";
                mysqli_query($conn, "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, balance, created_at) VALUES (CURDATE(), $bank_id, 'OPENING', $bank_id, '$desc', $opening_balance, $opening_balance, NOW())");
            }
            $success_msg = "Bank updated successfully!";
        } else {
            $error_msg = "Failed to update bank: " . mysqli_error($conn);
        }
    } else {
        $insert = "INSERT INTO bank_accounts (bank_name, account_title, account_number, opening_balance, current_balance, status)
                   VALUES ('$bank_name', '$account_title', '$account_number', $opening_balance, $opening_balance, 1)";
        if(mysqli_query($conn, $insert)) {
            $new_id = mysqli_insert_id($conn);
            if($opening_balance > 0) {
                $desc = "Opening Balance - $bank_name";
                $bal_query = "INSERT INTO bank_book (date, bank_account_id, reference_type, reference_id, description, debit, balance, created_at)
                              VALUES (CURDATE(), $new_id, 'OPENING', $new_id, '$desc', $opening_balance, $opening_balance, NOW())";
                mysqli_query($conn, $bal_query);
            }
            $success_msg = "Bank added successfully!";
        } else {
            $error_msg = "Failed to add bank: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Bank
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $check = "SELECT id FROM bank_book WHERE bank_account_id = $delete_id AND reference_type != 'OPENING' LIMIT 1";
    $check_result = mysqli_query($conn, $check);
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Cannot delete! This bank has transaction records.";
    } else {
        mysqli_query($conn, "DELETE FROM bank_book WHERE bank_account_id = $delete_id");
        mysqli_query($conn, "DELETE FROM bank_accounts WHERE id = $delete_id");
        $success_msg = "Bank deleted successfully!";
    }
}

// Handle Toggle Status
if(isset($_GET['toggle_status'])) {
    $bank_id = intval($_GET['toggle_status']);
    $current = intval($_GET['current_status']);
    $new_status = $current == 1 ? 0 : 1;
    $update = "UPDATE bank_accounts SET status = $new_status WHERE id = $bank_id";
    if(mysqli_query($conn, $update)) {
        $success_msg = $new_status == 1 ? "Bank activated!" : "Bank deactivated!";
    } else {
        $error_msg = "Failed to update status!";
    }
}

// Fetch all banks
$banks_query = "SELECT * FROM bank_accounts ORDER BY id ASC";
$banks_result = mysqli_query($conn, $banks_query);

// Fetch bank for editing
$edit_bank = null;
if(isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT * FROM bank_accounts WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_bank = mysqli_fetch_assoc($edit_result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .bank-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .bank-header-custom { background: linear-gradient(135deg, #1e7e34, #4e73df); color: white; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 13px; white-space: nowrap; }
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .badge-status { font-size: 12px; padding: 5px 10px; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow" style="background: linear-gradient(135deg, #1e7e34, #4e73df);">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" style="color: white;"><i class="fa fa-bars"></i></button>
                    <span style="color: white; font-weight: 600;"><i class="fas fa-university mr-2"></i> <?php echo $page_title; ?></span>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 text-white"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow">
                                <a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt mr-2 text-danger"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <?php if($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>
                    <?php if($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-5">
                            <div class="bank-card">
                                <h5 class="mb-3" style="color: #1e7e34;">
                                    <i class="fas fa-<?php echo $edit_bank ? 'edit' : 'plus-circle'; ?> mr-2"></i>
                                    <?php echo $edit_bank ? 'Edit Bank Account' : 'Add New Bank Account'; ?>
                                </h5>
                                <form method="POST" action="">
                                    <?php if($edit_bank): ?>
                                    <input type="hidden" name="bank_id" value="<?php echo $edit_bank['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label><i class="fas fa-university text-success mr-1"></i> Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control" required
                                               value="<?php echo $edit_bank ? htmlspecialchars($edit_bank['bank_name']) : ''; ?>"
                                               placeholder="e.g., Bank AL Habib">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-user-tie text-info mr-1"></i> Account Title</label>
                                        <input type="text" name="account_title" class="form-control"
                                               value="<?php echo $edit_bank ? htmlspecialchars($edit_bank['account_title']) : ''; ?>"
                                               placeholder="Account holder name">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-credit-card text-primary mr-1"></i> Account Number</label>
                                        <input type="text" name="account_number" class="form-control"
                                               value="<?php echo $edit_bank ? htmlspecialchars($edit_bank['account_number']) : ''; ?>"
                                               placeholder="Account / Mobile number">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-coins text-warning mr-1"></i> Opening Balance (₨)</label>
                                        <input type="number" step="0.01" name="opening_balance" class="form-control"
                                               value="<?php echo $edit_bank ? htmlspecialchars($edit_bank['opening_balance']) : '0'; ?>" min="0">
                                        <small class="text-muted">Set initial balance for this bank account</small>
                                    </div>
                                    <button type="submit" name="save_bank" class="btn btn-green btn-block">
                                        <i class="fas fa-save mr-1"></i> <?php echo $edit_bank ? 'Update Bank' : 'Save Bank'; ?>
                                    </button>
                                    <?php if($edit_bank): ?>
                                    <a href="manage_banks.php" class="btn btn-secondary btn-block mt-2">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="bank-card">
                                <h5 class="mb-3" style="color: #1e7e34;">
                                    <i class="fas fa-list mr-2"></i> Bank Accounts List
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="bankTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Bank Name</th>
                                                <th>Account Title</th>
                                                <th>Account Number</th>
                                                <th class="text-right">Balance</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sno = 0;
                                            if($banks_result && mysqli_num_rows($banks_result) > 0):
                                            while($bank = mysqli_fetch_assoc($banks_result)): $sno++;
                                            ?>
                                            <tr>
                                                <td><?php echo $sno; ?></td>
                                                <td><strong><?php echo htmlspecialchars($bank['bank_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($bank['account_title'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($bank['account_number'] ?? '-'); ?></td>
                                                <td class="text-right"><?php echo formatCurrency($bank['current_balance']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $bank['status'] == 1 ? 'success' : 'secondary'; ?> badge-status">
                                                        <?php echo $bank['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="manage_banks.php?edit_id=<?php echo $bank['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_banks.php?toggle_status=<?php echo $bank['id']; ?>&current_status=<?php echo $bank['status']; ?>" class="btn btn-sm btn-<?php echo $bank['status'] == 1 ? 'warning' : 'success'; ?>" title="<?php echo $bank['status'] == 1 ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $bank['status'] == 1 ? 'ban' : 'check'; ?>"></i>
                                                    </a>
                                                    <a href="manage_banks.php?delete_id=<?php echo $bank['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this bank?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; else: ?>
                                            <tr><td colspan="7" class="text-center py-4">No bank accounts added yet.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#bankTable').DataTable({
                "pageLength": 25,
                "order": [[0, 'asc']],
                "language": { "search": "Search:", "lengthMenu": "Show _MENU_ entries" }
            });
        });
    </script>
</body>
</html>
