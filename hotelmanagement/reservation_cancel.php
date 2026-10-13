<?php
// reservation_cancel.php
session_start();
require_once 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservation_id > 0) {
    try {
        // We only want to cancel reservations that aren't already checked-in or checked-out
        $stmt = $pdo->prepare("UPDATE Reservations SET status = 'Cancelled' WHERE reservation_id = :id AND status IN ('Pending', 'Confirmed')");
        $stmt->execute(['id' => $reservation_id]);
    } catch (PDOException $e) {
        // Silently handle database errors on background actions, or log them
    }
}

// Redirect back to the reservations page
header("Location: reservations.php");
exit;
?>