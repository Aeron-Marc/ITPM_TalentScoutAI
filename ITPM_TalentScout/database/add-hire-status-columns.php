<?php
require_once 'db.php';

$conn = getConnection();

$check = $conn->query("SHOW COLUMNS FROM application LIKE 'hire_status'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE application ADD COLUMN hire_status ENUM('none', 'offered', 'accepted', 'rejected') DEFAULT 'none'");
}
$check = $conn->query("SHOW COLUMNS FROM application LIKE 'hire_offer_message'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE application ADD COLUMN hire_offer_message TEXT");
}
$check = $conn->query("SHOW COLUMNS FROM application LIKE 'hire_offer_date'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE application ADD COLUMN hire_offer_date TIMESTAMP NULL");
}
$check = $conn->query("SHOW COLUMNS FROM application LIKE 'hire_response_date'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE application ADD COLUMN hire_response_date TIMESTAMP NULL");
}
echo "Application table updated successfully!";

$conn->close();