function loadContent(url) {
    $('#mainContent').load(url, function() {
        // Store the URL in localStorage
        localStorage.setItem('lastContent', url);
    });
}


// Load the stored content if available
var lastContent = localStorage.getItem('lastContent');
console.log("Last content URL:", lastContent); // Debugging line

if (lastContent) {
    loadContent(lastContent);
} else {
    // Default content to show if no previous content is stored
    loadContent('contentloaderpages/registerpage.php');
}