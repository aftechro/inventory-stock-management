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
    // Add Category
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = sanitize($_POST['name'], $conn);

        $sql = "INSERT INTO categories (name) VALUES ('$name')";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Category added successfully!";
        } else {
            $error_message = "Error adding category: " . $conn->error;
        }
    }

    // Edit Category
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name'], $conn);

        $sql = "UPDATE categories SET name='$name' WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Category updated successfully!";
        } else {
            $error_message = "Error updating category: " . $conn->error;
        }
    }

    // Delete Category
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);

        $sql = "DELETE FROM categories WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Error deleting category: " . $conn->error;
        }
    }
}

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<?php require 'header.php'; require 'nav.php'; ?>

<div class="container mt-4">
    <h2>Manage Categories</h2>
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
    <button class="btn btn-primary my-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Add Category
    </button>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th style="width:150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($category = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#editCategoryModal<?= $category['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <!-- Delete Button -->
                    <form method="POST" action="categories.php" style="display:inline-block;"
                        onsubmit="return confirm('Are you sure you want to delete this category?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Edit Category Modal -->
            <div class="modal fade" id="editCategoryModal<?= $category['id'] ?>" tabindex="-1"
                aria-labelledby="editCategoryModalLabel<?= $category['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="categories.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editCategoryModalLabel<?= $category['id'] ?>">Edit Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                <div class="mb-3">
                                    <label for="name<?= $category['id'] ?>" class="form-label">Category Name</label>
                                    <input type="text" class="form-control" id="name<?= $category['id'] ?>" name="name"
                                        value="<?= htmlspecialchars($category['name']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Edit Category Modal -->
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Category Modal -->

<?php require 'footer.php'; ?>
