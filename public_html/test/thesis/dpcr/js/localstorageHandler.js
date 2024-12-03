// Function to load content into the content area
function loadContent(url) {
    // Check if the browser is online
    if (navigator.onLine) {
        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                $('#mainContent').html(data);
                // Reattach form submission handler after content is loaded
                attachFormHandler();
            },
            error: function() {
                $('#mainContent').html('<p>Error loading content. Please try again later.</p>');
            }
        });
    } else {
        // Handle offline scenario
        $('#mainContent').html('<p>You are offline. Please check your connection.</p>');
    }
}

// Function to set the active link
function setActive(element) {
    // Remove the active class from all links
    const links = document.querySelectorAll('.navbar a');
    links.forEach(link => link.classList.remove('active'));

    // Add the active class to the clicked link
    element.classList.add('active');

    // Save the active link's href in localStorage
    localStorage.setItem('activeLink', element.getAttribute('href'));

    // Load content
    loadContent(element.getAttribute('href'));
}

// Load the active link on page load based on localStorage
function loadActiveLink() {
    const activeLink = localStorage.getItem('activeLink');
    if (activeLink) {
        const links = document.querySelectorAll('.navbar a');
        links.forEach(link => {
            if (link.getAttribute('href') === activeLink) {
                link.classList.add('active');
                loadContent(activeLink);
            }
        });
    } else {
        // Load the default page if no active link is set
        const defaultPage = 'taskcontentpages/viewsubmittedtasks.php';
        loadContent(defaultPage);
        document.querySelector('.navbar a[href="' + defaultPage + '"]').classList.add('active');
    }
}

// Attach event listeners to all links
$(document).ready(function() {
    $('.navbar a').click(function(e) {
        e.preventDefault();
        setActive(this);
    });

    // Load the active link when the page is loaded
    loadActiveLink();
});