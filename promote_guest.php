<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect IDs from the Upgrade Modal
    $guest_id = mysqli_real_escape_string($conn, $_POST['guest_id']);
    $type_id = mysqli_real_escape_string($conn, $_POST['membership_type_id']);
    $registration_date = date('Y-m-d');

    // 1. Fetch the Guest's existing data to "move" it
    $query = $conn->prepare("SELECT name, contact_info FROM guests WHERE guest_id = ?");
    $query->bind_param("i", $guest_id);
    $query->execute();
    $guest_data = $query->get_result()->fetch_assoc();

    if ($guest_data) {
        // Start a Transaction to ensure both steps happen or none at all
        $conn->begin_transaction();

        try {
            // 2. Insert into the MEMBERS table
            // Mapping: 'contact_info' becomes 'phone'. 'address' is set to 'Pending Update'
            $insert = $conn->prepare("INSERT INTO members (name, phone, address, membership_date, membership_type_id) VALUES (?, ?, 'Pending Update', ?, ?)");
            $insert->bind_param("sssi", 
                $guest_data['name'], 
                $guest_data['contact_info'], 
                $registration_date, 
                $type_id
            );
            $insert->execute();

            // 3. Delete from the GUESTS table (Cleanup)
            $delete = $conn->prepare("DELETE FROM guests WHERE guest_id = ?");
            $delete->bind_param("i", $guest_id);
            $delete->execute();

            // Success: Finalize the move
            $conn->commit();
            header("Location: home.php#panel-members"); 
            exit;

        } catch (Exception $e) {
            // Failure: Undo changes
            $conn->rollback();
            echo "Enrollment failed: " . $e->getMessage();
        }
    } else {
        echo "Visitor not found.";
    }
}
?>