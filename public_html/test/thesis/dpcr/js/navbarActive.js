document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.navbar a');

    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Maintain the active state on page reload
    const lastPage = localStorage.getItem('lastPage');
    if (lastPage) {
        navLinks.forEach(link => {
            if (link.getAttribute('onclick').includes(lastPage)) {
                link.classList.add('active');
            }
        });
    }
});


