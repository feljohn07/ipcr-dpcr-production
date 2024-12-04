<?php
include __DIR__ . '/../../../dbconnections/config.php';

    function get_college($user_id) {
        global $conn; // Make sure $conn is available inside the function
        
        $query = "SELECT college FROM usersinfo WHERE idnumber = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $currentUserCollegeResult = $stmt->get_result();
        
        return $currentUserCollegeResult->fetch_assoc()['college'];
    }

?>