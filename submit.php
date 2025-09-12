<?php
// session_start() 確実に呼ぶ
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none');
    session_start();
}

// セッションからデータを取得
$data      = $_SESSION['input_data'] ?? null;
$files     = $_SESSION['files'] ?? null;
$fileNames = $_SESSION['file_names'] ?? null;

if ($data === null) {
    // データがなければ入力画面に戻す
    header('Location: input.php');
    exit;
}

// DB接続とクラス読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Address.php';
require_once 'FileBlobHelper.php';

// ユーザーデータの準備
$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

$userData = [
    'name'        => $data['name'],
    'kana'        => $data['kana'],
    'gender_flag' => $data['gender'] ?? null,
    'birth_date'  => sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']),
    'tel'         => $data['tel'],
    'email'       => $data['email'],
    'password'    => $hashedPassword
];

$addressData = [
    'postal_code' => $data['postal_code'],
    'prefecture'  => $data['prefecture'],
    'city_town'   => $data['city_town'],
    'building'    => $data['building']
];

try {
    $pdo->beginTransaction();

    // ユーザー登録
    $user = new User($pdo);
    $userId = $user->create($userData);

    // 住所登録
    $address = new UserAddress($pdo);
    $addressData['user_id'] = $userId;
    $address->create($addressData);

    // ファイル BLOB 化
    $blobInputs = [];

    if (!empty($files['document1'])) {
        $blobInputs['document1'] = [
            'tmp_name' => $files['document1'],
            'name'     => $fileNames['document1'] ?? basename($files['document1']),
            'type'     => mime_content_type($files['document1']),
            'error'    => 0,
            'size'     => filesize($files['document1'])
        ];
    }

    if (!empty($files['document2'])) {
        $blobInputs['document2'] = [
            'tmp_name' => $files['document2'],
            'name'     => $fileNames['document2'] ?? basename($files['document2']),
            'type'     => mime_content_type($files['document2']),
            'error'    => 0,
            'size'     => filesize($files['document2'])
        ];
    }

    $blobs = FileBlobHelper::getMultipleBlobs(
        $blobInputs['document1'] ?? null,
        $blobInputs['document2'] ?? null
    );

    if ($blobs !== null) {
        $expiresAt = null;
        $user->saveDocument(
            $userId,
            $blobs['front'],
            $blobs['back'],
            $expiresAt
        );
    }

    $pdo->commit();

    // 一時ファイル削除
    foreach ($files as $path) {
        if (file_exists($path)) unlink($path);
    }

    // セッション破棄
    unset($_SESSION['files'], $_SESSION['file_names'], $_SESSION['input_data'], $_SESSION['source']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo "エラーが発生しました: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>完了画面</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>完了画面</h2>
    </div>
    <div>
        <div>
            <h1>登録完了</h1>
            <p>
                登録ありがとうございました。<br>
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
            <a href="login.php">
                <button type="button">ログイン画面に戻る</button>
            </a>
        </div>
    </div>
</body>

</html>