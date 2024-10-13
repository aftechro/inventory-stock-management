<?php
session_start();
require 'db.php';

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Function to import data from a CSV or an XLSX file
function importData($filePath)
{
    global $conn;

    // Use finfo to get the MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    echo "<div class='alert alert-info'>Detected MIME type: " . htmlspecialchars($mimeType) . "</div>";

    if ($mimeType === 'text/plain' || $mimeType === 'text/csv') {
        // Read CSV file
        $file = fopen($filePath, 'r');
        if ($file === false) {
            die("<div class='alert alert-danger'>Error opening CSV file.</div>");
        }
        $header = fgetcsv($file); // Get the first row as header
        if ($header === false) {
            die("<div class='alert alert-danger'>Error reading CSV header.</div>");
        }
        
        // Trim whitespace and convert to lowercase for matching
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);
        
        echo "<div class='alert alert-info'>CSV header detected: " . htmlspecialchars(implode(", ", $header)) . "</div>";

        // Read rows
        while (($row = fgetcsv($file)) !== false) {
            echo "<div class='alert alert-info'>Read row: " . htmlspecialchars(implode(", ", $row)) . "</div>";  // Output the row read
            processRow($row, $header);
        }
        fclose($file);
    } elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        // Handle XLSX files here...
        // (Code for handling XLSX files will be similar to CSV handling)
    } else {
        die("<div class='alert alert-danger'>Unsupported file type: " . htmlspecialchars($mimeType) . "</div>");
    }
}

// Function to process each row
function processRow($row, $header)
{
    global $conn;

    // Map headers to indices (using lowercase for matching)
    $nameIndex = array_search('name', $header);
    $descriptionIndex = array_search('description', $header);
    $buyingPriceIndex = array_search('buying price', $header);
    $sellingPriceIndex = array_search('selling price', $header);
    $quantityIndex = array_search('quantity', $header);
    $categoryIndex = array_search('category', $header);
    $accountsIndex = array_search('accounts', $header);
    $vendorIndex = array_search('vendor', $header);

    // Check if all necessary indices are found
    if ($nameIndex === false || $descriptionIndex === false || 
        $buyingPriceIndex === false || $sellingPriceIndex === false || 
        $quantityIndex === false || $categoryIndex === false || 
        $accountsIndex === false || $vendorIndex === false) {
        echo "<div class='alert alert-danger'>One or more required headers are missing.</div>";
        echo "Header mapping:<br>";
        echo "Name Index: $nameIndex<br>";
        echo "Description Index: $descriptionIndex<br>";
        echo "Buying Price Index: $buyingPriceIndex<br>";
        echo "Selling Price Index: $sellingPriceIndex<br>";
        echo "Quantity Index: $quantityIndex<br>";
        echo "Category Index: $categoryIndex<br>";
        echo "Accounts Index: $accountsIndex<br>";
        echo "Vendor Index: $vendorIndex<br>";
        return;
    }

    // Extract data
    $name = isset($row[$nameIndex]) ? $row[$nameIndex] : null;
    $description = isset($row[$descriptionIndex]) ? $row[$descriptionIndex] : null;
    $buyingPrice = isset($row[$buyingPriceIndex]) ? $row[$buyingPriceIndex] : null;
    $sellingPrice = isset($row[$sellingPriceIndex]) ? $row[$sellingPriceIndex] : null;
    $quantity = isset($row[$quantityIndex]) ? $row[$quantityIndex] : null;
    $category = isset($row[$categoryIndex]) ? $row[$categoryIndex] : null;
    $account = isset($row[$accountsIndex]) ? $row[$accountsIndex] : null;
    $vendor = isset($row[$vendorIndex]) ? $row[$vendorIndex] : null;

    // Show values to be inserted
    echo "<div class='alert alert-info'>Values to insert: Name: $name, Description: $description, Buying Price: $buyingPrice, Selling Price: $sellingPrice, Quantity: $quantity, Category: $category, Account: $account, Vendor: $vendor</div>";

    // Handle category
    $categoryId = insertIfNotExists($conn, 'categories', $category);
    
    // Handle account
    $accountId = insertIfNotExists($conn, 'accounts', $account);
    
    // Handle vendor
    $vendorId = insertIfNotExists($conn, 'vendors', $vendor);

    // Insert product
    $stmt = $conn->prepare("INSERT INTO products (name, description, buying_price, selling_price, quantity, category_id, account_id, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddiiii", $name, $description, $buyingPrice, $sellingPrice, $quantity, $categoryId, $accountId, $vendorId);
    
    if ($stmt->execute() === false) {
        echo "<div class='alert alert-danger'>Failed to insert product: " . htmlspecialchars($stmt->error) . "</div>";
    } else {
        echo "<div class='alert alert-success'>Inserted product successfully.</div>";
    }
}

// Function to insert data if not exists and return the ID
function insertIfNotExists($conn, $table, $name)
{
    if ($name === null || trim($name) === '') {
        return null; // Avoid inserting empty names
    }

    $stmt = $conn->prepare("SELECT id FROM $table WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id']; // Return existing ID
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO $table (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute() === false) {
            echo "<div class='alert alert-danger'>Failed to insert into $table: " . htmlspecialchars($stmt->error) . "</div>";
        }
        return $conn->insert_id; // Return new ID
    }
}

// Check if a file is uploaded only when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $filePath = $_FILES['file']['tmp_name'];
    importData($filePath);
}

$conn->close();
?>


    <?php require 'header.php'; require 'nav.php'; ?>

    <div class="container mt-5">
        <h2>Import Data</h2>
        <form action="sql_import.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="file" class="form-label">Choose a file</label>
                <input type="file" class="form-control" id="file" name="file" accept=".csv, .xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">Import Data</button>
        </form>
    </div>

    <?php require 'footer.php'; ?>
