<?php
session_start(); // Start session management

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (!$isLoggedIn) {
    // Store the intended URL (current URL) in session
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: index.php"); // Redirect to login
    exit;
}

require 'db.php'; // Database connection file

// Base URL for the application
$base_url = "https://stock.yourdomain.com"; // Change to your domain

// QR code directory
$qrDirectory = 'uploads/qr';
if (!file_exists($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

// Function to generate QR code
function generateQRCode($productId) {
    global $qrDirectory, $base_url;

    // QR code URL should point to the stock.php file with the appropriate product ID
    $qrCodeUrl = "$base_url/stock.php?product_id=" . $productId;
    $qrCodeFilePath = $qrDirectory . '/' . $productId . '.png';

    if (!file_exists($qrCodeFilePath)) {
        $qrCodeImage = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qrCodeUrl));
        if ($qrCodeImage !== false) {
            file_put_contents($qrCodeFilePath, $qrCodeImage);
        }
    }
}

// Generate QR codes for all products on page load
$result = $conn->query("SELECT id FROM products");
while ($product = $result->fetch_assoc()) {
    generateQRCode($product['id']);
}

// Function to log user actions in the stock_logs table
function logAction($user_id, $action, $product_id, $quantity_change, $description) {
    global $conn; // Use the global connection variable

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO stock_logs (user_id, action, product_id, quantity_change, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $user_id, $action, $product_id, $quantity_change, $description);

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->close(); // Close the statement
    } else {
        error_log("Failed to log action: " . $stmt->error); // Log error if logging fails
    }
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_quantity') {
    $product_id = intval($_POST['id']);
    $quantity_change = intval($_POST['quantity']); // Get quantity adjustment from input

    // Get current quantity
    $result = $conn->query("SELECT quantity FROM products WHERE id = $product_id");
    $current_quantity = $result->fetch_assoc()['quantity'];
    $new_quantity = $current_quantity + $quantity_change; // Calculate new quantity

    // Update product quantity in the database
    $sql = "UPDATE products SET quantity = $new_quantity WHERE id = $product_id";
    if ($conn->query($sql) === TRUE) {
        // Log the action after successful update
        $user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session
        $action = ($quantity_change > 0) ? 'added' : 'removed';
        $description = "User ID $user_id $action $quantity_change qty for product ID $product_id.";
        logAction($user_id, $action, $product_id, $quantity_change, $description); // Log the action

        $success_message = "Quantity updated successfully!";
    } else {
        $error_message = "Error updating quantity: " . $conn->error;
        logAction($_SESSION['user_id'], 'update failed', $product_id, 0, $error_message); // Log error
    }
}

// Search functionality
$search_query = '';
$category_filter = '';
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = intval($_GET['category']);
    $category_filter = " AND p.category_id = $category_id"; // Ensure the filter is applied correctly
}

// Fetch products with categories and include selling price
$sql = "SELECT p.*, c.name AS category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE (p.name LIKE '%$search_query%' OR p.description LIKE '%$search_query%') $category_filter";

$products = $conn->query($sql);

// Handle QR code scan
$modal_product_id = null;
$product_name = '';
$product_quantity = 0;
$product_description = ''; // New variable for product description

if (isset($_GET['product_id'])) {
    $scanned_product_id = intval($_GET['product_id']);
    $product_result = $conn->query("SELECT * FROM products WHERE id = $scanned_product_id");

    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $modal_product_id = $product['id'];
        $product_name = $product['name'];
        $product_quantity = $product['quantity'];
        $product_description = $product['description']; // Get product description
    } else {
        $error_message = "Product not found.";
        logAction($_SESSION['user_id'], 'product scan failed', $scanned_product_id, 0, $error_message); // Log error
    }
}
?>



    <style>
        .qr-button {
            width: 100px; /* Set the button size */
            height: 100px; /* Set the button height */
            background-size: cover; /* Cover the button with the QR code */
            background-repeat: no-repeat; /* No repeat of the image */
            border: none; /* No border */
            cursor: pointer; /* Change cursor to pointer */
            position: relative; /* Position relative for absolute elements */
        }
        .qr-button:focus {
            outline: none; /* Remove focus outline */
        }
        .badge-low {
            background-color: #ffc107; /* Yellow for low stock */
            color: white;
        }
        .badge-out {
            background-color: #dc3545; /* Red for out of stock */
            color: white;
        }
        .badge-in {
            background-color: #28a745; /* Green for in stock */
            color: white;
        }
        .alert {
            display: flex; /* Flexbox for alert layout */
            justify-content: space-between; /* Space between alert text and close button */
            align-items: center; /* Center items vertically */
        }
        .close-alert {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: inherit; /* Use current text color */
            cursor: pointer; /* Pointer cursor */
        }
        .quantity-large {
            font-size: 1.5rem; /* Adjust the size as needed */
            font-weight: bold; /* Make it bold for emphasis */
        }



    </style>

<?php require 'nav.php'; require 'header.php'; ?>

<div class="container mt-4">
    <h2>Stock Management</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="close-alert" data-bs-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="close-alert" data-bs-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>
    
    
    
    
    

    
    

<!-- Search Form -->
<form method="GET" action="stock.php" class="mb-3">
    <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="Search for products..." value="<?= htmlspecialchars($search_query) ?>">
        <select name="category" class="form-select">
            <option value="">All Categories</option>
            <?php
            // Fetch categories for the dropdown in alphabetical order
            $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
            while ($category = $categories->fetch_assoc()): ?>
                <option value="<?= $category['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </div>
</form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th style="width: 30%;">Product</th>
                    <th class="text-center">Quantity / Status</th>
                    <th class="text-end">Selling Price</th> <!-- New Header -->
                    <th class="text-end" style="width: 20%;">QR Code</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td class="align-middle" style="white-space: nowrap;">
                            <div style="text-align: left;">
                                <?= html_entity_decode($product['name']) ?><br>
                                <small class="text-muted"><?= html_entity_decode($product['description']) ?></small>
                            </div>
                        </td>
                        <td class="align-middle text-center">
                            <span class="quantity-large"><?= htmlspecialchars($product['quantity']) ?></span><br>
                            <?php
                            if ($product['quantity'] <= 0) {
                                echo '<span class="badge badge-out">Out of Stock</span>';
                            } elseif ($product['quantity'] <= 3) {
                                echo '<span class="badge badge-low">Low Stock</span>';
                            } else {
                                echo '<span class="badge badge-in">In Stock</span>';
                            }
                            ?>
                        </td>
                        <td class="align-middle text-end"> 
                            <span style="font-size: 1.5rem; font-weight: bold;"><?= htmlspecialchars($product['selling_price']) ?> €</span>
                            <br>
                            <small style="font-size: 1rem;">+ VAT</small> <!-- Add VAT notice -->
                        </td>
                        <td class="align-middle text-end">
                            <button class="qr-button" style="background-image: url('uploads/qr/<?= htmlspecialchars($product['id'] . '.png') ?>')" 
                                    data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-quantity="<?= htmlspecialchars($product['quantity']) ?>" data-description="<?= htmlspecialchars($product['description']) ?>">
                            </button>
                            <br>
                            <a href="<?= $base_url ?>/uploads/qr/<?= htmlspecialchars($product['id'] . '.png') ?>" target="_blank" class="small">Print QR Code</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<!-- Stock Management Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">Stock Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Product Info Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0" id="modal-product-name"><?= html_entity_decode($product['name']) ?></h5>
                        <p class="text-muted mb-0" id="modal-product-description"><?= html_entity_decode($product_description) ?></p>
                    </div>
                    <div>
                        <h4 class="mb-0">
                            <strong id="modal-current-stock"><?= htmlspecialchars($product_quantity) ?></strong>
                            <span id="stock-status-badge" class="badge"></span>
                        </h4>
                       
                    </div>
                </div>

                <hr>

                <!-- IN/OUT Buttons -->
                <div class="d-flex justify-content-center mb-3">
                    <button type="button" class="btn btn-success btn-lg me-3 w-50" id="inButton">
                        <i class="bi bi-plus-circle"></i> IN
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg w-50" id="outButton">
                        <i class="bi bi-dash-circle"></i> OUT
                    </button>
                </div>

                <!-- Input Section for Quantity -->
                <form id="update-quantity-form" method="POST" action="stock.php">
                    <input type="hidden" name="action" value="update_quantity">
                    <input type="hidden" name="id" id="modal-product-id" value="<?= $modal_product_id ?>">

                    <div class="mb-3">
                        <label for="new-quantity" class="form-label">Enter Quantity:</label>
                        <input type="number" class="form-control form-control-lg" name="quantity" id="new-quantity" placeholder="0" required>
                        <small class="form-text text-muted" id="quantity-help-text">Enter the amount to adjust stock.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">Update Stock</button>
                </form>
            </div>
        </div>
    </div>
</div>

                        
                        
<!-- jQuery Script to Handle IN/OUT Button Logic and Modal -->
 <script src="assets/js/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var isOutOperation = false; // Flag to check if it's an OUT operation

    // Open modal with correct product info
    $('.qr-button').on('click', function() {
        var productId = $(this).data('id');
        var productName = $(this).data('name');
        var currentQuantity = $(this).data('quantity');
        var productDescription = $(this).data('description');

        $('#modal-product-id').val(productId);
        $('#modal-current-stock').text(currentQuantity); // Correct stock value
        $('#modal-product-name').html(productName);  // Product name
        $('#modal-product-description').html(productDescription);// Product description
        $('#new-quantity').val(''); // Clear input

        // Set the stock badge color based on the quantity
        var stockBadge = $('#stock-status-badge');
        if (currentQuantity <= 0) {
            stockBadge.text('Out of Stock').removeClass().addClass('badge bg-danger');
        } else if (currentQuantity <= 3) {
            stockBadge.text('Low Stock').removeClass().addClass('badge bg-warning');
        } else {
            stockBadge.text('In Stock').removeClass().addClass('badge bg-success');
        }

        // Reset button states
        $('#inButton').removeClass('btn-outline-success').addClass('btn-success');
        $('#outButton').removeClass('btn-danger').addClass('btn-outline-danger');
        isOutOperation = false; // Reset operation flag

        $('#qrModal').modal('show');
    });

    // Handle IN button click
    $('#inButton').on('click', function() {
        isOutOperation = false; // IN operation, so flag is set to false
        $('#inButton').removeClass('btn-outline-success').addClass('btn-success'); // Highlight IN
        $('#outButton').removeClass('btn-danger').addClass('btn-outline-danger'); // Grey out OUT
    });

    // Handle OUT button click
    $('#outButton').on('click', function() {
        isOutOperation = true; // OUT operation, so flag is set to true
        $('#outButton').removeClass('btn-outline-danger').addClass('btn-danger'); // Highlight OUT
        $('#inButton').removeClass('btn-success').addClass('btn-outline-success'); // Grey out IN
    });

    // Before form submission, adjust the quantity for OUT operation
    $('#update-quantity-form').on('submit', function(event) {
        var quantity = parseInt($('#new-quantity').val(), 10);

        if (isOutOperation && quantity > 0) {
            $('#new-quantity').val(-quantity); // Convert positive input to negative for OUT
        }
    });

    // Handle auto-opening for QR scans
    if (<?= json_encode($modal_product_id !== null) ?>) {
        $('#qrModal').modal('show');
    }
});
</script>


<?php require 'footer.php'; ?>
