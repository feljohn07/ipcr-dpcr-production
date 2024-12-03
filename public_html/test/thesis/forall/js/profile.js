document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#profile-form')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../forall/profile_action.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Profile Successfully Updated!');
                // Call the function to reset the form to view mode
                disableEditing();
            } else {
                alert('An error occurred while updating the profile.');
            }
        };

        xhr.send(formData);
    }
});

function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            document.querySelector('.profile-picture img').src = e.target.result;
        }

        reader.readAsDataURL(input.files[0]); // convert to base64 string
    }
}

function enableEditing() {
    var fields = document.querySelectorAll('#profile-form input[type="text"], #profile-form input[type="email"], #profile-form input[type="tel"], #profile-form input[type="file"], #profile-form select');
    fields.forEach(function(field) {
        if (field.id !== 'idnumber' && field.id !== 'college' && field.id !== 'role' && field.id !== 'designation') {
            field.removeAttribute('readonly');
            field.removeAttribute('disabled');
        }
        if (field.type === 'file') {
            field.removeAttribute('disabled');
        }
    });
    document.getElementById('edit-btn').style.display = 'none';
    document.getElementById('save-btn').style.display = 'inline-block';
}

function disableEditing() {
    var fields = document.querySelectorAll('#profile-form input[type="text"], #profile-form input[type="email"], #profile-form input[type="tel"], #profile-form input[type="file"], #profile-form select');
    fields.forEach(function(field) {
        field.setAttribute('readonly', true);
        field.setAttribute('disabled', true);
    });
    document.getElementById('edit-btn').style.display = 'inline-block';
    document.getElementById('save-btn').style.display = 'none';
}