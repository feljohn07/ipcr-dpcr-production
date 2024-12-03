function loadContent(page) {
    localStorage.setItem('lastPage', page);
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        document.getElementById('mainContent').innerHTML = this.responseText;
        setActiveLink(page);
    }
    xhttp.open('GET', page, true);
    xhttp.send();
}

function loadLastPage() {
    const lastPage = localStorage.getItem('lastPage');
    if (lastPage) {
        loadContent(lastPage);
    } else {
        loadContent('vptask.php'); // Default page
    }
}

function setActiveLink(page) {
    const links = document.querySelectorAll('.navbar a');
    links.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('onclick').includes(page)) {
            link.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', loadLastPage);