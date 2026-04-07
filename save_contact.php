<?php
// save_contact.php

// Database configuration
$host = 'sql304.infinityfree.com';
$dbname = 'if0_41634806_portfolio_contacts';
$username = 'if0_41634806';      // Change to your MySQL username
$password = 'IRyKSnQLNk';          // Change to your MySQL password

// Set response header for JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Support both form-encoded and raw JSON payloads.
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
if (!is_array($jsonInput)) {
    $jsonInput = [];
}

// Find the first non-empty value among possible key aliases.
function firstNonEmpty(array ...$sources): string
{
    foreach ($sources as $source) {
        foreach ($source as $value) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }
    }
    return '';
}

// Get form data using common key aliases from frontend forms.
$name = firstNonEmpty(
    [$_POST['name'] ?? null, $_POST['full_name'] ?? null, $_POST['fullname'] ?? null, $_POST['user_name'] ?? null],
    [$jsonInput['name'] ?? null, $jsonInput['full_name'] ?? null, $jsonInput['fullname'] ?? null, $jsonInput['user_name'] ?? null]
);
$email = firstNonEmpty(
    [$_POST['email'] ?? null, $_POST['user_email'] ?? null, $_POST['mail'] ?? null],
    [$jsonInput['email'] ?? null, $jsonInput['user_email'] ?? null, $jsonInput['mail'] ?? null]
);
$message = firstNonEmpty(
    [$_POST['message'] ?? null, $_POST['msg'] ?? null, $_POST['comment'] ?? null],
    [$jsonInput['message'] ?? null, $jsonInput['msg'] ?? null, $jsonInput['comment'] ?? null]
);

// Basic validation
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

try {
    // Create database connection
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);
    
    // Execute query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Thank you! Your message has been sent successfully.']);
    } else {
        throw new Exception('Failed to save message');
    }
    
    // Close connections
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
