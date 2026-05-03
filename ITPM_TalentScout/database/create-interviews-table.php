<?php
require_once 'db.php';

$conn = getConnection();

$sql = "CREATE TABLE IF NOT EXISTS interviews (
  interview_id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  employer_id INT NOT NULL,
  employee_id INT NOT NULL,
  scheduled_datetime DATETIME NOT NULL,
  confirmation_message TEXT,
  status ENUM('scheduled', 'accepted', 'rejected', 'cancelled') DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_application (application_id),
  INDEX idx_employer (employer_id),
  INDEX idx_employee (employee_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'interviews' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();