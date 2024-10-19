<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in'] || $_SESSION['role'] !== 'admin') {
    die('Access denied'); // Deny access if not logged in or not an admin
}

// Include necessary files
require 'header.php'; 
require 'nav.php'; 
require 'db.php';

// Initialize messages
$success_message = '';
$error_message = '';


// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'], $conn);

    // Add Customer
    if ($action === 'add') {
        $account_number = sanitize($_POST['account_number'], $conn);
        $company_name = sanitize($_POST['company_name'], $conn);
        $address = sanitize($_POST['address'], $conn);
        $city = sanitize($_POST['city'], $conn);
        $state = sanitize($_POST['state'], $conn);
        $zip = sanitize($_POST['zip'], $conn);
        $country = sanitize($_POST['country'], $conn);
        $email = sanitize($_POST['email'], $conn);
        $landline = sanitize($_POST['landline'], $conn);
        $mobile = sanitize($_POST['mobile'], $conn);
        $website = sanitize($_POST['website'], $conn);
        $contact_name = sanitize($_POST['contact_name'], $conn);
        $contact_email = sanitize($_POST['contact_email'], $conn);
        $contact_phone = sanitize($_POST['contact_phone'], $conn);
        $contact_position = sanitize($_POST['contact_position'], $conn);
        $options = isset($_POST['options']) ? implode(',', $_POST['options']) : '';
        $reference = sanitize($_POST['reference'], $conn);

        $sql = "INSERT INTO customers (account_number, company_name, address, city, state, zip, country, email, landline, mobile, website, contact_name, contact_email, contact_phone, contact_position, options, reference) 
                VALUES ('$account_number', '$company_name', '$address', '$city', '$state', '$zip', '$country', '$email', '$landline', '$mobile', '$website', '$contact_name', '$contact_email', '$contact_phone', '$contact_position', '$options', '$reference')";

        if ($conn->query($sql)) {
            $success_message = "Customer added successfully.";
        } else {
            $error_message = "Error adding customer: " . $conn->error;
        }
    }

    // Edit Customer
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $account_number = sanitize($_POST['account_number'], $conn);
        $company_name = sanitize($_POST['company_name'], $conn);
        $address = sanitize($_POST['address'], $conn);
        $city = sanitize($_POST['city'], $conn);
        $state = sanitize($_POST['state'], $conn);
        $zip = sanitize($_POST['zip'], $conn);
        $country = sanitize($_POST['country'], $conn);
        $email = sanitize($_POST['email'], $conn);
        $landline = sanitize($_POST['landline'], $conn);
        $mobile = sanitize($_POST['mobile'], $conn);
        $website = sanitize($_POST['website'], $conn);
        $contact_name = sanitize($_POST['contact_name'], $conn);
        $contact_email = sanitize($_POST['contact_email'], $conn);
        $contact_phone = sanitize($_POST['contact_phone'], $conn);
        $contact_position = sanitize($_POST['contact_position'], $conn);
        $options = isset($_POST['options']) ? implode(',', $_POST['options']) : '';
        $reference = sanitize($_POST['reference'], $conn);

        $sql = "UPDATE customers 
                SET account_number = '$account_number', company_name = '$company_name', address = '$address', city = '$city', state = '$state', zip = '$zip', country = '$country', email = '$email', landline = '$landline', mobile = '$mobile', website = '$website', contact_name = '$contact_name', contact_email = '$contact_email', contact_phone = '$contact_phone', contact_position = '$contact_position', options = '$options', reference = '$reference' 
                WHERE id = $id";

        if ($conn->query($sql)) {
            $success_message = "Customer updated successfully.";
        } else {
            $error_message = "Error updating customer: " . $conn->error;
        }
    }

    // Delete Customer
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM customers WHERE id = $id";
        if ($conn->query($sql)) {
            $success_message = "Customer deleted successfully.";
        } else {
            $error_message = "Error deleting customer: " . $conn->error;
        }
    }
}

// Fetch customers for display
$customers_sql = "SELECT * FROM customers WHERE deleted_at IS NULL";
$customers = $conn->query($customers_sql);

// Check for a specific customer if editing
$customer = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    }
}

// Prepare options for display
$optionsArray = [];
if ($customer && isset($customer['options'])) {
    $options = $customer['options'];
    if (is_string($options)) {
        $optionsArray = explode(',', $options); // Convert it to an array
    } else {
        $optionsArray = (array) $options; // Ensure it's an array
    }
}

$optionString = implode(', ', $optionsArray); // Now you can safely use implode

?>



<div class="container mt-4">
    <h2>Manage Customers</h2>

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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Add Customer</button>
    </div>

<div class="table-responsive">
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by Account Number, Company, or Contact Name">
    <table class="table table-bordered table-striped table-hover" id="customerTable">
        <thead class="table-dark">
            <tr>
                <th style="width: 25%;">Account</th>
                <th style="width: 20%;">Company</th>
                <th style="width: 20%;">Contact Info</th>
                <th style="width: 25%;">Address</th>
                <th style="width: 10%;">Options</th>
                <th style="width: 5%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($customer = $customers->fetch_assoc()): ?>
            <tr>
                <td class="align-middle" style="text-align: left;">
                    <strong>
                        <a href="customer.php?id=<?= $customer['id'] ?>" class="text-primary text-decoration-none">
                            <span class="badge bg-primary"><?= htmlspecialchars($customer['account_number']) ?></span>
                        </a>
                    </strong><br>
                    <strong>
                        <a href="customer.php?id=<?= $customer['id'] ?>" class="text-dark text-decoration-none">
                            <?= htmlspecialchars($customer['company_name']) ?>
                        </a>
                    </strong>
                </td>

                <td>
                    <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($customer['landline']) ?>"><?= htmlspecialchars($customer['landline']) ?></a><br>
                    <i class="fas fa-mobile-alt"></i> <a href="tel:<?= htmlspecialchars($customer['mobile']) ?>"><?= htmlspecialchars($customer['mobile']) ?></a><br>
                    <i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($customer['email']) ?>"><?= htmlspecialchars($customer['email']) ?></a><br>
                    <i class="fas fa-globe"></i> <a href="<?= htmlspecialchars($customer['website']) ?>" target="_blank"><?= htmlspecialchars($customer['website']) ?></a>
                </td>
                <td>
                    <i class="fas fa-user"></i> <strong><?= htmlspecialchars($customer['contact_name']) ?></strong><br>
                    <small><?= htmlspecialchars($customer['contact_position']) ?></small><br>
                    <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($customer['contact_phone']) ?>"><?= htmlspecialchars($customer['contact_phone']) ?></a><br>
                    <i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($customer['contact_email']) ?>"><?= htmlspecialchars($customer['contact_email']) ?></a>
                </td>
                <td class="align-middle">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($customer['address']) ?><br>
                    <?= htmlspecialchars($customer['state']) ?>, <?= htmlspecialchars($customer['zip']) ?><br>
                    <?= htmlspecialchars($customer['country']) ?>
                </td>
                <td class="text-center">
                    <?php 
                    $options = explode(',', $customer['options']);
                    foreach ($options as $option) {
                        echo '<div class="badge bg-secondary" style="display: inline-block; margin: 2px; font-size: 0.9em;">' . htmlspecialchars(trim($option)) . '</div><br>'; // Each option on a new line
                    }
                    ?>
                    <br>
                    <a href="#" class="reference-link" data-reference="<?= htmlspecialchars($customer['reference']) ?>">Notes/Reference</a>

                </td>
                <td class="text-center align-middle">
                    <button class="btn btn-warning btn-sm edit-btn" 
                            data-id="<?= $customer['id'] ?>" 
                            data-account_number="<?= htmlspecialchars($customer['account_number']) ?>" 
                            data-company_name="<?= htmlspecialchars($customer['company_name']) ?>" 
                            data-address="<?= htmlspecialchars($customer['address']) ?>" 
                            data-city="<?= htmlspecialchars($customer['city']) ?>" 
                            data-state="<?= htmlspecialchars($customer['state']) ?>" 
                            data-zip="<?= htmlspecialchars($customer['zip']) ?>" 
                            data-country="<?= htmlspecialchars($customer['country']) ?>" 
                            data-email="<?= htmlspecialchars($customer['email']) ?>" 
                            data-landline="<?= htmlspecialchars($customer['landline']) ?>" 
                            data-mobile="<?= htmlspecialchars($customer['mobile']) ?>" 
                            data-website="<?= htmlspecialchars($customer['website']) ?>" 
                            data-contact_name="<?= htmlspecialchars($customer['contact_name']) ?>" 
                            data-contact_email="<?= htmlspecialchars($customer['contact_email']) ?>" 
                            data-contact_phone="<?= htmlspecialchars($customer['contact_phone']) ?>" 
                            data-contact_position="<?= htmlspecialchars($customer['contact_position']) ?>" 
                            data-options="<?= htmlspecialchars($customer['options']) ?>" 
                            data-reference="<?= htmlspecialchars($customer['reference']) ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#editCustomerModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <br><br>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $customer['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
            
<!-- Notes Reference Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <!-- Center the modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notes / Reference</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="notesContent">No notes available.</p> <!-- Content will be set dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#customerTable tbody tr');

    rows.forEach(row => {
        const accountNumber = row.cells[0].innerText.toLowerCase();
        const companyName = row.cells[1].innerText.toLowerCase();
        const contactName = row.cells[2].innerText.toLowerCase();

        if (accountNumber.includes(searchValue) || companyName.includes(searchValue) || contactName.includes(searchValue)) {
            row.style.display = ''; // Show the row
        } else {
            row.style.display = 'none'; // Hide the row
        }
    });
});

// Update the modal content and show the modal
document.querySelectorAll('.reference-link').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the default link behavior

        const reference = this.dataset.reference; // Get reference from data attribute
        document.getElementById('notesContent').innerText = reference || "No notes available."; // Set the content

        // Show the modal using Bootstrap's Modal API
        const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
        notesModal.show();
    });
});

</script>




<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="customers.php">
                <div class="modal-header" style="background-color: #343a40; color: white;">
                    <h5 class="modal-title" id="addCustomerModalLabel"><i class="fa fa-plus-circle me-2"></i>Add Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Section 1: Company Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-building me-2"></i>Company Information</h5>
                    </div>
                    <hr>
                    <input type="hidden" name="action" value="add">

                    <div class="row mb-4">
                        <div class="col-12 col-md-4 mb-3">
                            <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_number" name="account_number" required>
                        </div>
                        <div class="col-12 col-md">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter company name">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="Enter address" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="Enter city" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" placeholder="Enter state">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="zip" class="form-label">ZIP Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="zip" name="zip" placeholder="Enter ZIP code" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" placeholder="Enter country">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 2: Company Contact Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-phone me-2"></i>Company Contact Information</h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="info@company-name.com">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="landline" class="form-label">Landline</label>
                            <input type="text" class="form-control" id="landline" name="landline" placeholder="Enter main landline">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" placeholder="Enter website URL">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 3: Contact Person Details -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-user me-2"></i>Contact Person Details</h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" placeholder="Enter contact name" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="contact_position" class="form-label">Contact Position <span class="text-danger">*</span></label>
                            <select class="form-select" id="contact_position" name="contact_position" required>
                                <option value="" disabled selected>Select Position</option>
                                <option value="company owner">Company Owner</option>
                                <option value="accounts dept.">Accounts Department</option>
                                <option value="nominated contact">Nominated Contact</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="contact_email" class="form-label">Contact Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Enter contact email" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="contact_phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" placeholder="Enter contact phone" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="options" class="form-label">Customer Options <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="indexing" id="option_indexing">
                                    <label class="form-check-label" for="option_indexing">Indexing</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="allow quote" id="option_allow_quote">
                                    <label class="form-check-label" for="option_allow_quote">Allow Quote</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="allow invoice" id="option_allow_invoice">
                                    <label class="form-check-label" for="option_allow_invoice">Allow Invoice</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 4: Additional Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Additional Information</h5>
                    </div>
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference</label>
                        <textarea class="form-control" id="reference" name="reference" rows="3" placeholder="Enter reference information or other relevant notes"></textarea>
                    </div>

                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
                
                
<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="customers.php">
                <div class="modal-header" style="background-color: #343a40; color: white;">
                    <h5 class="modal-title" id="editCustomerModalLabel"><i class="fa fa-edit me-2"></i>Edit Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Section 1: Company Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-building me-2"></i>Company Information</h5>
                    </div>
                    <hr>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row mb-4">
                        <div class="col-12 col-md-4 mb-3">
                            <label for="edit_account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_account_number" name="account_number" required>
                        </div>
                        <div class="col-12 col-md">
                            <label for="edit_company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="edit_company_name" name="company_name" placeholder="Enter company name">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="edit_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_address" name="address" placeholder="Enter address" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_city" name="city" placeholder="Enter city" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="edit_state" class="form-label">State</label>
                            <input type="text" class="form-control" id="edit_state" name="state" placeholder="Enter state">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_zip" class="form-label">ZIP Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_zip" name="zip" placeholder="Enter ZIP code" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="edit_country" name="country" placeholder="Enter country">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 2: Company Contact Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-phone me-2"></i>Company Contact Information</h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" placeholder="info@company-name.com">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_landline" class="form-label">Landline</label>
                            <input type="text" class="form-control" id="edit_landline" name="landline" placeholder="Enter main landline">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Enter mobile number">
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="edit_website" name="website" placeholder="Enter website URL">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 3: Contact Person Details -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-user me-2"></i>Contact Person Details</h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="edit_contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_contact_name" name="contact_name" placeholder="Enter contact name" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_contact_position" class="form-label">Contact Position <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_contact_position" name="contact_position" required>
                                <option value="" disabled selected>Select Position</option>
                                <option value="company owner">Company Owner</option>
                                <option value="accounts dept.">Accounts Department</option>
                                <option value="nominated contact">Nominated Contact</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_contact_email" class="form-label">Contact Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_contact_email" name="contact_email" placeholder="Enter contact email" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12 col-md mb-3">
                            <label for="edit_contact_phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_contact_phone" name="contact_phone" placeholder="Enter contact phone" required>
                        </div>
                        <div class="col-12 col-md mb-3">
                            <label for="edit_options" class="form-label">Customer Options <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap" id="edit_options">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="indexing" id="edit_option_indexing">
                                    <label class="form-check-label" for="edit_option_indexing">Indexing</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="allow quote" id="edit_option_allow_quote">
                                    <label class="form-check-label" for="edit_option_allow_quote">Allow Quote</label>
                                </div>
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="options[]" value="allow invoice" id="edit_option_allow_invoice">
                                    <label class="form-check-label" for="edit_option_allow_invoice">Allow Invoice</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 4: Additional Information -->
                    <div class="bg-secondary text-white p-2 mb-3 rounded">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Additional Information</h5>
                    </div>
                    <div class="mb-3">
                        <label for="edit_reference" class="form-label">Reference</label>
                        <textarea class="form-control" id="edit_reference" name="reference" rows="3" placeholder="Enter reference information or other relevant notes"></textarea>
                    </div>

                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

                        
<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="customers.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCustomerModalLabel">Delete Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCustomerId">
                    <p>Are you sure you want to delete this customer?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add the event listeners for edit buttons to populate the modal with the correct data
document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_account_number').value = this.dataset.account_number;
        document.getElementById('edit_company_name').value = this.dataset.company_name;
        document.getElementById('edit_address').value = this.dataset.address;
        document.getElementById('edit_city').value = this.dataset.city;
        document.getElementById('edit_state').value = this.dataset.state;
        document.getElementById('edit_zip').value = this.dataset.zip;
        document.getElementById('edit_country').value = this.dataset.country;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_landline').value = this.dataset.landline;
        document.getElementById('edit_mobile').value = this.dataset.mobile;
        document.getElementById('edit_website').value = this.dataset.website;
        document.getElementById('edit_contact_name').value = this.dataset.contact_name;
        document.getElementById('edit_contact_email').value = this.dataset.contact_email;
        document.getElementById('edit_contact_phone').value = this.dataset.contact_phone;
        document.getElementById('edit_contact_position').value = this.dataset.contact_position;

        // Handle options
        const options = this.dataset.options ? this.dataset.options.split(',') : []; // Split options into an array

        // Clear all checkboxes first
        document.querySelectorAll('#edit_options input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false; // Uncheck all checkboxes
        });

        // Check the relevant checkboxes based on the options array
        options.forEach(option => {
            const checkbox = document.querySelector(`#edit_options input[type="checkbox"][value="${option.trim()}"]`);
            if (checkbox) {
                checkbox.checked = true; // Check the corresponding checkbox
            }
        });

        document.getElementById('edit_reference').value = this.dataset.reference;
    });
});

// Handle delete button click to set the customer ID in the modal
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        const customerId = this.dataset.id;
        document.getElementById('deleteCustomerId').value = customerId; // Set the ID in the hidden input field
    });
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#customerTable tbody tr');

    rows.forEach(row => {
        const accountNumber = row.cells[0].innerText.toLowerCase();
        const companyName = row.cells[1].innerText.toLowerCase();
        const contactName = row.cells[2].innerText.toLowerCase();

        if (accountNumber.includes(searchValue) || companyName.includes(searchValue) || contactName.includes(searchValue)) {
            row.style.display = ''; // Show the row
        } else {
            row.style.display = 'none'; // Hide the row
        }
    });
});

// Handle reference link click
document.querySelectorAll('.reference-link').forEach(link => {
    link.addEventListener('click', function() {
        const reference = this.dataset.reference;
        document.getElementById('referenceContent').innerText = reference;
        const referenceModal = new bootstrap.Modal(document.getElementById('referenceModal'));
        referenceModal.show();
    });
});
</script>



<?php require 'footer.php'; ?>
