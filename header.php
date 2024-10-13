<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">	
    <meta charset="UTF-8">
    <title>Stock</title>

    <link rel="stylesheet" href="assets/css/bootstrap5.3.min.css" >
    <link rel="stylesheet" href="assets/css/bootstrap5.3.1min.css">
    <link rel="stylesheet" href="assets/css/bootstrap4.5.2.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.css">  
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css" >






    <style>


        /* Existing styles */
        .card {
            position: relative;
            overflow: hidden;
            height: 100%; /* Ensures all cards are equal height */
            text-align: center;
            color: white; /* Text color for card content */
            padding: 20px; /* Padding for card content */
            border-radius: 15px; /* Rounded corners */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); /* Shadow effect */
            transition: transform 0.2s; /* Smooth hover effect */
            cursor: pointer; /* Pointer cursor to indicate clickable cards */
        }

        .card:hover {
            transform: scale(1.05); /* Slight zoom effect on hover */
        }

        .card-bg-icon {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0; /* Place it behind the text */
            opacity: 0.1; /* Faded background */
            font-size: 10rem; /* Adjust icon size */
            color: white; /* Icon color */
            text-align: center;
            line-height: 200px; /* Center the icon vertically */
        }

        .card-body {
            position: relative;
            z-index: 1; /* Ensure text is above the overlay */
        }

        .card-title {
            font-weight: bold; /* Make title bold */
        }

        .display-4 {
            font-size: 3rem; /* Adjust size of the number */
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .display-4 {
                font-size: 2rem; /* Smaller number on mobile */
            }

            .card-bg-icon {
                font-size: 6rem; /* Smaller icon on mobile */
            }
        }

        /* Custom styling for tables */
        .table {
            margin-top: 20px;
            border-radius: 10px; /* Rounded corners for table */
            overflow: hidden; /* Ensure rounded corners are applied */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        .table thead th {
            background-color: #343a40; /* Dark background for header */
            color: white; /* White text for header */
            font-weight: bold; /* Bold font for header */
            text-align: left; /* Align text to the left */
        }

        .table tbody tr:hover {
            background-color: #f8f9fa; /* Light gray background on row hover */
        }

        /* Container for titles and tables */
        .table-container {
            display: flex;
            flex-direction: column; /* Stack title above table */
            margin-top: 20px; /* Space above the table */
        }

        /* Styling for titles */
        .table-title {
            margin-bottom: 20px;
            font-size: 1.5rem; /* Font size for titles */
            font-weight: bold; /* Bold titles */
        }

        /* Custom styles for table content */
        .table tbody td {
            text-align: left; /* Left aligned text in body */
        }

/* Go to Top Button Styles */
#goTopBtn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: none; /* Initially hidden */
    background-color: #343a40; /* Dark grey background */
    color: white;
    border: none;
    border-radius: 5px; /* Slightly rounded corners */
    width: 60px; /* Width of the square */
    height: 60px; /* Height of the square */
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    text-align: center; /* Center the icon horizontally */
    line-height: 60px; /* Center the icon vertically */
    font-size: 28px; /* Increase the icon size */
}

    </style>
</head>
<body>
