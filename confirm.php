<?php

/**
 * 確認画面
 *
 * ** 確認画面は、登録画面から遷移してきます
 *
 * ** 確認画面で行う処理は以下です
 * ** 1.セッションを開始
 * **   1.セッションに登録画面の情報があるか確認する
 * **     ※無ければ、登録画面に遷移する
 * **   2.セッションから登録画面の情報を$_POSTへコピーする
 * ** 2.セッションを削除
 * ** 3.html を描画
 * **   登録画面で入力した情報を画面に表示する
 */

// 1.セッションの開始
// session_cache_limiter('none');
// session_start();

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none');
    session_start();
}

/* ============================
   デバッグ用：画像・入力データ確認
   ============================ */
// デバッグ用：セッションとIDの確認
// echo "<pre>=== SESSION 入力データ ===\n";
// var_dump($_SESSION['input_data'] ?? []);
$id = $_SESSION['input_data']['id'];
// echo "\n=== SESSION ファイルパス ===\n";
// var_dump($_SESSION['files'] ?? []);
// echo "\n=== SESSION ファイル名 ===\n";
// echo '<pre>';
// var_dump($_SESSION['files']['document1']);   // 新しいファイル
// var_dump($_SESSION['file_names']['document1']); // ファイル名
// var_dump($_SESSION['db_files']['document1'] ?? null); // DBからの画像パス（あれば）
// echo '</pre>';


// exit; // デバッグ用にここで止める
// ============================
// ここまでデバッグ用
// ============================

$data = $_SESSION['input_data'] ?? [];
$source = $_SESSION['source'] ?? 'input'; // デフォルトは input

$inputData = $_SESSION['input_data'] ?? null;
$source = $_SESSION['source'] ?? 'input'; // デフォルトは input

if (!$inputData) {
    // 送信元に応じてリダイレクト
    $redirect = $source === 'edit' ? 'edit.php' : 'input.php';
    header('Location: ' . $redirect);
    exit();
}

// $_POST にコピーして後続の html 部分で使用
$_POST = $inputData;

// 一時保存先ディレクトリ
$uploadDir = __DIR__ . '/tmp_upload/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// document1, document2 を一時保存
foreach (['document1', 'document2'] as $doc) {
    if (!empty($_FILES[$doc]['tmp_name']) && $_FILES[$doc]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$doc]['tmp_name'];
        $filename = uniqid() . '_' . basename($_FILES[$doc]['name']);
        $savePath = $uploadDir . $filename;

        // 一時ディレクトリにコピー
        if (move_uploaded_file($tmpName, $savePath)) {
            $_SESSION['files'][$doc] = $savePath;
            $_SESSION['file_names'][$doc] = $_FILES[$doc]['name'];
        }
    }
}
// var_dump($_POST);
// 送信先を決定
$mode = $_POST['mode'] ?? 'create';
$actionUrl = $mode === 'edit' ? 'update.php' : 'submit.php';

// confirm.php の冒頭、HTML開始直前
$imgSrc = null;
$fileName = '';


// 3.htmlの描画
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>確認画面</h2>
    </div>
    <div>
        <!-- <form action="submit.php" method="post"> -->
        <form action="<?= htmlspecialchars($actionUrl) ?>" method="post" enctype="multipart/form-data">

            <!-- mode を引き継ぐ -->
            <input type="hidden" name="mode" value="<?= htmlspecialchars($_SESSION['input_data']['mode'] ?? 'create') ?>">

            <input type="hidden" name="name" value="<?php echo $_POST['name'] ?>">
            <input type="hidden" name="kana" value="<?php echo $_POST['kana'] ?>">
            <?php if ($source === 'input'): ?>
                <input type="hidden" name="gender" value="<?php echo $_POST['gender'] ?>">
                <input type="hidden" name="birth_year" value="<?php echo $_POST['birth_year'] ?>">
                <input type="hidden" name="birth_month" value="<?php echo $_POST['birth_month'] ?>">
                <input type="hidden" name="birth_day" value="<?php echo $_POST['birth_day'] ?>">

            <?php elseif ($source === 'edit'): ?>
                <input type="hidden" name="gender_flag" value="<?php echo $_POST['gender_flag'] ?>">
                <input type="hidden" name="birth_date" value="<?php echo $_POST['birth_date'] ?>">
            <?php endif; ?>

            <!-- <input type="hidden" name="gender" value="<?php echo $_POST['gender'] ?>"> -->
            <!-- <input type="hidden" name="birth_year" value="<?php echo $_POST['birth_year'] ?>"> -->
            <!-- <input type="hidden" name="birth_month" value="<?php echo $_POST['birth_month'] ?>"> -->
            <!-- <input type="hidden" name="birth_day" value="<?php echo $_POST['birth_day'] ?>"> -->
            <input type="hidden" name="postal_code" value="<?php echo $_POST['postal_code'] ?>">
            <input type="hidden" name="prefecture" value="<?php echo $_POST['prefecture'] ?>">
            <input type="hidden" name="city_town" value="<?php echo $_POST['city_town'] ?>">
            <input type="hidden" name="building" value="<?php echo $_POST['building'] ?>">
            <input type="hidden" name="tel" value="<?php echo $_POST['tel'] ?>">
            <input type="hidden" name="email" value="<?php echo $_POST['email'] ?>">
            <input type="hidden" name="password" value="<?php echo $_POST['password'] ?>">
            <input type="hidden" name="password_confirm" value="<?php echo $_POST['password_confirm'] ?>">
            <!-- <h1 class="contact-title">登録内容確認</h1> -->
            <!-- <p>登録内容をご入力の上、「登録する」ボタンをクリックしてください。</p> -->
            <?php if ($source === 'input'): ?>
                <h1 class="contact-title">登録内容確認</h1>
                <p>登録内容をご入力の上、「登録する」ボタンをクリックしてください。</p>
            <?php elseif ($source === 'edit'): ?>
                <h1 class="contact-title">更新内容確認</h1>
                <p>更新内容をご入力の上、「更新する」ボタンをクリックしてください。</p>
            <?php endif; ?>
            <div>
                <div>
                    <label>お名前</label>
                    <p><?= htmlspecialchars($_POST['name']) ?></p>
                </div>
                <div>
                    <label>ふりがな</label>
                    <p><?= htmlspecialchars($_POST['kana']) ?></p>
                </div>
                <div>
                    <label>性別</label>
                    <?php if ($source === 'input'): ?>
                        <p><?php if ($_POST['gender'] == '1') {
                                echo "男性";
                            } elseif ($_POST['gender'] == '2') {
                                echo "女性";
                            } elseif ($_POST['gender'] == '3') {
                                echo "その他";
                            } ?></p>
                    <?php elseif ($source === 'edit'): ?>
                        <p><?php if ($_POST['gender_flag'] == '1') {
                                echo "男性";
                            } elseif ($_POST['gender_flag'] == '2') {
                                echo "女性";
                            } elseif ($_POST['gender_flag'] == '3') {
                                echo "その他";
                            } ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label>生年月日</label>
                    <?php if ($source === 'input'): ?>
                        <p><?= htmlspecialchars($_POST['birth_year']
                                . "年 "
                                . $_POST['birth_month']
                                . "月 "
                                . $_POST['birth_day']
                                . "日") ?></p>
                    <?php elseif ($source === 'edit'): ?>
                        <p><?= htmlspecialchars($_POST['birth_date']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label>郵便番号</label>
                    <p><?= htmlspecialchars("〒"
                            . $_POST['postal_code']) ?></p>
                </div>
                <div>
                    <label>住所</label>
                    <p><?= htmlspecialchars($_POST['prefecture']
                            . $_POST['city_town']
                            . $_POST['building']) ?></p>
                </div>
                <div>
                    <label>電話番号</label>
                    <p><?= htmlspecialchars($_POST['tel']) ?></p>
                </div>
                <div>
                    <label>メールアドレス</label>
                    <p><?= htmlspecialchars($_POST['email']) ?></p>
                </div>
                <?php if ($source === 'input'): ?>
                    <div>
                        <label>パスワード</label>
                        <p><?= str_repeat('●', strlen($_POST['password'] ?? '')) ?></p>
                    </div>
                    <div>
                        <label>パスワード（確認）</label>
                        <p><?= str_repeat('●', strlen($_POST['password_confirm'] ?? '')) ?></p>
                    </div> <?php elseif ($source === 'edit'): ?>
                <?php endif; ?>
                <!-- <?php $file_names['document1'] = $_SESSION['file_names']['document1']; ?> -->
                <!-- <?php var_dump($id); ?> -->

                <!-- 本人確認書類（表）表示 -->
                <div class="preview-container" style="text-align:center;">
                    <label>本人確認書類（表）</label>
                    <p id="document1-error" class="error-msg">
                        <?= htmlspecialchars($errors['document1'] ?? '') ?>
                    </p>

                    <?php
                    $newFile = !empty($_SESSION['files']['document1']);
                    $dbFile  = !empty($_SESSION['db_files']['document1']);
                    ?>

                    <?php if (!$newFile && !$dbFile): ?>
                        <p>画像は登録されていません</p>
                    <?php endif; ?>

                    <?php $deleteFront = $_SESSION['delete_flags']['front'] ?? 0; ?>

                    <?php if ($newFile): ?>
                        <!-- 新しいファイル -->
                        <br> <!-- ← 改行を明示的に追加 -->
                        <div style="margin-bottom:10px; text-align:center;">
                            <p style="margin:0; font-weight:bold;">新しいファイル</p>
                            <img src="/202505_AP_01_src_2ndminiSystem_nishimura_yoshinobu/tmp_uploads/<?= basename($_SESSION['files']['document1']) ?>"
                                alt="アップロード画像" style="max-width:200px; display:block; margin:0 auto;">
                            <span style="display:block; text-align:center; font-size:14px; margin-top:4px;">
                                <?= htmlspecialchars($_SESSION['file_names']['document1']) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($dbFile): ?>
                        <br> <!-- 改行を明示的に追加 -->
                        <!-- DBからの画像 -->
                        <div>
                            <p style="margin:0; font-weight:bold;">DBの画像</p>
                            <img src="<?= $_SESSION['db_files']['document1'] ?>"
                                alt="DB画像" style="max-width:200px; display:block; margin:0 auto;">
                            <span><?= htmlspecialchars($_SESSION['db_file_names']['document1']) ?></span>
                            <?php if ($deleteFront): ?>
                                <p style="color:red; font-weight:bold; margin-top:4px;">削除予定</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // 裏画像の状態チェック
                $newFileBack = !empty($_SESSION['files']['document2']);      // 新しい裏画像があるか
                $dbFileBack  = !empty($_SESSION['db_files']['document2']);  // DBに裏画像があるか
                $deleteBack = $_SESSION['delete_flags']['back'] ?? 0;
                ?>

                <div class="preview-container" style="text-align:center;">
                    <label>本人確認書類（裏）</label>
                    <p id="document2-error" class="error-msg">
                        <?= htmlspecialchars($errors['document2'] ?? '') ?>
                    </p>

                    <?php if (!$newFileBack && !$dbFileBack): ?>
                        <p>裏画像は登録されていません</p>
                    <?php endif; ?>
                    <?php $deleteBack  = $_SESSION['delete_flags']['back'] ?? 0; ?>
                    <?php if ($newFileBack): ?>
                        <!-- 新しい裏画像 -->
                        <br> <!-- 改行を明示的に追加 -->
                        <div style="margin-bottom:10px; text-align:center;">
                            <p style="margin:0; font-weight:bold;">新しい裏画像</p>
                            <img src="/202505_AP_01_src_2ndminiSystem_nishimura_yoshinobu/tmp_uploads/<?= basename($_SESSION['files']['document2']) ?>"
                                alt="アップロード裏画像" style="max-width:200px; display:block; margin:0 auto;">
                            <span style="display:block; text-align:center; font-size:14px; margin-top:4px;">
                                <?= htmlspecialchars($_SESSION['file_names']['document2']) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($dbFileBack): ?>
                        <!-- DBからの裏画像 -->
                        <br> <!-- 改行を明示的に追加 -->
                        <div style="margin-bottom:10px; text-align:center;">
                            <p style="margin:0; font-weight:bold;">DB裏画像</p>
                            <img src="<?= $_SESSION['db_files']['document2'] ?>"
                                alt="DB裏画像" style="max-width:200px; display:block; margin:0 auto;">
                            <span style="display:block; text-align:center; font-size:14px; margin-top:4px;">
                                <?= htmlspecialchars($_SESSION['db_file_names']['document2']) ?>
                            </span>
                            <?php if ($deleteBack): ?>
                                <p style="color:red; font-weight:bold; margin-top:4px;">削除予定</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            // 編集か新規か判定
            $rewriteUrl = ($mode === 'edit')
                ? 'edit.php?id=' . urlencode($_SESSION['input_data']['id'] ?? '')
                : 'input.php';
            ?>
            <a href="<?= $rewriteUrl ?>">
                <button type="button">内容を修正する</button>
            </a>
            <!-- <button type="submit" name="submit">登録する</button> -->
            <?php if ($source === 'input'): ?>
                <button type="submit" name="mode" value="create">登録する</button>
            <?php elseif ($source === 'edit'): ?>
                <button type="submit" name="mode" value="update">更新する</button>
            <?php endif; ?>
        </form>
    </div>
</body>

</html>