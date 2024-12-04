<?php
// Database connection
include __DIR__ . '/../../../dbconnections/config.php';

// Include the sendEmail function
include __DIR__ . '/../includes/send_email.php';
include __DIR__ . '/../includes/check_email_if_exists.php';

// check first if email already exist
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = isset($_POST['email']) ?? $_POST['email'];

    // Check if the email exists in the database
    $sql = "SELECT idnumber FROM usersinfo WHERE gmail=?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        echo $email;
        echo $stmt->num_rows;

        if ($stmt->num_rows > 0) {
            echo 'Email Already Exist! Please use another email';
            $emailValue = htmlspecialchars($_POST['email']); // Store the submitted email
        } else {
            $emailValue = htmlspecialchars($_POST['email']); // Store the submitted email
            sendEmail($emailValue, 0); // Call the sendEmail function with the submitted email
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email Verification</title>
    <link rel="stylesheet" href="style.css"> <!-- External styles if needed -->
</head>

<body>
    <div class="email-container">
        <h2>Send Email</h2>
        <form id='EmailForm' method="POST" action="">
            <div class="input-group">
                <label for="email">Recipient Email:</label>
                <!-- Pre-fill the input field with the submitted value -->
                <input type="email" id="email" name="email" required value="<?php echo $gmail; ?>">
            </div>
            <button type="submit" form='EmailForm' class="send-button">Send Verification</button>
        </form>

        <?php
        // Display a success message if email is sent
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
            
            echo $_POST['email'];
        }
        ?>
    </div>
</body>

</html>