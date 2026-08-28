<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

// Get current user info with fallback values
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

// Page Title
$page_title = "View Customers";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        /* Green + Blue Theme - No Dim Text */
        :root {
            --primary-green: #1e7e34;
            --primary-blue: #4e73df;
            --success-green: #28a745;
            --info-blue: #36b9cc;
            --dark-text: #2c3e50;
        }
        
        body {
            color: #2c3e50 !important;
            background-color: #f8f9fc;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
            color: white !important;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
            border: none;
        }
        
        .card-header h6 {
            color: white !important;
            font-weight: 600;
            margin: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #e8f5e9 0%, #e3f2fd 100%);
            color: #1e7e34 !important;
            font-weight: 700;
            border-bottom: 2px solid #1e7e34;
            padding: 12px 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            color: #2c3e50 !important;
            font-size: 14px;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
            transition: all 0.3s ease;
        }
        
        .badge-receivable {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
            min-width: 90px;
        }
        
        .badge-payable {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
            min-width: 90px;
        }
        
        .badge-active {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-inactive {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
            min-width: 90px;
        }
        
        .btn-action {
            margin: 2px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .dataTables_wrapper {
            padding: 0;
        }
        
        .dataTables_filter {
            margin-bottom: 20px;
        }
        
        .dataTables_filter label {
            font-weight: 600;
            color: #1e7e34;
        }
        
        .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            margin-left: 10px;
            width: 250px;
        }
        
        .dataTables_length select {
            border-radius: 20px;
            padding: 5px 10px;
        }
        
        .dataTables_info, .dataTables_paginate {
            margin-top: 20px;
            color: #2c3e50;
        }
        
        .paginate_button {
            border-radius: 8px !important;
            margin: 0 2px;
        }
        
        .paginate_button.current {
            background: linear-gradient(135deg, #1e7e34, #4e73df) !important;
            color: white !important;
            border: none !important;
        }
        
        /* Top Bar Styles */
        .topbar {
            height: 70px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .topbar .welcome-text {
            color: #1e7e34;
            font-weight: 500;
            font-size: 16px;
        }
        
        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .topbar .user-name {
            color: #4e73df;
            font-weight: 600;
            background: #e3f2fd;
            padding: 8px 15px;
            border-radius: 25px;
        }
        
        .topbar .logout-btn {
            color: #dc3545;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
            background: #fee;
        }
        
        .topbar .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        /* Customer Code Badge */
        .customer-code {
            background: #e8f5e9;
            color: #1e7e34;
            padding: 4px 8px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Action Buttons Group */
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn-action {
                width: 100%;
                margin: 2px 0;
            }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <div class="topbar">
                    <div class="welcome-text">
                        <i class="fas fa-store"></i> Welcome to <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name">
                            <i class="fas fa-user-circle"></i> 
                            <?php 
                            $display_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : $user_name);
                            $display_role = isset($_SESSION['role_name']) ? $_SESSION['role_name'] : (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : $user_role);
                            echo htmlspecialchars($display_name); 
                            ?> 
                            (<?php echo htmlspecialchars(ucfirst($display_role)); ?>)
                        </span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-users"></i> <?php echo $page_title; ?>
                        </h1>
                        <div>
                            <a href="add_customer.php" class="btn btn-success btn-sm shadow-sm">
                                <i class="fas fa-plus"></i> Add New Customer
                            </a>
                            <a href="#" id="exportBtn" class="btn btn-info btn-sm shadow-sm ml-2">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stats Cards Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalCustomers">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Receivable</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalReceivable">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Payable</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalPayable">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeCustomers">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-list"></i> Customer List
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="customersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="10%">Customer Code</th>
                                            <th width="20%">Customer Name</th>
                                            <th width="15%">Company Name</th>
                                            <th width="10%">Mobile</th>
                                            <th width="10%">Opening Balance</th>
                                            <th width="12%">Current Balance</th>
                                            <th width="8%">Status</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM customers ORDER BY id DESC";
                                        $result = mysqli_query($conn, $query);
                                        $counter = 1;
                                        $total_receivable = 0;
                                        $total_payable = 0;
                                        $total_customers = 0;
                                        $active_customers = 0;
                                        
                                        if(mysqli_num_rows($result) > 0):
                                            while($row = mysqli_fetch_assoc($result)):
                                                $total_customers++;
                                                if($row['status'] == 1) $active_customers++;
                                                
                                                $current_balance = floatval($row['current_balance']);
                                                $opening_balance = floatval($row['opening_balance']);
                                                $balance_type = $row['balance_type'];
                                                
                                                if($current_balance > 0) {
                                                    $total_receivable += $current_balance;
                                                } elseif($current_balance < 0) {
                                                    $total_payable += abs($current_balance);
                                                }
                                                
                                                // Determine display for opening balance
                                                if($opening_balance > 0) {
                                                    $opening_display = number_format($opening_balance, 2);
                                                    $opening_display .= ($balance_type == 'receivable') ? ' <span class="text-success">DR</span>' : ' <span class="text-danger">CR</span>';
                                                } else {
                                                    $opening_display = '<span class="text-muted">0.00</span>';
                                                }
                                                
                                                // Determine current balance display and class
                                                if($current_balance > 0) {
                                                    $balance_display = '₨ ' . number_format($current_balance, 2);
                                                    $balance_suffix = ' <small class="font-weight-bold">DR</small>';
                                                    $balance_color_class = 'text-danger';
                                                } elseif($current_balance < 0) {
                                                    $balance_display = '₨ ' . number_format(abs($current_balance), 2);
                                                    $balance_suffix = ' <small class="font-weight-bold">CR</small>';
                                                    $balance_color_class = 'text-success';
                                                } else {
                                                    $balance_display = '₨ 0.00';
                                                    $balance_suffix = '';
                                                    $balance_color_class = '';
                                                }
                                                
                                                $status_class = ($row['status'] == 1) ? 'badge-active' : 'badge-inactive';
                                                $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
                                        ?>
                                        <tr>
                                            <td><span class="badge badge-light"><?php echo $counter++; ?></span></td>
                                            <td><span class="customer-code"><?php echo htmlspecialchars($row['customer_code']); ?></span></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['company_name'] ?? '—'); ?></td>
                                            <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                            <td class="text-right"><?php echo $opening_display; ?></td>
                                            <td class="text-right font-weight-bold balance-cell <?php echo $balance_color_class; ?>" data-balance="<?php echo $current_balance; ?>">
                                                <?php echo $balance_display; ?><?php echo $balance_suffix; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-buttons">
                                                    <a href="customer_detail.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-info btn-sm btn-action" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="credit_rec.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-success btn-sm btn-action" title="Receive Payment">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </a>
                                                    <a href="customer_ledger.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-primary btn-sm btn-action" title="View Ledger">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <button onclick="editCustomer(<?php echo $row['id']; ?>)" 
                                                            class="btn btn-warning btn-sm btn-action" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteCustomer(<?php echo $row['id']; ?>)" 
                                                            class="btn btn-danger btn-sm btn-action" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                                <h5>No customers found</h5>
                                                <a href="customer.php" class="btn btn-success btn-sm mt-2">
                                                    <i class="fas fa-plus"></i> Add Your First Customer
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f8f9fc; font-weight: bold;">
                                            <th colspan="6" class="text-right">Grand Total:</th>
                                            <th class="text-right" id="totalBalance">0.00</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
    
    <!-- Scroll to Top Button -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <!-- JS Files -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Pass PHP variables to JavaScript
        var totalReceivable = <?php echo $total_receivable; ?>;
        var totalPayable = <?php echo $total_payable; ?>;
        var totalCustomers = <?php echo $total_customers; ?>;
        var activeCustomers = <?php echo $active_customers; ?>;
        
        $(document).ready(function() {
            // Update stats cards
            $('#totalCustomers').text(totalCustomers);
            $('#totalReceivable').text('₨ ' + totalReceivable.toFixed(2));
            $('#totalPayable').text('₨ ' + totalPayable.toFixed(2));
            $('#activeCustomers').text(activeCustomers);
            
            // Initialize DataTable
            var table = $('#customersTable').DataTable({
                "pageLength": 25,
                "order": [[0, 'desc']],
                "language": {
                    "search": "<i class='fas fa-search'></i> Search Customers:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ customers",
                    "emptyTable": "No customers found",
                    "zeroRecords": "No matching customers found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "→",
                        "previous": "←"
                    }
                },
                "columnDefs": [
                    { "orderable": false, "targets": [8] },
                    { "className": "text-center", "targets": [0, 4, 7, 8] }
                ],
                "drawCallback": function() {
                    calculateTotal();
                }
            });
            
            // Calculate total balance
            function calculateTotal() {
                var total = 0;
                $('#customersTable tbody tr').each(function() {
                    var balanceCell = $(this).find('td.balance-cell');
                    var amount = parseFloat(balanceCell.data('balance')) || 0;
                    total += amount;
                });
                
                if(total > 0) {
                    $('#totalBalance').html('<span class="text-danger">₨ ' + total.toFixed(2) + ' (Total Receivable)</span>');
                } else if(total < 0) {
                    $('#totalBalance').html('<span class="text-success">₨ ' + Math.abs(total).toFixed(2) + ' (Total Payable)</span>');
                } else {
                    $('#totalBalance').html('₨ 0.00');
                }
            }
            
            // Export to CSV
            $('#exportBtn').click(function(e) {
                e.preventDefault();
                
                var data = [];
                var headers = [];
                
                $('#customersTable thead th').each(function(index) {
                    var headerText = $(this).text();
                    if(headerText !== 'Actions' && headerText !== '#') {
                        headers.push(headerText);
                    }
                });
                data.push(headers);
                
                $('#customersTable tbody tr').each(function() {
                    var row = [];
                    $(this).find('td').each(function(index) {
                        if(index !== 8 && index !== 0) {
                            var text = $(this).text().trim();
                            if(index === 2) {
                                text = $(this).find('strong').text().trim();
                            }
                            row.push(text);
                        }
                    });
                    data.push(row);
                });
                
                var csv = data.map(row => row.join(',')).join('\n');
                var blob = new Blob([csv], {type: 'text/csv'});
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                link.href = url;
                link.download = 'customers_export_' + new Date().toISOString().slice(0,19) + '.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                Swal.fire({
                    title: 'Success!',
                    text: 'Export completed successfully!',
                    icon: 'success',
                    confirmButtonColor: '#1e7e34',
                    timer: 2000
                });
            });
        });
        
        function editCustomer(id) {
            window.location.href = 'edit_customer.php?id=' + id;
        }
        
        function deleteCustomer(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Customer will be deleted but all data (sales, payments, ledger) will remain safe!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_customer.php',
                        type: 'POST',
                        data: {id: id},
                        dataType: 'json',
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonColor: '#1e7e34'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonColor: '#1e7e34'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while deleting the customer.',
                                icon: 'error',
                                confirmButtonColor: '#1e7e34'
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>