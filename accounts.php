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
    // Add Account
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = sanitize($_POST['name'], $conn);

        $sql = "INSERT INTO accounts (name) VALUES ('$name')";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Account added successfully!";
        } else {
            $error_message = "Error adding account: " . $conn->error;
        }
    }

    // Edit Account
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name'], $conn);

        $sql = "UPDATE accounts SET name='$name' WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Account updated successfully!";
        } else {
            $error_message = "Error updating account: " . $conn->error;
        }
    }

    // Delete Account
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);

        $sql = "DELETE FROM accounts WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "Account deleted successfully!";
        } else {
            $error_message = "Error deleting account: " . $conn->error;
        }
    }
}

// Fetch accounts
$accounts = $conn->query("SELECT * FROM accounts ORDER BY name ASC");
?>

<?php require 'header.php'; require 'nav.php'; ?>

<div class="container mt-4">
    <h2>Manage Accounts</h2>
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
    <button class="btn btn-primary my-3" data-bs-toggle="modal" data-bs-target="#addAccountModal">
        <i class="fas fa-plus"></i> Add Account
    </button>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th style="width:150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($account = $accounts->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($account['name']) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#editAccountModal<?= $account['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <!-- Delete Button -->
                    <form method="POST" action="accounts.php" style="display:inline-block;"
                        onsubmit="return confirm('Are you sure you want to delete this account?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $account['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Edit Account Modal -->
            <div class="modal fade" id="editAccountModal<?= $account['id'] ?>" tabindex="-1"
                aria-labelledby="editAccountModalLabel<?= $account['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="accounts.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editAccountModalLabel<?= $account['id'] ?>">Edit Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $account['id'] ?>">
                                <div class="mb-3">
                                    <label for="name<?= $account['id'] ?>" class="form-label">Account Name</label>
                                    <input type="text" class="form-control" id="name<?= $account['id'] ?>" name="name"
                                        value="<?= htmlspecialchars($account['name']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Edit Account Modal -->
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="accounts.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountModalLabel">Add Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Account Modal -->

<?php require 'footer.php'; ?>
