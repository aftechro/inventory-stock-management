<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'export') {
    // Fetch all products along with category, account, and vendor names
    $sql = "
        SELECT 
            p.id, 
            p.name, 
            p.description, 
            p.buying_price, 
            p.selling_price, 
            p.quantity, 
            c.name AS category_name, 
            a.name AS account_name, 
            v.name AS vendor_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN accounts a ON p.account_id = a.id
        LEFT JOIN vendors v ON p.vendor_id = v.id
    ";
    
    $products = $conn->query($sql);

    if ($products->num_rows > 0) {
        // Set headers to download the file
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products.csv"');

        $output = fopen('php://output', 'w');
        
        // Add the header of the CSV file
        fputcsv($output, ['ID', 'Name', 'Description', 'Buying Price', 'Selling Price', 'Quantity', 'Category', 'Account', 'Vendor']);

        // Add data to CSV
        while ($product = $products->fetch_assoc()) {
            fputcsv($output, [
                $product['id'],
                $product['name'],
                $product['description'],
                $product['buying_price'],
                $product['selling_price'],
                $product['quantity'],
                $product['category_name'],
                $product['account_name'],
                $product['vendor_name']
            ]);
        }

        fclose($output);
        exit;
    } else {
        echo "No products found.";
    }
} else {
    echo "Invalid request.";
}
?>
