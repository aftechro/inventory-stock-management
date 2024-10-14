<?php
// Include the database connection
require '../db.php';

// Function to handle product search when the user types in the search box
if (isset($_GET['query'])) {
    $searchTerm = $conn->real_escape_string($_GET['query']);
    
    // Query the database to find products matching the search term
    $sql = "SELECT id, name, description, quantity, selling_price FROM products 
            WHERE name LIKE '%$searchTerm%' LIMIT 10";
    $result = $conn->query($sql);

    // Prepare the results as JSON, with HTML entities encoded
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['name'] = htmlentities($row['name'], ENT_QUOTES, 'UTF-8');
        $row['description'] = htmlentities($row['description'], ENT_QUOTES, 'UTF-8');
        $products[] = $row;
    }

    // Return the results as JSON and exit
    echo json_encode($products);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Quote</title>
    <link rel="stylesheet" href="../assets/css/bootstrap5.3.min.css">
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container my-5">
    <h2>Search Products and Create Quote</h2>

    <!-- Search Product Input -->
    <input type="text" id="searchProduct" class="form-control mb-3" placeholder="Start typing product name...">

    <!-- Product Search Result List -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Selling Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="productList">
            <!-- Live search results will be populated here -->
        </tbody>
    </table>

    <!-- Selected Products Table -->
    <h3>Selected Products</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Stock Status</th>
                <th>Unit Price</th>
                <th>Quantity Needed</th>
                <th>Total Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="selectedProducts">
            <!-- Added products will show up here -->
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td id="totalPrice" style="font-weight: bold;">€0.00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Buttons for Show Quote, Reset, and Submit to Xero -->
    <div class="d-flex justify-content-between my-3">
        <div>
            <button class="btn btn-primary" id="showQuote">Show Quote</button>
            <button class="btn btn-secondary" id="resetTable">Reset</button>
        </div>
        <button class="btn btn-success" id="submitXero">Submit to Xero (Coming Soon)</button>
    </div>

    <!-- Generated Quote View -->
    <div id="quoteView" class="mt-5">
        <!-- Quote table will be displayed here after clicking Show Quote -->
    </div>

</div>

<script>
$(document).ready(function() {
    let selectedProducts = [];

    // Predictive search as user types
    $('#searchProduct').on('input', function() {
        let query = $(this).val();

        // Fetch products using API when at least 2 characters are entered
        if (query.length >= 2) {
            $.getJSON('quote.php?query=' + query, function(data) {
                let productList = '';
                if (data.length > 0) {
                    data.forEach(product => {
                        let stockStatus = getStockStatus(product.quantity);
                        productList += `
                            <tr>
                                <td>${product.name}</td>
                                <td>${product.description}</td>
                                <td><span class="${stockStatus.class}">${product.quantity}</span></td>
                                <td>€${product.selling_price}</td>
                                <td><button class="btn btn-primary addProduct" data-id="${product.id}" data-name="${product.name}" data-description="${product.description}" data-price="${product.selling_price}" data-quantity="${product.quantity}">Add</button></td>
                            </tr>
                        `;
                    });
                } else {
                    productList = '<tr><td colspan="5">No products found.</td></tr>';
                }
                $('#productList').html(productList);
            });
        } else {
            $('#productList').html(''); // Clear table if search is too short
        }
    });

    // Add product to selected products table
    $(document).on('click', '.addProduct', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let description = $(this).data('description');
        let price = parseFloat($(this).data('price'));
        let quantity = $(this).data('quantity');
        let rowNum = selectedProducts.length + 1;

        let stockStatus = getStockStatus(quantity);

        let row = `
            <tr>
                <td>${rowNum}</td>
                <td><b>${name}</b><br><small>${description}</small></td>
                <td><span class="${stockStatus.class}">${stockStatus.label} (${quantity})</span></td>
                <td>€${price.toFixed(2)}</td>
                <td><input type="number" class="form-control quantityInput" value="1" min="1" data-price="${price.toFixed(2)}"></td>
                <td class="totalRow">€${price.toFixed(2)}</td>
                <td><button class="btn btn-danger removeProduct">X</button></td>
            </tr>
        `;

        selectedProducts.push({ id, name, description, price, quantity });
        $('#selectedProducts').append(row);
        updateTotal();
        
        // Clear search input after adding product
        $('#searchProduct').val('');
        $('#productList').html(''); // Clear previous search results
    });

    // Function to determine stock status and color
    function getStockStatus(quantity) {
        if (quantity == 0) {
            return { class: 'text-danger', label: 'Out of Stock' };
        } else if (quantity > 0 && quantity <= 4) {
            return { class: 'text-warning', label: 'Low Stock' };
        } else {
            return { class: 'text-success', label: 'In Stock' };
        }
    }

    // Remove product
    $(document).on('click', '.removeProduct', function() {
        $(this).closest('tr').remove();
        selectedProducts.splice($(this).closest('tr').index(), 1);
        updateTotal();
    });

    // Update total price when quantity changes
    $(document).on('input', '.quantityInput', function() {
        let quantity = parseFloat($(this).val());
        let price = parseFloat($(this).data('price'));
        let total = quantity * price;

        $(this).closest('tr').find('.totalRow').text('€' + total.toFixed(2));
        updateTotal();
    });

    // Update total price
    function updateTotal() {
        let total = 0;
        $('#selectedProducts tr').each(function() {
            total += parseFloat($(this).find('.totalRow').text().substring(1)); // Removing "€" and parsing to float
        });
        $('#totalPrice').text('€' + total.toFixed(2)).css('font-weight', 'bold'); // Bold total
    }

    // Show Quote Button functionality - generate the quote table under selected products
    $('#showQuote').click(function() {
        if (selectedProducts.length > 0) {
            let quoteTable = `
                <h4>Quote Summary</h4>
                <table class="table table-bordered" id="quoteSummaryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Unit Price</th>
                            <th>QTY</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            selectedProducts.forEach((product, index) => {
                let qty = $(`#selectedProducts tr:eq(${index}) .quantityInput`).val(); // Get user-inputted quantity
                let totalPrice = (product.price * qty).toFixed(2); // Calculate total price
                quoteTable += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><b>${product.name}</b><br><small>${product.description}</small></td>
                        <td>€${product.price.toFixed(2)}</td>
                        <td>${qty}</td>
                        <td>€${totalPrice}</td>
                    </tr>
                `;
            });

            quoteTable += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="font-weight: bold;">Total</td>
                            <td style="font-weight: bold;">€${$('#totalPrice').text().substring(1)}</td>
                        </tr>
                    </tfoot>
                </table>
                <button class="btn btn-secondary" id="copyTable">Copy Table</button>
                <button class="btn btn-success">Submit to Xero (Coming Soon)</button>
            `;

            $('#quoteView').html(quoteTable);
        } else {
            alert('No products selected to quote.');
        }
    });

    // Reset button functionality
    $('#resetTable').click(function() {
        $('#selectedProducts').empty();
        $('#quoteView').html(''); // Clear the quote view as well
        selectedProducts = [];
        updateTotal();
    });

    // Copy quote summary to clipboard with formatting
    $(document).on('click', '#copyTable', function() {
        let quoteTableHtml = document.getElementById('quoteSummaryTable');
        let range = document.createRange();
        range.selectNode(quoteTableHtml);
        window.getSelection().removeAllRanges(); // Clear current selections
        window.getSelection().addRange(range); // Select the quote table
        document.execCommand('copy'); // Copy to clipboard
        window.getSelection().removeAllRanges(); // Clear selection after copying
        alert('Quote copied to clipboard with formatting!');
    });
});
</script>
</body>
</html>
