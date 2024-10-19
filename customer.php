<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your db connection script
include('db.php');

// Fetch customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no customer ID is provided, redirect to customers list
if ($customer_id === 0) {
    header('Location: customers.php');
    exit();
}

// Fetch customer details from database
$sql = "SELECT * FROM customers WHERE id = $customer_id AND deleted_at IS NULL";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Customer not found!";
    exit();
}

$customer = $result->fetch_assoc(); 

// Fetch quotes for this customer
$account_number = $customer['account_number'];
$quotes_sql = "SELECT q.id, q.company_name, q.created_at, 
                      (SELECT SUM(total_price) FROM quote_items WHERE quote_id = q.id) AS total_price,
                      q.status 
               FROM quotes q
               WHERE q.account_number = '$account_number'";
$quotes_result = $conn->query($quotes_sql);

// Include header
include 'header.php'; 
?>
<title><?= htmlspecialchars($customer['company_name']) ?> - <?= htmlspecialchars($customer['contact_name']) ?></title>

<style>
    .card-header {
        background-color: #007bff; /* Blue background for header */
        color: white; /* White text for contrast */
    }
    .card-body {
        text-align: left;
        background-color: #f8f9fa; /* Light background for card body */
        color: #333; /* Dark text color for body */
    }
    .card {
        margin-bottom: 1rem; /* Space between cards */
    }
    .status-pending { color: orange; }
    .status-approved { color: green; }
    .status-rejected { color: red; }
    .status-completed { color: blue; }
</style>

<?php include 'nav.php'; ?>

<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-building"></i> Customer: <?= htmlspecialchars($customer['company_name']) ?></h2>

    <!-- Customer Added Date -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-calendar-alt"></i> <strong>Customer Added:</strong> <?= date('F j, Y, g:i a', strtotime($customer['created_at'])) ?>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="customerTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="info-tab" data-bs-toggle="tab" href="#info" role="tab" aria-controls="info" aria-selected="true"><i class="fas fa-info-circle"></i> Info</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="quotes-tab" data-bs-toggle="tab" href="#quotes" role="tab" aria-controls="quotes" aria-selected="false"><i class="fas fa-file-alt"></i> Quotes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="invoices-tab" data-bs-toggle="tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="false"><i class="fas fa-file-invoice"></i> Invoices</a>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content">
        <!-- Info Tab -->
        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
            <div class="row">
                <!-- Customer Info Card -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fas fa-building"></i> Customer Info
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Account:</strong> <?= htmlspecialchars($customer['account_number']) ?></p>
                            <p class="mb-1"><strong>Company:</strong> <?= htmlspecialchars($customer['company_name']) ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($customer['address']) ?>, <?= htmlspecialchars($customer['city']) ?>, <?= htmlspecialchars($customer['state']) ?>, <?= htmlspecialchars($customer['zip']) ?>, <?= htmlspecialchars($customer['country']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Contact Info Card -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fas fa-user"></i> Contact Info
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($customer['contact_name']) ?></p>
                            <p class="mb-1"><strong>Position:</strong> <?= htmlspecialchars($customer['contact_position']) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($customer['contact_email']) ?>"><?= htmlspecialchars($customer['contact_email']) ?></a></p>
                            <p class="mb-1"><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($customer['contact_phone']) ?>"><?= htmlspecialchars($customer['contact_phone']) ?></a></p>
                        </div>
                    </div>
                </div>

                <!-- Options Card -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fas fa-cogs"></i> Options
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Features:</strong></p>
                            <p>
                                <?php 
                                $options = explode(',', $customer['options']);
                                foreach ($options as $option) {
                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($option)) . '</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Reference Card -->
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fas fa-sticky-note"></i> Reference
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($customer['reference']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Communication Card -->
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fas fa-envelope"></i> Communication
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($customer['email']) ?>"><?= htmlspecialchars($customer['email']) ?></a></p>
                            <p class="mb-1"><strong>Landline:</strong> <a href="tel:<?= htmlspecialchars($customer['landline']) ?>"><?= htmlspecialchars($customer['landline']) ?></a></p>
                            <p class="mb-1"><strong>Mobile:</strong> <a href="tel:<?= htmlspecialchars($customer['mobile']) ?>"><?= htmlspecialchars($customer['mobile']) ?></a></p>
                            <p class="mb-1"><strong>Website:</strong> <a href="<?= htmlspecialchars($customer['website']) ?>" target="_blank"><?= htmlspecialchars($customer['website']) ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quotes Tab -->
        <div class="tab-pane fade" id="quotes" role="tabpanel" aria-labelledby="quotes-tab">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i> Quotes
                </div>
                <div class="card-body">
                    <?php if ($quotes_result->num_rows > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">QTE #</th> 
                                    <th style="width: 50%;">Products</th>
                                    <th style="width: 10%;">Quote Total</th>
                                    <th style="width: 15%;">Quote Date</th>
                                    <th style="width: 10%;">Status</th> 
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($quote = $quotes_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <center><a href="quote_details.php?id=<?= $quote['id'] ?>"><?= htmlspecialchars($quote['id']) ?></a></center>
                                        </td>
                                        <td>
                                            <?php
                                            // Fetch quote items for this quote
                                            $quote_items_sql = "SELECT product_name, quantity, total_price FROM quote_items WHERE quote_id = " . $quote['id'];
                                            $quote_items_result = $conn->query($quote_items_sql);

                                            if ($quote_items_result->num_rows > 0) {
                                                $products = [];
                                                while ($item = $quote_items_result->fetch_assoc()) {
                                                    $products[] = htmlspecialchars($item['product_name']) . " (Qty: " . $item['quantity'] . ", Total: $" . number_format($item['total_price'], 2) . ")";
                                                }
                                                echo implode('<br>', $products);
                                            } else {
                                                echo "No products found.";
                                            }
                                            ?>
                                        </td>
                                        <td>$<?= number_format($quote['total_price'], 2) ?></td>
                                        <td><?= date('F j, Y', strtotime($quote['created_at'])) ?></td>                             
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            $icon = '';
                                            switch ($quote['status']) {
                                                case 'Pending':
                                                    $statusClass = 'status-pending';
                                                    $icon = '<i class="fas fa-clock"></i>';
                                                    break;
                                                case 'Approved':
                                                    $statusClass = 'status-approved';
                                                    $icon = '<i class="fas fa-check-circle"></i>';
                                                    break;
                                                case 'Rejected':
                                                    $statusClass = 'status-rejected';
                                                    $icon = '<i class="fas fa-times-circle"></i>';
                                                    break;
                                                case 'Completed':
                                                    $statusClass = 'status-completed';
                                                    $icon = '<i class="fas fa-clipboard-check"></i>';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $statusClass ?>">
                                                <?= $icon ?> <?= htmlspecialchars($quote['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No quotes available for this customer.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Invoices Tab -->
        <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-file-invoice"></i> Invoices
                </div>
                <div class="card-body">
                    <p>Here, we will display customer-related invoices in the future.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<?php $conn->close(); ?>
