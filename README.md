 **NOTE** - Still working on few bits to make it better, as time permits, but keep an eye on it!


![ezgif-6-8538a8bec1](https://github.com/user-attachments/assets/009987c0-a9e1-499d-839c-2065adb518e5)


# Stock Management System

A comprehensive stock management solution that allows users to efficiently manage inventory, add products, and monitor stock levels in real-time. This application enables scanning QR codes with a mobile camera for quick access to product details, making inventory management easier and more effective.


## Features

### 1. Inventory Management
- **Update Inventory**: Easily manage stock levels by scanning QR codes, enabling quick updates for both stock-in and stock-out operations.

### 2. Real-Time Stock Monitoring
- **Live Updates**: Stay informed with real-time updates on stock levels, including alerts for out-of-stock and low-stock products.

### 3. Detailed Inventory Insights
- **IN/OUT Tracking**: View detailed information about inventory transactions whenever stock levels change, ensuring accountability and transparency.

## Installation

1. **Set Up Database**:
   - Create the SQL tables as shown in the `stock-db.sql` file.
   - Add your first user via phpMyAdmin, ensuring that the password type is set using the `password_hash()` PHP function (select the password hashing option in phpMyAdmin when inserting new user data).
   - ![image](https://github.com/user-attachments/assets/09aa1dda-35f8-412c-aca7-f72ec595a315)


2. **Upload Files**:
   - Add your application files to your web server’s directory (e.g., `www/html`).
   - **Download fontawesome** and extract files inside assets folder

3. **Configure Base URL**:
   - Change the base URL in `stock.php` on line 17:
     ```php
     // Base URL for the application
     $base_url = "https://stock.yourdomain.com"; // Change to your domain
     ```

## Usage

1. **Set Up Accounts**:
   - Add accounts, categories, and vendors as needed.

2. **Manage Products**:
   - Create new products via the "Manage Products" section and export them as CSV, or manually add products.

3. **Import Products**:
   - Use the CSV file to populate the exported CSV file. **REMOVE THE ID COLUMN** of exported CSV file. After that, use the "Import Products" option in the management menu.

4. **Generate QR Codes**:
   - Click on the stock section to generate QR codes. A folder named `uploads/qr` will be created to store these codes.
     
5. **Generate Quotes**:
   - Go to yourdomain.com/app/quote.php to create quotes based on your product list and quantity available

## Conclusion

This Stock Management System provides a robust solution for businesses looking to streamline their inventory processes. With the ability to scan QR codes for quick access and real-time stock updates, you can ensure that your inventory is always accurate and up-to-date.

---

Feel free to customize this further or let me know if you need any additional changes or information!
