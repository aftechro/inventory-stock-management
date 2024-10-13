<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


require 'db.php';
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}


// Fetch product data
$sql = "SELECT COUNT(id) AS total_products, 
               SUM(CASE WHEN quantity BETWEEN 1 AND 4 THEN 1 ELSE 0 END) AS low_stock, 
               SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock 
        FROM products";
$result = $conn->query($sql);
$stats = $result->fetch_assoc();

// Fetch products with low stock (1 to 4) and out of stock (0)
$sql_low_stock = "SELECT * FROM products WHERE quantity BETWEEN 1 AND 4";
$sql_out_of_stock = "SELECT * FROM products WHERE quantity = 0";

$low_stock_products = $conn->query($sql_low_stock);
$out_of_stock_products = $conn->query($sql_out_of_stock);





?>
<style>
    .container {
        margin-top: 20px; /* Top margin for spacing */
    }

    .scrollable-list {
        max-height: 300px; /* Set maximum height for the scrollable area */
        overflow-y: auto; /* Enable vertical scrolling */
        border: 1px solid #ccc; /* Border around the list */
        border-radius: 5px; /* Rounded corners */
        background-color: #f9f9f9; /* Light background color */
        padding: 10px; /* Padding for the list */
    }

    .stock_changes {
        display: table; /* Use table display for structured layout */
        width: 100%; /* Full width for table */
        margin-bottom: 15px; /* Space between rows */
        border-bottom: 1px solid #e0e0e0; /* Separator line */
    }

    .stock_badge {
        display: table-cell; /* Badge behaves like a table cell */
        font-size: 25px; /* Font size for the badge */
        width: 50px; /* Fixed width for the badge */
        height: 50px; /* Fixed height for the badge */
        text-align: center; /* Center the badge text */
        vertical-align: middle; /* Align badge vertically */
        margin-right: 18px; /* Space between badge and text */
    }

    .details {
        display: table-cell; /* Details behave like a table cell */
        vertical-align: middle; /* Align text vertically */
    }

    .product-name {
        font-weight: bold; /* Make product name bold */
        font-size: 16px; /* Font size for product name */
    }

    .additional-data {
        font-size: 12px; /* Font size for additional data */
        color: #555; /* Color for additional data */
    }

    .out-of-stock {
        color: red; /* Red text for out of stock */
    }

    .low-stock {
        color: orange; /* Orange text for low stock */
    }

    .in-stock {
        color: green; /* Green text for in stock */
    }

    /* Add specific badge color styles */
    .bg-success {
        background-color: #28a745; /* Green background for stock additions */
        color: white; /* White text for the badge */
    }

    .bg-danger {
        background-color: #dc3545; /* Red background for stock removals */
        color: white; /* White text for the badge */
    }
</style>
    <?php require 'header.php'; require 'nav.php'; ?>



    <div class="container mt-4">
        <h2>Dashboard</h2>

<div class="row">
    <div class="col-12 col-md-6 col-lg-3 mb-4 d-none d-md-block" onclick="changeTab('out-of-stock')">
        <div class="card bg-danger">
            <div class="card-bg-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="card-body">
                <h1 class="display-4"><?= $stats['out_of_stock'] ?></h1>
                <h5 class="card-title">Out of Stock</h5>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 mb-4 d-none d-md-block" onclick="changeTab('low-stock')">
        <div class="card bg-warning">
            <div class="card-bg-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="card-body">
                <h1 class="display-4"><?= $stats['low_stock'] ?></h1>
                <h5 class="card-title">Low Stock - 1 to 4</h5>
            </div>
        </div>
    </div>

<!-- Clickable card that checks for access -->
<div class="col-12 col-md-6 col-lg-3 mb-4 d-none d-md-block" onclick="checkAccess()">
    <div class="card bg-primary">
        <div class="card-bg-icon"><i class="fas fa-box-open"></i></div>
        <div class="card-body">
            <h1 class="display-4"><?= $stats['total_products'] ?></h1>
            <h5 class="card-title">Total Products</h5>
        </div>
    </div>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="accessDeniedModal" tabindex="-1" role="dialog" aria-labelledby="accessDeniedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accessDeniedModalLabel">Access Denied</h5>
                <button type="button" class="close" onclick="closeModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                You do not have access to this section. Please contact the administrator if you believe this is an error.
            </div>
            <div class="modal-footer">
                <!-- Only the Close button to dismiss the modal -->
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function checkAccess() {
        // Get the user's role from PHP session
        var userRole = '<?php echo $_SESSION['role']; ?>';

        // Check if the user is an admin
        if (userRole === 'admin') {
            // Redirect to products.php if the user is an admin
            window.location.href = 'products.php';
        } else {
            // Show the modal if the user is not an admin
            $('#accessDeniedModal').modal('show');
        }
    }

    function closeModal() {
        $('#accessDeniedModal').modal('hide');
    }
</script>



<div class="col-12 col-md-6 col-lg-3 mb-4" onclick="window.location.href='stock.php'">
    <div class="card bg-success">
        <div class="card-bg-icon"><i class="fas fa-barcode"></i></div>
        <div class="card-body">
            <h1 class="display-4">Live Stock</h1>
            <!-- small class="card-title">Manage Stock</small -->
        </div>
    </div>
</div>


</div>
    






        <!-- Tabs for Product Display -->
        <h3>Stock:</h3>
<ul class="nav nav-tabs" id="productTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="out-of-stock-tab" data-toggle="tab" href="#out-of-stock" role="tab" aria-controls="out-of-stock" aria-selected="true">
            Out of Stock 
            <span class="badge badge-light d-md-none"><?= $stats['out_of_stock'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="low-stock-tab" data-toggle="tab" href="#low-stock" role="tab" aria-controls="low-stock" aria-selected="false">
            Low Stock 
            <span class="badge badge-light d-md-none"><?= $stats['low_stock'] ?></span>
        </a>
    </li>
</ul>


        <div class="tab-content" id="productTabContent">
            <div class="tab-pane fade show active" id="out-of-stock" role="tabpanel" aria-labelledby="out-of-stock-tab">
                <div class="table-container">
                
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Products Out of Stock</th>
                            <!-- th>Qty</th -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($product = $out_of_stock_products->fetch_assoc()): ?>
                        <tr class="table-danger">
                            <td><?= $product['name'] ?></td>
                            <!-- td><?= $product['quantity'] ?></td -->
                    		
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="low-stock" role="tabpanel" aria-labelledby="low-stock-tab">
                <div class="table-container">
                 
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Products with Low Stock (1 to 4) </th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                        <tr class="table-warning">
                            <td><?= $product['name'] ?></td>
                            <td><?= $product['quantity'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
                    
                    
  <div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <h6>Stock In (Additions)</h6>
            <div class="scrollable-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;">
                <?php
                // Fetch up to 6 stock additions
                $additions_query = "
                    SELECT sl.quantity_change, sl.created_at, p.name AS product_name, u.username, p.quantity AS total_stock 
                    FROM stock_logs sl 
                    JOIN products p ON sl.product_id = p.id 
                    JOIN users u ON sl.user_id = u.id 
                    WHERE sl.action = 'added' 
                    ORDER BY sl.created_at DESC
                    LIMIT 10
                ";
                $additions_result = $conn->query($additions_query);

                if ($additions_result->num_rows > 0) {
                    while ($addition = $additions_result->fetch_assoc()) {
                        $product_name = htmlspecialchars($addition['product_name']);
                        $quantity_added = $addition['quantity_change'];
                        $user = htmlspecialchars($addition['username']);
                        $date = date('d M Y - H:i', strtotime($addition['created_at']));
                        $total_stock = $addition['total_stock']; // Total stock after addition

                        echo "<div class='d-flex align-items-center border-bottom py-2'> 
                            <div class='badge bg-success me-3' style='font-size: 1.5rem; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;'>+{$quantity_added}</div> 
                            <div class='flex-grow-1'>
                                <strong class='h6'>{$product_name}</strong><br>
                                <span class='text-muted'>By: {$user} | on: {$date} | In stock: <strong>{$total_stock}</strong></span>
                            </div>
                        </div>";
                    }
                } else {
                    echo '<div class="text-muted">No stock additions found.</div>';
                }
                ?>
            </div>
        </div>

        <div class="col-md-6">
            <h6>Stock Out (Removals)</h6>
            <div class="scrollable-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;">
                <?php
                // Fetch up to 6 stock removals
                $removals_query = "
                    SELECT sl.quantity_change, sl.created_at, p.name AS product_name, u.username, p.quantity AS total_stock 
                    FROM stock_logs sl 
                    JOIN products p ON sl.product_id = p.id 
                    JOIN users u ON sl.user_id = u.id 
                    WHERE sl.action = 'removed' 
                    ORDER BY sl.created_at DESC
                    LIMIT 10
                ";
                $removals_result = $conn->query($removals_query);

                if ($removals_result->num_rows > 0) {
                    while ($removal = $removals_result->fetch_assoc()) {
                        $product_name = htmlspecialchars($removal['product_name']);
                        $quantity_removed = $removal['quantity_change'];
                        $user = htmlspecialchars($removal['username']);
                        $date = date('d M Y - H:i', strtotime($removal['created_at']));
                        $total_stock = $removal['total_stock']; // Total stock after removal

                        echo "<div class='d-flex align-items-center border-bottom py-2'> 
                            <div class='badge bg-danger me-3' style='font-size: 1.5rem; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;'>{$quantity_removed}</div> 
                            <div class='flex-grow-1'>
                                <strong class='h6'>{$product_name}</strong><br>
                                <span class='text-muted'>By: {$user} | on: {$date} | In stock: <strong class='". ($total_stock <= 0 ? 'text-danger' : ($total_stock < 5 ? 'text-warning' : 'text-success')) ."'>".$total_stock."</strong></span>
                            </div>
                        </div>";
                    }
                } else {
                    echo '<div class="text-muted">No stock removals found.</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <hr>
</div>
                  
                    
                    


                
<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap4.5.2.bundle.min.js"></script>


<?php require 'footer.php'; ?>


