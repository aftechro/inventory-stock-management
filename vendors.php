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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Vendor
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = sanitize($_POST['name'], $conn);

        $sql = "INSERT INTO vendors (name) VALUES ('$name')";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Vendor added successfully!";
        } else {
            $error_message = "Error adding vendor: " . $conn->error;
        }
    }

    // Edit Vendor
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name'], $conn);

        $sql = "UPDATE vendors SET name='$name' WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Vendor updated successfully!";
        } else {
            $error_message = "Error updating vendor: " . $conn->error;
        }
    }

    // Delete Vendor
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);

        $sql = "DELETE FROM vendors WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Vendor deleted successfully!";
        } else {
            $error_message = "Error deleting vendor: " . $conn->error;
        }
    }
}

// Fetch vendors
$vendors = $conn->query("SELECT * FROM vendors ORDER BY name ASC");
?>

<?php require 'header.php'; require 'nav.php'; ?>

<div class="container mt-4">
    <h2>Manage Vendors</h2>
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
    <button class="btn btn-primary my-3" data-bs-toggle="modal" data-bs-target="#addVendorModal">
        <i class="fas fa-plus"></i> Add Vendor
    </button>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th style="width:150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($vendor = $vendors->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($vendor['name']) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#editVendorModal<?= $vendor['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <!-- Delete Button -->
                    <form method="POST" action="vendors.php" style="display:inline-block;"
                        onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $vendor['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Edit Vendor Modal -->
            <div class="modal fade" id="editVendorModal<?= $vendor['id'] ?>" tabindex="-1"
                aria-labelledby="editVendorModalLabel<?= $vendor['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="vendors.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editVendorModalLabel<?= $vendor['id'] ?>">Edit Vendor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $vendor['id'] ?>">
                                <div class="mb-3">
                                    <label for="name<?= $vendor['id'] ?>" class="form-label">Vendor Name</label>
                                    <input type="text" class="form-control" id="name<?= $vendor['id'] ?>" name="name"
                                        value="<?= htmlspecialchars($vendor['name']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update Vendor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Edit Vendor Modal -->
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="vendors.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Vendor Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Vendor Modal -->

<?php require 'footer.php'; ?>
