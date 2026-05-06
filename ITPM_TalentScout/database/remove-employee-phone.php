<?php
require_once 'db.php';
$conn = getConnection();
$conn->query("ALTER TABLE employee DROP COLUMN phone");
echo "Phone column removed from employee table";
$conn->close();