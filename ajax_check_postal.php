<?php
// ajax_check_postal.php
require_once 'Db.php'; // $pdo が定義されている前提

header('Content-Type: application/json; charset=utf-8');

// 入力値取得
$postal = $_GET['postal'] ?? '';
$clean_zip = str_replace("-", "", $postal);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM address_master WHERE postal_code = :postal_code");
    $stmt->execute([':postal_code' => $clean_zip]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'valid' => $count > 0,
        'message' => $count > 0 ? '' : '郵便番号が見つかりません'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'valid' => false,
        'message' => '郵便番号確認中にエラーが発生しました'
    ]);
}
exit;
