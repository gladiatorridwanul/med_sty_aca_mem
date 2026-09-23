$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Add active class to current nav link
    var currentPath = window.location.pathname;
    $('.sidebar .nav-link').each(function() {
        if (currentPath.indexOf($(this).attr('href')) !== -1) {
            $(this).addClass('active');
        }
    });
});