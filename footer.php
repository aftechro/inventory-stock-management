

    <script src="assets/js/bootstrap4.5.2.bundle.min.js"></script>	
	<script src="assets/js/bootstrap5.3.bundle.min.js"></script>
    <script src="assets/js/jquery-3.5.1.slim.min.js"></script>
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/jquery-ui.minjs"></script>
    <script src="assets/js/popper.min.js"></script>


    <script>
        // JavaScript function to change tabs and scroll to the table when cards are clicked
        function changeTab(tabName) {
            $('.nav-tabs .nav-link').removeClass('active');
            $('.tab-pane').removeClass('show active');

            if (tabName === 'out-of-stock') {
                $('#out-of-stock-tab').addClass('active');
                $('#out-of-stock').addClass('show active');
                $('html, body').animate({
                    scrollTop: $('#out-of-stock').offset().top
                }, 500); // Scroll to out of stock table
            } else if (tabName === 'low-stock') {
                $('#low-stock-tab').addClass('active');
                $('#low-stock').addClass('show active');
                $('html, body').animate({
                    scrollTop: $('#low-stock').offset().top
                }, 500); // Scroll to low stock table
            }
        }

        // Function to scroll to top
        function scrollToTop() {
            $('html, body').animate({scrollTop: 0}, 500); // Scroll to top smoothly
        }

        // Show/Hide Go to Top Button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('#goTopBtn').fadeIn(); // Show button if scrolled down
            } else {
                $('#goTopBtn').fadeOut(); // Hide button if near top
            }
        });
    </script>
    
    
    
    
    
      <!-- Go to Top Button -->
    <button id="goTopBtn" onclick="scrollToTop()">↑</button>  
    
</body>
</html>
