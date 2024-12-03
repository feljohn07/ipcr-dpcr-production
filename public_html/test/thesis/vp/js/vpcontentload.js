function loadContent(page) {
    localStorage.setItem('lastPage', page);
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        document.getElementById('mainContent').innerHTML = this.responseText;
        setActiveLink(page);
        if (page === 'taskcontentpages/taskContent.html') {
            loadLastTaskPage(); // Load the last task page when taskContent.html is loaded
        }
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

function loadTaskContent(page) {
    localStorage.setItem('lastTaskPage', page);
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        document.getElementById('taskContent').innerHTML = this.responseText;
    }
    xhttp.open('GET', page, true);
    xhttp.send();
}

function loadLastTaskPage() {
    const lastTaskPage = localStorage.getItem('lastTaskPage');
    if (lastTaskPage) {
        loadTaskContent(lastTaskPage);
    } else {
        loadTaskContent('alltask.html'); // Default task page
    }
}

document.addEventListener('DOMContentLoaded', loadLastPage);