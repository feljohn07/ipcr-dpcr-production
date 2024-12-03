function loadContent(page) {
    localStorage.setItem('lastPage', page);
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
        document.getElementById('mainContent').innerHTML = this.responseText;
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
        loadContent('../forall/profile.php'); // Default page
    }
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
        loadTaskContent('taskcontentpages/viewsubmittedtask.php'); // Default task page
    }
}

document.addEventListener('DOMContentLoaded', loadLastTaskPage);

function loadFormData() {
    var savedFormData = localStorage.getItem('createTaskFormData');
    if (savedFormData) {
        var formDataObj = JSON.parse(savedFormData);
        for (var key in formDataObj) {
            if (formDataObj.hasOwnProperty(key)) {
                var element = document.querySelector('[name="' + key + '"]');
                if (element) {
                    element.value = formDataObj[key];
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', loadLastPage);