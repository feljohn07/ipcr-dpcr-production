<?php
// send_email_async.php
include __DIR__ . '/send_email.php';

include __DIR__ . '/../../../dbconnections/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if message is set in the request
    if (isset($data['message']) && isset($data['user_id'])) {

        global $conn;

        $query = "
            SELECT * from usersinfo WHERE idnumber = ? LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $data['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();

        if ($user) {
            $message = $data['message'];
            echo $user['gmail'];
            sendEmail($user['gmail'], $data['message']);
        } else {
            echo $data['user_id'];
        }


    }

}
?>