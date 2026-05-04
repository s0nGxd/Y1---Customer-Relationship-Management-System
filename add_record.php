<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to add records";
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customer_id = $_POST['customer_id'] ?? '';
    $invoice_number = $_POST['invoice_number'] ?? '';
    $product = $_POST['product'] ?? '';
    $amount = $_POST['amount_spent'] ?? 0;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $updated_by = $_SESSION['user_id'];

    // Validate required fields
    if (empty($customer_id) || empty($invoice_number) || empty($product) || empty($amount)) {
        $_SESSION['error'] = "Please fill out all required fields";
        header("Location: record.php");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert new record
        $sql = "INSERT INTO customer_record (customer_id, invoice_number, product, amount_spent, purchase_date, notes, updated_by) 
                VALUES (:customer_id, :invoice_number, :product, :amount_spent, :purchase_date, :notes, :updated_by)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':invoice_number', $invoice_number);
        $stmt->bindParam(':product', $product);
        $stmt->bindParam(':amount_spent', $amount);
        $stmt->bindParam(':purchase_date', $purchase_date);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':updated_by', $updated_by);
        
        $stmt->execute();
        
    header("Location: record.php");
    exit;
} else {
    // Not a POST request, redirect to records page
    header("Location: record.php");
    exit;
}
?>