<?php
// functions.php
session_start(); // Start the session globally for login handling

// Clean input to prevent XSS attacks
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format currency (You can change $ to Rp or your preferred currency)
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

// Format date to a readable string (e.g., Oct 15, 2023)
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime 
function format_datetime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}
?>