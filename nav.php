<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current page filename
$is_admin = $_SESSION['role'] === 'admin'; // Check if the user is an admin
$username = htmlspecialchars($_SESSION['username']); 
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark-gray shadow-sm py-3">
    <div class="container">
        <!-- Branding with logo -->
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-warehouse me-2"></i> AFTECH Stock
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left-side Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'stock.php') ? 'active' : '' ?>" href="stock.php">
                        <i class="fas fa-file-alt"></i> Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'quote.php') ? 'active' : '' ?>" href="quote.php">
                        <i class="fas fa-database"></i> Quote
                    </a>
                </li>



                <!-- Admin Section: Manage Dropdown -->
                <?php if ($is_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= ($current_page == 'products.php' || $current_page == 'sql_import.php' || $current_page == 'categories.php' || $current_page == 'vendors.php' || $current_page == 'accounts.php') ? 'active' : '' ?>" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tools"></i> Manage
                        </a>
                        <ul class="dropdown-menu">
                			<li><a class="dropdown-item" href="customers.php">Customers</a></li>
                            <li><a class="dropdown-item" href="products.php">Products</a></li>
                            <li><a class="dropdown-item" href="sql_import.php">Import Products</a></li>
                            <li><a class="dropdown-item" href="categories.php">Categories</a></li>
                            <li><a class="dropdown-item" href="vendors.php">Vendors</a></li>
                            <li><a class="dropdown-item" href="accounts.php">Accounts</a></li>
                        </ul>
                    </li>
                

                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'users.php') ? 'active' : '' ?>" href="users.php">
                        <i class="fas fa-users-cog"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'logs.php') ? 'active' : '' ?>" href="logs.php">
                        <i class="fas fa-list"></i> Logs
                    </a>
                </li>
                
               <?php endif; ?> 
            </ul>
                

            <!-- Right-side User Info and Logout -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <span class="navbar-text me-3 dropdown-toggle" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i> <?= $username ?>
                    </span>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Custom Styles -->
<style>
    /* Main Navbar Styling */
    .bg-dark-gray {
        background-color: #2a2a2a; /* Clean, dark gray */
    }

    /* Boxed Layout for Navbar */
    .navbar {
        border-radius: 0; /* No rounded corners */
        padding: 0.5rem 1rem; /* Padding for top/bottom and sides */
    }

    /* Container Style */
    .container {
        max-width: 1200px; /* Maximum width for the navbar */
        margin: 0 auto; /* Center the container */
        padding: 0; /* Remove additional padding */
    }

    /* Brand styling */
    .navbar-brand {
        font-size: 1.5rem;
        color: #ffffff;
        transition: color 0.3s;
    }
    .navbar-brand:hover {
        color: #f39c12;
    }

    /* Navbar Links */
    .navbar-nav .nav-link {
        font-size: 1.1rem;
        padding: 0.75rem 1rem;
        color: rgba(255,255,255,0.85);
        transition: all 0.3s ease;
    }

    /* Hover and Active Link */
    .navbar-nav .nav-link:hover {
        color: #f39c12;
        border-bottom: 2px solid #f39c12;
    }
    .navbar-nav .nav-link.active {
        color: #f39c12;
        border-bottom: 2px solid #f39c12;
    }

    /* Dropdown Menu */
    .dropdown-menu {
        background-color: #333;
        border-radius: 6px;
        box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
    }
    .dropdown-item {
        color: rgba(255,255,255,0.85);
        transition: all 0.2s ease;
    }
    .dropdown-item:hover {
        background-color: rgba(255,255,255,0.1);
        color: #f39c12;
    }

    /* User Info and Logout Button */
    .navbar-text {
        font-size: 1.1rem;
        color: rgba(255,255,255,0.85);
        cursor: pointer; /* Change cursor to indicate dropdown */
    }

    /* Box shadows */
    .navbar {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    /* Margin adjustments for navbar */
    .navbar-nav {
        margin-left: 30px; /* Space between logo and menu */
    }
</style>
