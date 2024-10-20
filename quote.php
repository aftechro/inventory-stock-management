<?php

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
require 'db.php';

$success_message = '';
$error_message = '';

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

// Function to handle customer search when the user types in the search box
if (isset($_GET['customer_query'])) {
    $customerTerm = $conn->real_escape_string($_GET['customer_query']);

    // Query the database to find customers matching the search term
    $sql = "SELECT id, account_number, company_name, contact_name, contact_phone FROM customers 
            WHERE (account_number LIKE '%$customerTerm%' 
            OR company_name LIKE '%$customerTerm%' 
            OR contact_name LIKE '%$customerTerm%' 
            OR contact_phone LIKE '%$customerTerm%') 
            AND options LIKE '%allow quote%' LIMIT 10"; // Updated line

    $result = $conn->query($sql);

    // Check if there was an error in the query
    if (!$result) {
        echo "SQL Error: " . $conn->error;
        exit;
    }

    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }

    // Return the results as JSON
    echo json_encode($customers);
    exit;
}

// Function to handle the creation of a quote
if (isset($_POST['create_quote'])) {
    $customer_id = $conn->real_escape_string($_POST['customer_id']);
    $quote_number = $conn->real_escape_string($_POST['quote_number']);
    
    // Default user ID for guests
    $created_by = 0; // or use NULL if your schema allows it

    // Fetch customer details
    $customerQuery = "SELECT * FROM customers WHERE id = '$customer_id'";
    $customerResult = $conn->query($customerQuery);
    $customer = $customerResult->fetch_assoc();

    if ($customer) {
        // Insert quote into quotes table
        $quoteSql = "INSERT INTO quotes (account_number, company_name, address, city, state, zip, country, email, landline, mobile, website, contact_name, contact_email, contact_phone, created_at, created_by)
                     VALUES ('{$customer['account_number']}', '{$customer['company_name']}', '{$customer['address']}', '{$customer['city']}', '{$customer['state']}', '{$customer['zip']}', '{$customer['country']}', '{$customer['email']}', '{$customer['landline']}', '{$customer['mobile']}', '{$customer['website']}', '{$customer['contact_name']}', '{$customer['contact_email']}', '{$customer['contact_phone']}', NOW(), '$created_by')";

        if ($conn->query($quoteSql)) {
            $quote_id = $conn->insert_id;

            // Check if products are set and insert each product into quote_items table
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $product) {
                    $product_name = $conn->real_escape_string($product['name']);
                    $quantity = (int) $product['quantity'];
                    $unit_price = (float) $product['unit_price'];
                    $total_price = $unit_price * $quantity;

                    $quoteItemSql = "INSERT INTO quote_items (quote_id, product_name, quantity, unit_price, total_price, created_at)
                                     VALUES ('$quote_id', '$product_name', '$quantity', '$unit_price', '$total_price', NOW())";
                    $conn->query($quoteItemSql);
                }
                
                // Redirect to show quote or success message
                header("Location: quote.php?success=1&quote_id=$quote_id");
                exit;
            } else {
                $error_message = 'No products selected. Please select at least one product.';
            }
        } else {
            $error_message = 'Error creating quote: ' . $conn->error;
        }
    } else {
        $error_message = 'Customer not found.';
    }
}

// Function to generate the next quote number
function getNextQuoteNumber($conn) {
    $sql = "SELECT id FROM quotes ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    $lastQuote = $result->fetch_assoc();

    $nextQuoteNumber = isset($lastQuote['id']) ? 'QTE-' . str_pad($lastQuote['id'] + 1, 4, '0', STR_PAD_LEFT) : 'QTE-0001';
    return $nextQuoteNumber;
}

include 'header.php';
include 'nav.php';
?>

<div class="container my-5">
    <h5>Search Customers and Products, Create Quote</h5><hr>

<!-- Customer and Product Search Cards in a Row -->
<div class="row mb-3">
    <!-- Customer Search Card -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header" style="background-color: #343a40; color: white;">
                <i class="fa fa-user" aria-hidden="true"></i> Search Customer
            </div>
            <div class="card-body" style="color: grey;">
                <blockquote class="blockquote mb-0">
                    <input type="text" id="searchCustomer" class="form-control mb-3" placeholder="Start typing customer details...">
                    <div id="selectedCustomer"></div>
                </blockquote>
            </div>
        </div>
    </div>

    <!-- Product Search Card -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header" style="background-color: #343a40; color: white;">
                <i class="fa fa-box" aria-hidden="true"></i> Search Product
            </div>
            <div class="card-body">
                <blockquote class="blockquote mb-0">
                    <input type="text" id="searchProduct" class="form-control mb-3" placeholder="Start typing product name...">
                    <div id="selectedProduct"></div>
                </blockquote>
            </div>
        </div>
    </div>
</div>

<!-- Product Search Result List -->
<table class="table table-bordered" id="productTable" style="display: none;">
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

    <div class="container mt-4">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Quote has been successfully saved!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlentities($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

<!-- Selected Products Table -->
<h5>Selected Products</h5><hr>
<div id="selectedProductsContainer" style="display: none;"> <!-- Initially hidden -->
    <form method="POST" id="quoteForm">
        <input type="hidden" name="customer_id" id="customer_id">
        <input type="hidden" name="quote_number" value="<?php echo getNextQuoteNumber($conn); ?>">
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
   
</div>



        <!-- Buttons for Show Quote, Reset, and Submit -->
        <div class="d-flex justify-content-between my-3">
            <div>
                <button class="btn btn-primary" id="showQuote">Show Quote</button>
                <button class="btn btn-secondary" id="resetTable">Reset</button>
            </div>
            <form method="POST" id="quoteForm">
            <button type="submit" name="create_quote" class="btn btn-success">Submit Quote</button>
        </div>
    </form>

    <!-- Generated Quote View -->
    <div id="quoteView" class="mt-5">
        <!-- Quote table will be displayed here after clicking Show Quote -->
    </div>


</div>

<script>
$(document).ready(function() {
    let selectedProducts = [];

    
// Customer search functionality
$(document).ready(function() {
    let selectedProducts = [];

// Customer search functionality (remains unchanged)
$('#searchCustomer').on('input', function() {
    let query = $(this).val();
    if (query.length >= 2) {
        $.getJSON('quote.php?customer_query=' + query, function(data) {
            let customerList = '';
            if (data.length > 0) {
                data.forEach(customer => {
                    customerList += `
                        <div class="customer-result" data-id="${customer.id}">
                            ${customer.company_name} (${customer.account_number}) - ${customer.contact_name}, ${customer.contact_phone}
                        </div>
                    `;
                });
            } else {
                customerList = '<div>No customers found.</div>';
            }
            $('#selectedCustomer').html(customerList);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX error: ', textStatus, errorThrown);
        });
    } else {
        $('#selectedCustomer').html('');
    }
});

// Select customer and add remove button
$(document).on('click', '.customer-result', function() {
    let customerId = $(this).data('id');
    let customerText = $(this).text();
    
    // Clear the search input after selection
    $('#searchCustomer').val('');
    
    // Store customer ID in hidden input
    $('#customer_id').val(customerId);
    
    // Display selected customer in an <h5> tag
    $('#selectedCustomer').html(`
        <h6 class="selected-customer">
            <strong>Selected customer:</strong><br> ${customerText}
            <button class="btn btn-danger btn-sm removeCustomer" title="Remove Customer">X</button>
        </h6>
    `);
});

// Remove selected customer
$(document).on('click', '.removeCustomer', function() {
    $('#searchCustomer').val(''); // Clear input
    $('#customer_id').val(''); // Clear hidden input
    $('#selectedCustomer').html(''); // Clear selected customer display
});




// Predictive product search as user types
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

                $('#productList').html(productList);
                $('#productTable').show(); // Show table if there are products
            } else {
                $('#productList').html(''); // Clear previous results
                $('#productTable').hide(); // Hide table if no products found
            }
        });
    } else {
        $('#productList').html(''); // Clear table if search is too short
        $('#productTable').hide(); // Hide table if search is too short
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

    // Show the table if products are added
    $('#selectedProductsContainer').show(); 

    // Clear search input and hide the table after adding product
    $('#searchProduct').val(''); // Clear search input
    $('#productList').html(''); // Clear previous search results
    $('#productTable').hide(); // Hide table
});

// Reset button functionality
$('#resetTable').click(function() {
    $('#selectedProducts').empty();
    $('#quoteView').html('');
    selectedProducts = [];
    updateTotal();
    $('#searchCustomer').val('');
    $('#customer_id').val('');
    $('#searchProduct').val(''); // Clear search input
    $('#productList').html(''); // Clear product list
    $('#productTable').hide(); // Hide table on reset

    // Hide the selected products container
    $('#selectedProductsContainer').hide(); 
});


// Add hidden inputs for products before form submission
$('#quoteForm').submit(function() {
    // Clear previous product inputs
    $('#quoteForm input[name^="products"]').remove();
    
    // Add selected products as hidden inputs
    selectedProducts.forEach((product, index) => {
        $('<input>').attr({
            type: 'hidden',
            name: 'products[' + index + '][name]',
            value: product.name
        }).appendTo('#quoteForm');

        $('<input>').attr({
            type: 'hidden',
            name: 'products[' + index + '][quantity]',
            value: $(`#selectedProducts tr:eq(${index}) .quantityInput`).val()
        }).appendTo('#quoteForm');

        $('<input>').attr({
            type: 'hidden',
            name: 'products[' + index + '][unit_price]',
            value: product.price.toFixed(2)
        }).appendTo('#quoteForm');
    });
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




// Show Quote Button functionality
$('#showQuote').click(function(event) {
    event.preventDefault(); // Prevent form submission
    if (selectedProducts.length > 0 && $('#customer_id').val()) {
        // Extract customer name and contact info without the "Remove Customer" button
        let selectedCustomerText = $('#selectedCustomer .selected-customer').contents().filter(function() {
            return this.nodeType === 3; // Node type 3 is text node
        }).text().trim(); // Get only the text node and trim it
        
        let quoteTable = `
            <h4>Quote Summary</h4>
            <p><b>Quote Number:</b> ${$('input[name="quote_number"]').val()}</p>
            <p><b>Customer:</b> ${selectedCustomerText}</p>
            <p><b>Created At:</b> ${new Date().toLocaleString()}</p>
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
            let qty = $(`#selectedProducts tr:eq(${index}) .quantityInput`).val();
            let totalPrice = (product.price * qty).toFixed(2);
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
            <button class="btn btn-primary" id="printQuote">Print Quote (soon)</button>
        `;

        $('#quoteView').html(quoteTable);

        // Scroll to the quote summary section
        $('html, body').animate({
            scrollTop: $('#quoteView').offset().top
        }, 800); // Smooth scroll to quoteView with a duration of 800ms
    } else {
        alert('No products selected or customer not chosen.');
    }
});
    
    
    

// Reset button functionality (remains unchanged)
$('#resetTable').click(function() {
    $('#selectedProducts').empty();
    $('#quoteView').html('');
    selectedProducts = [];
    updateTotal();
    $('#searchCustomer').val('');
    $('#customer_id').val('');
});



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
    







    
    $.getJSON('quote.php?customer_query=' + query, function(data) {
    console.log(data); // Log the response to check the returned data
    let customerList = '';
    if (data.length > 0) {
        data.forEach(customer => {
            customerList += `
                <div class="customer-result" data-id="${customer.id}">
                    ${customer.company_name} (${customer.account_number}) - ${customer.contact_name}, ${customer.contact_phone}
                </div>
            `;
        });
    } else {
        customerList = '<div>No customers found.</div>';
    }
    $('#selectedCustomer').html(customerList);
}).fail(function(jqXHR, textStatus, errorThrown) {
    // Handle any AJAX errors
    console.log('AJAX error: ', textStatus, errorThrown);
});

</script>

<!-- Include jQuery -->
<script src="assets/js/jquery-3.6.0.min.js"></script>

<!-- Include Bootstrap JS from jsDelivr -->
<script src="assets/js/bootstrap5.3.bundle.min.js"></script>


</body>
</html>
