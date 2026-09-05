<?php
session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}
include('../includes/database.php');
include('../includes/txt.php');
$page_title = "Hold Bills";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-pause-circle text-warning mr-2"></i> Hold Bills</h1>
            <a href="add_sale.php" class="btn btn-success"><i class="fas fa-plus"></i> New Sale</a>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="row">
                    <div class="col-md-3"><input type="text" id="filter_hold_no" class="form-control" placeholder="Hold No"></div>
                    <div class="col-md-3"><input type="text" id="filter_customer" class="form-control" placeholder="Customer"></div>
                    <div class="col-md-3">
                        <select id="filter_status" class="form-control">
                            <option value="hold">Hold</option>
                            <option value="converted">Converted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button id="searchBtn" class="btn btn-primary"><i class="fas fa-search"></i> Search</button></div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="holdBillsTable" width="100%">
                        <thead>
                            <tr><th>Hold No</th><th>Date</th><th>Customer</th><th>Amount (₨)</th><th>Status</th><th>Created By</th><th>Actions</th></tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?></span></div></div></footer>
</div>

<script>
$(document).ready(function() {
    var table = $('#holdBillsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'ajax_get_hold_bills.php',
            data: function(d) {
                d.hold_no = $('#filter_hold_no').val();
                d.customer = $('#filter_customer').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'hold_no' },
            { data: 'hold_date' },
            { data: 'customer_name', render: (data, type, row) => row.walk_in_customer_name ? '<span class="font-weight-bold">' + row.walk_in_customer_name + '</span> <small class="text-info">(Walk-In)</small>' : data },
            { data: 'grand_total', render: data => '₨ ' + parseFloat(data).toFixed(2) },
            { data: 'status', render: data => `<span class="badge badge-${data=='hold'?'warning':(data=='converted'?'success':'secondary')}">${data}</span>` },
            { data: 'created_by_name' },
            { data: null, render: function(data) {
                let btns = '<div class="btn-group btn-group-sm">';
                if(data.status == 'hold') {
                    btns += `<a href="add_sale.php?load_hold_id=${data.id}" class="btn btn-primary" title="Load into Form"><i class="fas fa-download"></i> Load</a>`;
                }
                btns += `<a href="print_hold_bill.php?id=${data.id}" target="_blank" class="btn btn-info" title="Print / Download PDF"><i class="fas fa-file-pdf"></i> PDF</a>`;
                if(data.status == 'hold') {
                    btns += `<button class="btn btn-danger delete-hold" data-id="${data.id}" title="Delete"><i class="fas fa-trash"></i></button>`;
                }
                btns += '</div>';
                return btns;
            }}
        ]
    });
    
    $('#searchBtn').click(() => table.ajax.reload());
    
    $(document).on('click', '.delete-hold', function() {
        let id = $(this).data('id');
        Swal.fire({ title: 'Confirm Delete', text: 'This hold bill will be permanently deleted.', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                $.getJSON(`add_sale.php?action=delete_hold&id=${id}`, function(response) {
                    if(response.success) {
                        Swal.fire('Deleted', '', 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>