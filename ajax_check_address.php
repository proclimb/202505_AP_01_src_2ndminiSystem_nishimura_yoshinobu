<?php
require_once 'Db.php';
header('Content-Type: application/json; charset=utf-8');

$prefecture = $_GET['prefecture'] ?? '';
$prefecture = trim($prefecture);

if (!$prefecture) {
    echo json_encode(['valid' => false, 'message' => '都道府県が入力されていません']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM address_master WHERE prefecture = :prefecture");
    $stmt->execute([':prefecture' => $prefecture]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'valid' => $count > 0,
        'message' => $count > 0 ? '' : '有効な都道府県ではありません'
    ]);
} catch (PDOException $e) {
    echo json_encode(['valid' => false, 'message' => '都道府県確認中にエラーが発生しました']);
}
exit;
