<?php
ob_start();
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    // Lấy checkbox, nếu có 'true' (string) thì chuyển thành true, ngược lại false
    $checked = isset($_POST['checked']) && $_POST['checked'] === 'true' ? true : false;
    
    if (!empty($item_id) && isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] = $quantity;
        $_SESSION['cart'][$item_id]['checked'] = $checked;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }
    exit;
}
?>
