<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register.html");
    exit;
}

$conn = getDB();

$fname = $_POST['first_name'] ?? '';
$lname = $_POST['last_name'] ?? '';
$user  = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$loc   = $_POST['location'] ?? '';
$rawPass = $_POST['password'] ?? '';

if ($fname === '' || $lname === '' || $user === '' || $email === '' || $rawPass === '') {
    header("Location: ../register.html?error=All fields are required");
    exit;
}

# CHECK IF USER ALREADY EXISTS
$check = $conn->prepare("SELECT id FROM operators WHERE username = ? OR email = ?");
$check->bind_param("ss", $user, $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    header("Location: ../register.html?error=Username or Email already exists");
    exit;
}

# INSERT NEW USER
$pass = password_hash($rawPass, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO operators (first_name, last_name, username, email, phone, location, password)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param("sssssss", $fname, $lname, $user, $email, $phone, $loc, $pass);

if ($stmt->execute()) {
    header("Location: ../login.php");
    exit;
} else {
    header("Location: ../register.html?error=Something went wrong");
    exit;
}
?>