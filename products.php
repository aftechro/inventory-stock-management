<?php
session_start();
require 'db.php';



// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success_message = '';
$error_message = '';


// Function to log actions in product_logs table
function logAction($conn, $user_id, $action, $product_id, $description, $product_name = null) {
    $created_at = date('Y-m-d H:i:s'); // Current timestamp

    // Prepare and execute the insert statement, including the product name in the log
    $sql = "INSERT INTO product_logs (created_at, user_id, action, product_id, description, product_name) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return;
    }
    
    $stmt->bind_param("sissss", $created_at, $user_id, $action, $product_id, $description, $product_name);

    if (!$stmt->execute()) {
        error_log("Failed to log action: " . $stmt->error); // Log error if it fails
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id']; // Assuming user ID is stored in session

    // Add Product
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = sanitize($_POST['name'], $conn);
        $description = sanitize($_POST['description'], $conn);
        $buying_price = sanitize($_POST['buying_price'], $conn);
        $selling_price = sanitize($_POST['selling_price'], $conn);
        $quantity = sanitize($_POST['quantity'], $conn);
        $category_id = sanitize($_POST['category'], $conn);
        $account_id = sanitize($_POST['account'], $conn);
        $vendor_id = sanitize($_POST['vendor'], $conn);

        $sql = "INSERT INTO products (name, description, buying_price, selling_price, quantity, category_id, account_id, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        if (!$stmt->bind_param("ssddiiii", $name, $description, $buying_price, $selling_price, $quantity, $category_id, $account_id, $vendor_id)) {
            die("Binding parameters failed: " . $stmt->error);
        }

        if ($stmt->execute()) {
            // Log the addition
            logAction($conn, $user_id, 'add', $conn->insert_id, "Added product: $name");
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Error adding product: " . $stmt->error;
        }
    }

    // Edit Product
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name'], $conn);
        $description = sanitize($_POST['description'], $conn);
        $buying_price = sanitize($_POST['buying_price'], $conn);
        $selling_price = sanitize($_POST['selling_price'], $conn);
        $quantity = sanitize($_POST['quantity'], $conn);
        $category_id = sanitize($_POST['category'], $conn);
        $account_id = sanitize($_POST['account'], $conn);
        $vendor_id = sanitize($_POST['vendor'], $conn);

        $sql = "UPDATE products SET name=?, description=?, buying_price=?, selling_price=?, quantity=?, category_id=?, account_id=?, vendor_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        if (!$stmt->bind_param("ssddiiiii", $name, $description, $buying_price, $selling_price, $quantity, $category_id, $account_id, $vendor_id, $id)) {
            die("Binding parameters failed: " . $stmt->error);
        }

        if ($stmt->execute()) {
            // Log the update
            logAction($conn, $user_id, 'edit', $id, "Updated product: $name");
            $success_message = "Product updated successfully!";
        } else {
            $error_message = "Error updating product: " . $stmt->error;
        }
    }

// Delete Product
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id']);

    // Fetch the product name before deletion
    $productQuery = "SELECT name FROM products WHERE id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $productName = $product['name'];

        // Log the deletion attempt along with product details
        logAction($conn, $user_id, 'delete', $id, "Deleted product ID: $id, Name: $productName", $productName);

        // Now delete the product
        $deleteProductQuery = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($deleteProductQuery);
        if ($stmt === false) {
            $error_message = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success_message = "Product deleted successfully!";
            } else {
                $error_message = "Error deleting product: " . $stmt->error;
            }
        }
    } else {
        $error_message = "Product not found.";
    }
}


}

// Fetch products
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$account_filter = isset($_GET['account']) ? intval($_GET['account']) : '';
$vendor_filter = isset($_GET['vendor']) ? intval($_GET['vendor']) : '';

$filterQuery = "WHERE 1=1";
if ($filter) {
    $filterQuery .= " AND name LIKE '%" . $conn->real_escape_string($filter) . "%'";
}
if ($category_filter) {
    $filterQuery .= " AND category_id = $category_filter";
}
if ($account_filter) {
    $filterQuery .= " AND account_id = $account_filter";
}
if ($vendor_filter) {
    $filterQuery .= " AND vendor_id = $vendor_filter";
}

$products = $conn->query("SELECT * FROM products $filterQuery");

// Check for errors in the query
if (!$products) {
    error_log("Query failed: " . $conn->error);
}

// Fetch categories, accounts, and vendors
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$accounts = $conn->query("SELECT * FROM accounts ORDER BY name ASC");
$vendors = $conn->query("SELECT * FROM vendors ORDER BY name ASC");

// Check for errors in the queries
if (!$categories) {
    error_log("Query failed for categories: " . $conn->error);
}
if (!$accounts) {
    error_log("Query failed for accounts: " . $conn->error);
}
if (!$vendors) {
    error_log("Query failed for vendors: " . $conn->error);
}

// Fetch names for existing products
$category_names = [];
$account_names = [];
$vendor_names = [];

// Get the names corresponding to each ID for existing products
while ($category = $categories->fetch_assoc()) {
    $category_names[$category['id']] = $category['name'];
}
while ($account = $accounts->fetch_assoc()) {
    $account_names[$account['id']] = $account['name'];
}
while ($vendor = $vendors->fetch_assoc()) {
    $vendor_names[$vendor['id']] = $vendor['name'];
}
?>




    <?php require 'header.php'; require 'nav.php'; ?>



    <script>
	$(document).ready(function() {
    // Dynamic search for products
    $('#search').on('input', function() {
        const query = $(this).val();
        if (query.length > 2) { // Start searching after 2 characters
            $.ajax({
                url: 'ajax_search.php',
                method: 'GET',
                data: { filter: query },
                success: function(data) {
                    $('#productTableBody').html(data);
                }
            });
        } else {
            // Reload the table or keep it empty if input is less than 3 characters
            $('#productTableBody').html('<tr><td colspan="9">Type at least 3 characters to search.</td></tr>');
        }
    });

    // Show delete confirmation modal
    $('.delete-btn').on('click', function() {
        const productId = $(this).data('id');
        $('#deleteProductId').val(productId);
        $('#deleteProductModal').modal('show');
    });
});

    </script>


<div class="container mt-4">
    <h2>Manage Products</h2>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

   <div class="d-flex justify-content-between mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>

    <form method="POST" action="export_csv.php" class="mb-0">
        <input type="hidden" name="action" value="export">
        <button type="submit" class="btn btn-success">Export to CSV</button>
    </form>
</div>

 <form method="GET" action="products.php" class="mb-3">
    <div class="input-group">
        <input type="text" id="search" name="filter" class="form-control" placeholder="Search products by name..." value="<?= isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : '' ?>">
        <select name="category" class="form-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($category_names as $cat_id => $cat_name): ?>
                <option value="<?= $cat_id ?>" <?= $cat_id == $category_filter ? 'selected' : '' ?>><?= htmlspecialchars($cat_name) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="account" class="form-select" onchange="this.form.submit()">
            <option value="">All Accounts</option>
            <?php foreach ($account_names as $acc_id => $acc_name): ?>
                <option value="<?= $acc_id ?>" <?= $acc_id == $account_filter ? 'selected' : '' ?>><?= htmlspecialchars($acc_name) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="vendor" class="form-select" onchange="this.form.submit()">
            <option value="">All Vendors</option>
            <?php foreach ($vendor_names as $ven_id => $ven_name): ?>
                <option value="<?= $ven_id ?>" <?= $ven_id == $vendor_filter ? 'selected' : '' ?>><?= htmlspecialchars($ven_name) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='products.php';">Reset</button>

    </div>
</form>


 <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Buying Price</th>
                <th>Selling Price</th>
                <th>Quantity</th>
                <th>Category</th>
                <th>Account</th>
                <th>Vendor</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="productTableBody">
            <?php while ($product = $products->fetch_assoc()): ?>
            <tr>
                <td><?= html_entity_decode($product['name']) ?></td>
                <td><?= html_entity_decode($product['description']) ?></td>
                <td><?= htmlspecialchars($product['buying_price']) ?> €</td>
                <td><?= htmlspecialchars($product['selling_price']) ?> €</td>
                <td><?= htmlspecialchars($product['quantity']) ?></td>
                <td><?= htmlspecialchars($category_names[$product['category_id']]) ?></td>
                <td><?= htmlspecialchars($account_names[$product['account_id']]) ?></td>
                <td><?= htmlspecialchars($vendor_names[$product['vendor_id']]) ?></td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editProductModal" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-description="<?= htmlspecialchars($product['description']) ?>" data-buying_price="<?= htmlspecialchars($product['buying_price']) ?>" data-selling_price="<?= htmlspecialchars($product['selling_price']) ?>" data-quantity="<?= htmlspecialchars($product['quantity']) ?>" data-category_id="<?= $product['category_id'] ?>" data-account_id="<?= $product['account_id'] ?>" data-vendor_id="<?= $product['vendor_id'] ?>">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $product['id'] ?>">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>




<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="products.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="buying_price" class="form-label">Buying Price</label>
                            <input type="number" class="form-control" id="buying_price" name="buying_price" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label for="account" class="form-label">Account</label>
                            <select class="form-select" id="account" name="account" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label for="vendor" class="form-label">Vendor</label>
                            <select class="form-select" id="vendor" name="vendor" required>
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="products.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editProductId">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="edit_buying_price" class="form-label">Buying Price</label>
                            <input type="number" class="form-control" id="edit_buying_price" name="buying_price" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="edit_selling_price" class="form-label">Selling Price</label>
                            <input type="number" class="form-control" id="edit_selling_price" name="selling_price" step="0.01" required>
                        </div>
                        <div class="col">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label for="edit_account" class="form-label">Account</label>
                            <select class="form-select" id="edit_account" name="account" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label for="edit_vendor" class="form-label">Vendor</label>
                            <select class="form-select" id="edit_vendor" name="vendor" required>
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="products.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteProductId">
                        <p>Are you sure you want to delete this product?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Populate edit modal fields with product data
    document.addEventListener('DOMContentLoaded', function () {
        const editProductModal = document.getElementById('editProductModal');
        editProductModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            const buying_price = button.getAttribute('data-buying_price');
            const selling_price = button.getAttribute('data-selling_price');
            const quantity = button.getAttribute('data-quantity');
            const category_id = button.getAttribute('data-category_id');
            const account_id = button.getAttribute('data-account_id');
            const vendor_id = button.getAttribute('data-vendor_id');

            // Update the modal's content
            document.getElementById('editProductId').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_buying_price').value = buying_price;
            document.getElementById('edit_selling_price').value = selling_price;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_category').value = category_id;
            document.getElementById('edit_account').value = account_id;
            document.getElementById('edit_vendor').value = vendor_id;
        });

        // Handle delete button click
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = button.getAttribute('data-id');
                document.getElementById('deleteProductId').value = id;
            });
        });
    });
</script>

<?php include 'footer.php'; ?>
