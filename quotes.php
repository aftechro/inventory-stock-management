<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php'; 

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Pagination settings
$limit = 10; // Number of quotes per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle quote status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quote'])) {
    $quote_id = sanitize($_POST['quote_id'], $conn);
    $quote_status = sanitize($_POST['quote_status'], $conn);
    $update_query = "UPDATE quotes SET status='$quote_status' WHERE id='$quote_id'";
    if ($conn->query($update_query) === TRUE) {
        $success_message = "Updated quote #$quote_id for " . sanitize($_POST['company_name'], $conn) . " - " . sanitize($_POST['contact_name'], $conn);
    } else {
        $error_message = "Error updating quote status: " . $conn->error;
    }
}

// Handle quote deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_quote'])) {
    $quote_id = sanitize($_POST['quote_id'], $conn);
    $delete_query = "DELETE FROM quotes WHERE id='$quote_id'";
    if ($conn->query($delete_query) === TRUE) {
        $success_message = "Deleted quote #$quote_id for " . sanitize($_POST['company_name'], $conn) . " - " . sanitize($_POST['contact_name'], $conn);
    } else {
        $error_message = "Error deleting quote: " . $conn->error;
    }
}

// Fetch quotes with optional search
$search = '';
if (isset($_POST['search'])) {
    $search = sanitize($_POST['search'], $conn);
}
$query = "SELECT q.*, SUM(qi.total_price) AS total_price FROM quotes q
          LEFT JOIN quote_items qi ON q.id = qi.quote_id
          WHERE q.id LIKE '%$search%' OR q.company_name LIKE '%$search%' OR q.contact_name LIKE '%$search%'
          GROUP BY q.id
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Get total quotes count for pagination
$total_query = "SELECT COUNT(*) AS total FROM quotes WHERE id LIKE '%$search%' OR company_name LIKE '%$search%' OR contact_name LIKE '%$search%'";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_quotes = $total_row['total'];
$total_pages = ceil($total_quotes / $limit);

// Helper function for formatting date
function formatDate($datetime) {
    return date('j M Y - H:i', strtotime($datetime));
}

// Helper function for rendering status
function renderStatus($status) {
    $class = '';
    $icon = '';
    switch ($status) {
        case 'Approved':
            $class = 'text-success';
            $icon = '✔️';
            break;
        case 'Rejected':
            $class = 'text-danger';
            $icon = '❌';
            break;
        case 'Completed':
            $class = 'text-info';
            $icon = '✅';
            break;
        default: // Pending
            $class = 'text-warning';
            $icon = '⏳';
            break;
    }
    return "<span class='$class'>$icon $status</span>";
}


include 'header.php';
include 'nav.php';
?>

<link rel="stylesheet" href="assets/css/bootstrap4.5.2.min.css">

<body>
<div class="container mt-5">
    <h5>Quotes Management</h5><hr>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search by Quote Number, Company Name, or Contact Name" value="<?php echo $search; ?>">
        <button type="submit" class="btn btn-primary mt-2">Search</button>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">QTE #</th>
                <th style="width: 30%;">Company Name</th>
                <th style="width: 10%; text-align: center;">Total</th>
                <th style="width: 20%;">Created At</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 10%; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $row['id']; ?></td>
                    <td>
                        <?php echo $row['company_name']; ?><br>
                        <?php echo $row['contact_name']; ?><br>
                        <small><?php echo $row['contact_position']; ?></small>
                    </td>
                    <td style="text-align: center;">$ <?php echo number_format($row['total_price'], 2); ?></td>
                    <td><?php echo formatDate($row['created_at']); ?></td>
                    <td><?php echo renderStatus($row['status']); ?></td>
                    <td>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">Edit</button>
                        <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['id']; ?>">X</button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Quote Status for Quote #<?php echo $row['id']; ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="quote_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="company_name" value="<?php echo $row['company_name']; ?>">
                                    <input type="hidden" name="contact_name" value="<?php echo $row['contact_name']; ?>">
                                    <div class="form-group">
                                        <label for="quote_status">Quote Status</label>
                                        <select name="quote_status" class="form-control" required>
                                            <option value="Pending" <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Approved" <?php if ($row['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                            <option value="Rejected" <?php if ($row['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
                                            <option value="Completed" <?php if ($row['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" name="update_quote" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion of Quote #<?php echo $row['id']; ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="quote_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="company_name" value="<?php echo $row['company_name']; ?>">
                                    <input type="hidden" name="contact_name" value="<?php echo $row['contact_name']; ?>">
                                    <p>Are you sure you want to delete the quote with ID <strong><?php echo $row['id']; ?></strong> for <strong><?php echo $row['company_name']; ?></strong>?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_quote" class="btn btn-danger">X</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<?php include 'footer.php';?>
