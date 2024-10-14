<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    exit; // Prevent unauthorized access
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

if ($filter) {
    // Sanitize user input
    $filter = $conn->real_escape_string($filter);
    
    $sql = "SELECT * FROM products WHERE name LIKE '%$filter%'";
    $result = $conn->query($sql);
    
    $output = '';
    if ($result->num_rows > 0) {
        while ($product = $result->fetch_assoc()) {
            $output .= '<tr>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td>' . htmlspecialchars($product['description']) . '</td>
                <td>' . htmlspecialchars($product['buying_price']) . '</td>
                <td>' . htmlspecialchars($product['selling_price']) . '</td>
                <td>' . htmlspecialchars($product['quantity']) . '</td>
                <td>' . htmlspecialchars($product['category_id']) . '</td>
                <td>' . htmlspecialchars($product['account_id']) . '</td>
                <td>' . htmlspecialchars($product['vendor_id']) . '</td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editProductModal" data-id="' . $product['id'] . '" data-name="' . htmlspecialchars($product['name']) . '" data-description="' . htmlspecialchars($product['description']) . '" data-buying_price="' . htmlspecialchars($product['buying_price']) . '" data-selling_price="' . htmlspecialchars($product['selling_price']) . '" data-quantity="' . htmlspecialchars($product['quantity']) . '" data-category_id="' . $product['category_id'] . '" data-account_id="' . $product['account_id'] . '" data-vendor_id="' . $product['vendor_id'] . '">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="' . $product['id'] . '">Delete</button>
                </td>
            </tr>';
        }
    } else {
        $output .= '<tr><td colspan="9">No products found.</td></tr>';
    }
    echo $output;
}
?>
