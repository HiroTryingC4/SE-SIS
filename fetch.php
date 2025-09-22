<?php
include 'db_connect.php';

$sql = "SELECT * FROM locations";
$result = $conn->query($sql);

$locations = array();

while($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

echo json_encode($locations);
?>
