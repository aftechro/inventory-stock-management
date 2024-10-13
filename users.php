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
    // Add User
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $username = sanitize($_POST['username'], $conn);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = sanitize($_POST['role'], $conn);

        $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            $success_message = "User added successfully!";
        } else {
            $error_message = "Error adding user: " . $conn->error;
        }
    }

    // Edit User
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $username = sanitize($_POST['username'], $conn);
        $role = sanitize($_POST['role'], $conn);
        $sql = "UPDATE users SET username='$username', role='$role' WHERE id=$id";

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username='$username', password='$password', role='$role' WHERE id=$id";
        }

        if ($conn->query($sql) === TRUE) {
            $success_message = "User updated successfully!";
        } else {
            $error_message = "Error updating user: " . $conn->error;
        }
    }

    // Delete User
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);

        $sql = "DELETE FROM users WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
    }
}

// Fetch users
$users = $conn->query("SELECT * FROM users ORDER BY username ASC");
?>

<?php require 'header.php'; require 'nav.php'; ?>

<div class="container mt-4">
    <h2>Manage Users</h2>
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
    <button class="btn btn-primary my-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-plus"></i> Add User
    </button>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th style="width:150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#editUserModal<?= $user['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <!-- Delete Button -->
                    <?php if ($user['username'] != $_SESSION['username']): ?>
                    <form method="POST" action="users.php" style="display:inline-block;"
                        onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1"
                aria-labelledby="editUserModalLabel<?= $user['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="users.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel<?= $user['id'] ?>">Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <div class="mb-3">
                                    <label for="username<?= $user['id'] ?>" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username<?= $user['id'] ?>"
                                        name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password<?= $user['id'] ?>" class="form-label">Password (leave blank to keep unchanged)</label>
                                    <input type="password" class="form-control" id="password<?= $user['id'] ?>"
                                        name="password">
                                </div>
                                <div class="mb-3">
                                    <label for="role<?= $user['id'] ?>" class="form-label">Role</label>
                                    <select class="form-control" id="role<?= $user['id'] ?>" name="role">
                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin
                                        </option>
                                        <option value="agent" <?= $user['role'] == 'agent' ? 'selected' : '' ?>>Agent
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Edit User Modal -->
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role">
                            <option value="admin">Admin</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add User Modal -->

<?php require 'footer.php'; ?>
