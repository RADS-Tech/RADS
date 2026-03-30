<?php
session_start();
require_once '../db.php';

$conn = getDB();

$user = $_POST['username'];
$pass = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM operators WHERE username=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
        $_SESSION['operator_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['location'] = $row['location'];

        $conn->query("UPDATE operators SET last_login=NOW() WHERE id={$row['id']}");

        header("Location: ../dashboard.php");
        exit;
    }
}
header("Location: ../login.php?error=Invalid credentials");
