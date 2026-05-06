<?php
require_once 'db.php';

$conn = getConnection();

$check = $conn->query("SHOW COLUMNS FROM employee LIKE 'phone'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE employee ADD COLUMN phone VARCHAR(50) DEFAULT NULL");
    echo "Phone column added to employee table!\n";
} else {
    echo "Phone column already exists.\n";
}

$conn->close();