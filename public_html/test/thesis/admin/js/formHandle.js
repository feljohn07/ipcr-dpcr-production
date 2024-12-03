document.addEventListener('DOMContentLoaded', function () {
    // Handle form submission for RDM
    var roleForm = document.getElementById('roleForm');
    if (roleForm) {
        roleForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(this);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'contentloaderpages/functions/rdm_action.php', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('User updated successfully!');
                    location.reload();
                } else {
                    alert('An error occurred while updating the user.');
                }
            };
            xhr.send(formData);
        });
    }

    // Handle form submission for Recomm App
    var recommappForm = document.getElementById('recommappForm');
    if (recommappForm) {
        recommappForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(this);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'contentloaderpages/functions/recom_action.php', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('User updated successfully!');
                    location.reload();
                } else {
                    alert('An error occurred while updating the user.');
                }
            };
            xhr.send(formData);
        });
    }

    // Handle form submission for Registration
    var registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(this);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'contentloaderpages/functions/register_action.php', true);

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert(response.message);
                            registrationForm.reset();
                        } else {
                            alert(response.message);
                        }
                    } catch (e) {
                        alert('Failed to parse response.');
                    }
                } else {
                    alert('An error occurred while processing your request.');
                }
            };

            xhr.onerror = function () {
                alert('An error occurred while processing your request.');
            };

            xhr.send(formData);
        });
    }
});