<?php
// staff_delete.php
session_start();
require_once 'db.php';

// Check if user is logged in (We'll enforce this globally later, but good to have here)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    // Security check: Prevent the logged-in user from deleting themselves
    if ($_SESSION['user_id'] == $user_id) {
        // In a real app, you might pass an error message via session here
        header("Location: staff_manage.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE user_id = :id");
        $stmt->execute(['id' => $user_id]);
    } catch (PDOException $e) {
        // If the user is referenced in the Stay table, MySQL will block the deletion.
        // We log the error or handle it. For now, we'll just redirect safely.
        // To make it perfect later, we could pass an ?error=in_use parameter to the URL.
    }
}

// Redirect back to the staff management page
header("Location: staff_manage.php");
exit;
?>