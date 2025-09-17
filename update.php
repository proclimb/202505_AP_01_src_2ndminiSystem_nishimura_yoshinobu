<?php

/**
 * 更新完了画面
 *
 * ** 更新完了画面は、更新確認画面から遷移してきます
 *
 * ** 更新完了画面で行う処理は以下です
 * ** 1.DBへ更新する為、$_POSTから入力情報を取得する
 * ** 2.DBへユーザ情報を更新する
 * **   1.DBへ接続
 * **     ※接続出来なかった場合は、エラーメッセージを表示する
 * **   2.ユーザ情報を更新する
 * ** 3.html を描画
 * **   更新完了のメッセージを表示します
 */

// session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none');
    session_start();
}

$data = $_SESSION['input_data'] ?? null;
$files = $_SESSION['files'] ?? null;

// echo "editから遷移";

if ($data === null) {
    header('Location: edit.php');
    exit;
}

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Address.php';
require_once 'FileBlobHelper.php';
require_once 'Validator.php';

// ------------------------
// 入力値のバリデーション
// ------------------------
$validator = new Validator($pdo);
if (!$validator->validate($_POST)) {
    $_SESSION['errors'] = $validator->getErrors();
    $_SESSION['old'] = $_POST;
    header('Location: edit.php?id=' . $_POST['id']);
    exit;
}

// 2. 入力データ取得
// 2-1. ユーザーデータ取得
$id = $data['id'];
$userData = [
    'name'         => $data['name'],
    'kana'         => $data['kana'],
    'gender_flag'  => $data['gender_flag'],
    'tel'          => $data['tel'],
    'email'        => $data['email'],
];


// 2-2. 住所データも取得
$addressData = [
    'user_id'      => $id,
    'postal_code'  => $data['postal_code'],
    'prefecture'   => $data['prefecture'],
    'city_town'    => $data['city_town'],
    'building'     => $data['building'],
];

// 3. トランザクション開始
try {
    $pdo->beginTransaction();

    // 3. ユーザー＆住所クラスを生成
    $user = new User($pdo);
    // 4. 各テーブルのupdateメソッドを呼び出し
    $user->update($id, $userData);


    $address = new UserAddress($pdo);
    $address->updateByUserId($addressData); // user_id付きのデータを渡す

    // 6. ファイルアップロードを BLOB 化して取得（保存期限なし = null）
    //    edit.php の <input type="file" name="document1"> / document2
    // echo "update.php";
    // var_dump($_SESSION['files']); // ← ここでファイルパスが正しく入っているか確認


    $files = $_SESSION['files'] ?? null;

    $blobInputs = [];

    // document1
    if (!empty($files['document1'])) {
        $blobInputs['document1'] = [
            'tmp_name' => $files['document1'],
            'name' => $_SESSION['file_names']['document1'] ?? basename($files['document1']),
            'type' => mime_content_type($files['document1']),
            'error' => 0,
            'size' => filesize($files['document1'])
        ];
    }

    // document2
    if (!empty($files['document2'])) {
        $blobInputs['document2'] = [
            'tmp_name' => $files['document2'],
            'name' => $_SESSION['file_names']['document2'] ?? basename($files['document2']),
            'type' => mime_content_type($files['document2']),
            'error' => 0,
            'size' => filesize($files['document2'])
        ];
    }

    // BLOBを取得
    $blobs = FileBlobHelper::getMultipleBlobs(
        $blobInputs['document1'] ?? null,
        $blobInputs['document2'] ?? null
    );

    // 追加：削除フラグを取得
    $deleteFront = $_SESSION['delete_flags']['front'] ?? 0;
    $deleteBack  = $_SESSION['delete_flags']['back'] ?? 0;


    $expiresAt = null;
    $frontName = $_SESSION['file_names']['document1'] ?? '';
    $backName  = $_SESSION['file_names']['document2'] ?? '';

    // レコードがあるか確認
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_documents WHERE user_id = ?");
    $stmt->execute([$id]);
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        // 🔹 UPDATE
        $sql = "UPDATE user_documents SET updated_at = NOW(), expires_at = :expires_at";

        // front画像
        if (!empty($blobs['front'])) {
            $sql .= ", front_image = :front_image, front_image_name = :front_name";
        } elseif ($deleteFront) {
            $sql .= ", front_image = NULL, front_image_name = NULL";
        }

        // back画像
        if (!empty($blobs['back'])) {
            $sql .= ", back_image = :back_image, back_image_name = :back_name";
        } elseif ($deleteBack) {
            $sql .= ", back_image = NULL, back_image_name = NULL";
        }

        $sql .= " WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':expires_at', $expiresAt);
        $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);

        if (!empty($blobs['front'])) {
            $stmt->bindValue(':front_image', $blobs['front'], PDO::PARAM_LOB);
            $stmt->bindValue(':front_name', $frontName);
        }

        if (!empty($blobs['back'])) {
            $stmt->bindValue(':back_image', $blobs['back'], PDO::PARAM_LOB);
            $stmt->bindValue(':back_name', $backName);
        }

        $stmt->execute();
    } else {
        // 🔹 INSERT（画像が1つでもアップロードされたら作成）
        if (!empty($blobs['front']) || !empty($blobs['back'])) {
            $sql = "INSERT INTO user_documents
            (user_id, front_image, back_image, front_image_name, back_image_name, expires_at, created_at, updated_at)
            VALUES (:user_id, :front_image, :back_image, :front_name, :back_name, :expires_at, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':front_image', !empty($blobs['front']) ? $blobs['front'] : null, PDO::PARAM_LOB);
            $stmt->bindValue(':back_image', !empty($blobs['back']) ? $blobs['back'] : null, PDO::PARAM_LOB);
            $stmt->bindValue(':front_name', $frontName);
            $stmt->bindValue(':back_name', $backName);
            $stmt->bindValue(':expires_at', $expiresAt);

            $stmt->execute();
        }
    }

    // 8. トランザクションコミット
    $pdo->commit();

    foreach ($_SESSION['files'] as $path) {
        if (file_exists($path)) unlink($path);
    }
    unset($_SESSION['files']);
} catch (Exception $e) {
    // いずれかで例外が発生したらロールバックしてエラー表示
    $pdo->rollBack();
    // 本番環境ならログ出力してエラーページへリダイレクトなどが望ましい
    echo "エラーが発生しました。詳細: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
    exit;
}

// 4.html の描画
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
        <h2>更新完了画面</h2>
    </div>
    <div>
        <div>
            <h1>更新完了</h1>
            <p>
                更新しました。<br>
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>


<?php
unset($_SESSION['files'], $_SESSION['file_names'], $_SESSION['input_data'], $_SESSION['source']);
session_unset();
?>

</html>