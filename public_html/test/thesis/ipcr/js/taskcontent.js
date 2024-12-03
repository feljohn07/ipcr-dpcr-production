function ipcrTaskContent(page) {
    localStorage.setItem('lastTaskPage', page);

    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.button');
    buttons.forEach(button => {
        button.classList.remove('active');
    });

    // Add active class to the clicked button
    const activeButton = Array.from(buttons).find(button => {
        return button.getAttribute('onclick').includes(page);
    });
    if (activeButton) {
        activeButton.classList.add('active');
    }

    // Load the content
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        document.getElementById('ipcrtaskContent').innerHTML = this.responseText;
    }
    xhttp.open('GET', page, true);
    xhttp.send();
}

function ipcrLastTaskPage() {
    const lastTaskPage = localStorage.getItem('lastTaskPage');
    if (lastTaskPage) {
        ipcrTaskContent(lastTaskPage);
    } else {
        ipcrTaskContent('ipcrtaskspages/ipcrtask.php'); // Default page
    }
}

document.addEventListener('DOMContentLoaded', ipcrLastTaskPage);