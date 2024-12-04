<?php
include __DIR__ . '/../../../dbconnections/config.php';

function get_user_info($user_id) {

    global $conn;

    $query = "
        SELECT * from usersinfo WHERE idnumber = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $currentUserCollegeResult = $stmt->get_result();
    
    return $currentUserCollegeResult->fetch_assoc();
}