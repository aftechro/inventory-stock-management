<?php
session_start(); // Start session management

// Enable error reporting for debugging
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Display errors on the page

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (!$isLoggedIn) {
    // Store the intended URL (current URL) in session
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: index.php"); // Redirect to login
    exit;
}

require 'db.php'; // Include database connection

// Pagination settings
$limit = 15; // Number of logs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search settings
$searchTerm = '';
$userId = '';
$date = '';

// Process the search form submission
if (isset($_POST['search'])) {
    $userId = $_POST['user_id'] ?? '';
    $date = $_POST['date'] ?? ''; // Single date input
    $searchTerm = trim($_POST['product_name'] ?? '');
}

// Fetch users for the dropdown
$usersQuery = "SELECT id, username FROM users ORDER BY username";
$usersResult = $conn->query($usersQuery);

// Check for errors in the query
if (!$usersResult) {
    die("Users Query failed: " . $conn->error);
}

// Fetch stock logs from the database with product name based on search criteria
$stockLogsQuery = "
    SELECT sl.created_at, u.username, sl.user_id, sl.action, p.id AS product_id, p.name, sl.quantity_change 
    FROM stock_logs sl 
    JOIN users u ON sl.user_id = u.id 
    JOIN products p ON sl.product_id = p.id 
    WHERE (u.id = '$userId' OR '$userId' = '') 
      AND (DATE(sl.created_at) = ? OR '$date' = '') 
      AND (p.name LIKE ? OR '$searchTerm' = '')
    ORDER BY sl.created_at DESC
    LIMIT $limit OFFSET $offset
";

// Prepare the statement
$stmt = $conn->prepare($stockLogsQuery);
$likeSearchTerm = '%' . $searchTerm . '%'; // Prepare like query
$stmt->bind_param("ss", $date, $likeSearchTerm); // Bind parameters
$stmt->execute();
$stockLogsResult = $stmt->get_result();

// Check for errors in the query
if (!$stockLogsResult) {
    die("Stock Logs Query failed: " . $conn->error);
}

// Count total logs for pagination
$totalLogsQuery = "SELECT COUNT(*) as count FROM stock_logs sl 
                   JOIN users u ON sl.user_id = u.id 
                   JOIN products p ON sl.product_id = p.id 
                   WHERE (u.id = '$userId' OR '$userId' = '') 
                     AND (DATE(sl.created_at) = ? OR '$date' = '') 
                     AND (p.name LIKE ? OR '$searchTerm' = '')";
$stmtTotal = $conn->prepare($totalLogsQuery);
$stmtTotal->bind_param("ss", $date, $likeSearchTerm);
$stmtTotal->execute();
$totalLogsResult = $stmtTotal->get_result();
$totalLogs = $totalLogsResult->fetch_assoc()['count'];
$totalPages = ceil($totalLogs / $limit);

// Fetch product logs for the product logs tab with search and pagination
$productLogsQuery = "
    SELECT pl.created_at, u.username, pl.user_id, pl.action, p.name, pl.description
    FROM product_logs pl
    JOIN users u ON pl.user_id = u.id
    LEFT JOIN products p ON pl.product_id = p.id
    WHERE (u.id = '$userId' OR '$userId' = '') 
      AND (p.name LIKE ? OR '$searchTerm' = '')
";

// Append date condition if it's not empty
if (!empty($date)) {
    $productLogsQuery .= " AND (DATE(pl.created_at) = ?)";
}

$productLogsQuery .= " ORDER BY pl.created_at DESC LIMIT $limit OFFSET $offset";

// Prepare the statement
$stmtProduct = $conn->prepare($productLogsQuery);
if (!empty($date)) {
    $stmtProduct->bind_param("ss", $likeSearchTerm, $date);
} else {
    $stmtProduct->bind_param("s", $likeSearchTerm);
}
$stmtProduct->execute();
$productLogsResult = $stmtProduct->get_result();

// Check for errors in the query
if (!$productLogsResult) {
    die("Product Logs Query failed: " . $conn->error);
}

// Count total product logs for pagination
$totalProductLogsQuery = "SELECT COUNT(*) as count FROM product_logs pl
                           JOIN users u ON pl.user_id = u.id
                           JOIN products p ON pl.product_id = p.id
                           WHERE (u.id = '$userId' OR '$userId' = '') 
                             AND (p.name LIKE ? OR '$searchTerm' = '')";

// Append date condition if it's not empty
if (!empty($date)) {
    $totalProductLogsQuery .= " AND (DATE(pl.created_at) = ?)";
}

$stmtTotalProduct = $conn->prepare($totalProductLogsQuery);
if (!empty($date)) {
    $stmtTotalProduct->bind_param("ss", $likeSearchTerm, $date);
} else {
    $stmtTotalProduct->bind_param("s", $likeSearchTerm);
}
$stmtTotalProduct->execute();
$totalProductLogsResult = $stmtTotalProduct->get_result();
$totalProductLogs = $totalProductLogsResult->fetch_assoc()['count'];
$totalProductPages = ceil($totalProductLogs / $limit);
?>



    <style>
        .action-added {
            color: green;
            font-weight: bold;
        }
        .action-removed {
            color: red;
            font-weight: bold;
        }
        .quantity-added {
            color: green;
            font-weight: bold;
        }
        .quantity-removed {
            color: red;
            font-weight: bold;
        }
        th, td {
            border: 1px solid #ddd; /* Border for action and description columns */
        }
        td {
            padding: 8px; /* Padding for table cells */
        }
        .bold {
            font-weight: bold; /* Bold for product name */
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; require 'header.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Change Logs</h2>

        <!-- Search Form -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">Select User</option>
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $userId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="text" name="date" id="date" class="form-control" placeholder="YYYY-MM-DD" value="<?php echo htmlspecialchars($date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="product_name" class="form-label">Product</label>
                    <input type="text" name="product_name" id="product_name" class="form-control" placeholder="Product Name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit" name="search">Search</button>
        </form>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="stock-logs-tab" data-bs-toggle="tab" href="#stock-logs" role="tab" aria-controls="stock-logs" aria-selected="true">Stock Logs</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="product-logs-tab" data-bs-toggle="tab" href="#product-logs" role="tab" aria-controls="product-logs" aria-selected="false">Product Logs</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="user-logs-tab" data-bs-toggle="tab" href="#user-logs" role="tab" aria-controls="user-logs" aria-selected="false">User Logs</a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content mt-3" id="myTabContent">
            <!-- Stock Logs Tab -->
            <div class="tab-pane fade show active" id="stock-logs" role="tabpanel" aria-labelledby="stock-logs-tab">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if there are results and display stock logs
                        if ($stockLogsResult->num_rows > 0) {
                            while ($row = $stockLogsResult->fetch_assoc()) {
                                // Format the date
                                $dateFormatted = date('d F Y - H:i', strtotime($row['created_at']));

                                // Determine the classes for action and quantity
                                $actionClass = ($row['action'] === 'added') ? 'action-added' : 'action-removed';
                                $quantityClass = ($row['quantity_change'] > 0) ? 'quantity-added' : 'quantity-removed';
                                
                                // Create a description string
                                $description = "{$row['username']} <span class='bold'>{$row['action']}</span> <span class='{$quantityClass}'>{$row['quantity_change']}</span> qty for product ID {$row['product_id']} (<span class='bold'>{$row['name']}</span>)";

                                echo "<tr>
                                    <td>{$dateFormatted}</td>
                                    <td>{$row['username']}</td>
                                    <td><span class='$actionClass'>" . ucfirst($row['action']) . "</span></td>
                                    <td class='bold'>{$row['name']}</td>
                                    <td><span class='$quantityClass'>{$row['quantity_change']}</span></td>
                                    <td>{$description}</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No logs found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&user_id=<?php echo htmlspecialchars($userId); ?>&date=<?php echo htmlspecialchars($date); ?>&product_name=<?php echo htmlspecialchars($searchTerm); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>

<!-- Product Logs Tab -->
<div class="tab-pane fade" id="product-logs" role="tabpanel" aria-labelledby="product-logs-tab">
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
             
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($log = $productLogsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($log['action'])); ?></td>




                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination for Product Logs -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalProductPages; $i++): ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>


            <!-- User Logs Tab -->
            <div class="tab-pane fade" id="user-logs" role="tabpanel" aria-labelledby="user-logs-tab">
                <p>User logs will be displayed here.</p>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Initialize the datepicker
            $("#date").datepicker({
                dateFormat: "yy-mm-dd" // Format for the date picker
            });
        });
    </script>
    
 <?php require 'footer.php'; ?>
