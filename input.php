<?php

/**
 * 登録画面
 *
 * ** 登録画面は、TOP、登録、登録確認の3画面から遷移してきます
 * ** 各画面毎の処理は以下です
 * **  1.TOP画面から遷移して来た場合
 * **    1.セッションを開始
 * **    2.値は無いので入力チェックは行わない
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目はからで表示する
 * **
 * **  2.登録確認画面から遷移して来た場合
 * **    1.セッションを開始
 * **    2.値は無いので入力チェックは行わない
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目は、登録画面で入力した値を表示する
 * **
 * **  3.登録画面から遷移してきた場合
 * **    1.セッションを開始
 * **    2.入力チェックをする
 * **      2-1.入力チェックでエラーがなかった場合は、登録確認画面へリダイレクトする
 * **      2-2.入力チェックでエラーが有った場合、次の処理を行う
 * **    3.セッションの削除
 * **    4.html を描画
 * **      入力項目は、登録画面で入力した値を表示する
 * **      エラーメッセージを表示する
 */

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'Validator.php';

// 1.セッションの開始
// session_cache_limiter('none');
// session_start();


if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none');
    session_start();
}

$_SESSION['source'] = 'input';   // ← inputから来た

// 2.変数の初期化
// *$_POSTの値があるときは初期化しない
$error_message = [];
$old = $_POST ?? [];

// POSTの場合
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['mode'] ?? '') === 'rewrite') {
        // 確認画面から戻ってきた場合
        $old = $_SESSION['input_data'] ?? [];
    } elseif (($_POST['mode'] ?? '') === 'create') {
        $validator = new Validator($pdo);

        if ($validator->validate($_POST)) {
            $_SESSION['input_data'] = $_POST;
            $_SESSION['source'] = 'input';

            // 一時アップロード先
            $tmpDir = __DIR__ . '/tmp_uploads/';
            if (!file_exists($tmpDir)) mkdir($tmpDir, 0777, true);

            // アップロードファイルの処理
            foreach (['document1', 'document2'] as $doc) {
                if (!empty($_FILES[$doc]['tmp_name']) && $_FILES[$doc]['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES[$doc]['name'], PATHINFO_EXTENSION);
                    $newPath = $tmpDir . uniqid() . '.' . $ext;

                    // ファイルを一時ディレクトリへ移動
                    if (move_uploaded_file($_FILES[$doc]['tmp_name'], $newPath)) {
                        $_SESSION['files'][$doc] = $newPath;                  // 実ファイルパス
                        $_SESSION['file_names'][$doc] = $_FILES[$doc]['name']; // 表示用ファイル名
                    }
                }
            }

            // echo "<pre>";
            // var_dump($_FILES);
            // var_dump($_SESSION['files'] ?? []);
            // var_dump($_SESSION['file_names'] ?? []);
            // echo "</pre>";
            // exit;

            header('Location: confirm.php');
            exit();
        } else {
            // バリデーションエラー時は入力値を保持
            $error_message = $validator->getErrors();
            $old = $_POST;
        }
    }
}
// GET で戻ってきた場合（初期表示やブラウザの戻るボタン）
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $old = $_SESSION['input_data'] ?? [];
}
// 5.html の描画
// ** これ以降は、htmlの部分になります
// ** php の部分は、入力した値を表示する時と入力エラー時のメッセージを表示する時に使用しています
// ** html 内に、php を記載する場合は、htmlで見やすいように1行で記載する事が多いです
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>登録画面</h2>
    </div>
    <div>
        <form action="input.php" method="post" name="form" enctype="multipart/form-data">
            <h1 class="contact-title">登録内容入力</h1>
            <p>登録内容をご入力の上、「確認画面へ」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前<span>必須</span></label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        placeholder="例）山田太郎"
                        value="<?= htmlspecialchars($old['name']) ?>">
                    <!-- ここにエラーメッセージを表示 -->
                    <div id="name-error" class="error-msg2">
                        <?php if (isset($error_message['name'])) : ?>
                            <?= htmlspecialchars($error_message['name']) ?>
                        <?php endif ?>
                    </div>
                </div>
                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input
                        type="text"
                        name="kana"
                        id="kana"
                        placeholder="例）やまだたろう"
                        value="<?= htmlspecialchars($old['kana']) ?>">
                    <div id="kana-error" class="error-msg2">
                        <?php if (isset($error_message['kana'])) : ?>
                            <?= htmlspecialchars($error_message['kana']) ?>
                        <?php endif ?>
                    </div>
                </div>
                <div>
                    <label>性別<span>必須</span></label>
                    <?php $gender = $old['gender_flag'] ?? '1'; ?>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value='1'
                            <?= ($old['gender_flag'] ?? '1') == '1'
                                ? 'checked' : '' ?>>男性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value='2'
                            <?= ($old['gender_flag'] ?? '') == '2'
                                ? 'checked' : '' ?>>女性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender"
                            value='3'
                            <?= ($old['gender_flag'] ?? '') == '3'
                                ? 'checked' : '' ?>>その他</label>
                </div>
                <div>
                    <label>生年月日<span>必須</span></label>
                    <!-- 年プルダウン -->
                    <div class="birth-selects">
                        <select name="birth_year" id="birth_year" class="form-control">
                            <option value="">年</option>
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear; $y >= 1900; $y--) :
                                $sel = (isset($old['birth_year'])
                                    && $old['birth_year'] == $y)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $y ?>"
                                    <?= $sel ?>><?= $y ?>年</option>
                            <?php endfor ?>
                        </select>

                        <!-- 月プルダウン -->
                        <select name="birth_month" id="birth_month" class="form-control">
                            <option value="">月</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) :
                                $sel = (isset($old['birth_month'])
                                    && $old['birth_month'] == $m)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $m ?>"
                                    <?= $sel ?>><?= $m ?>月</option>
                            <?php endfor ?>
                        </select>

                        <!-- 日プルダウン -->
                        <select name="birth_day" id="birth_day" class="form-control">
                            <option value="">日</option>
                            <?php
                            for ($d = 1; $d <= 31; $d++) :
                                $sel = (isset($old['birth_day'])
                                    && $old['birth_day'] == $d)
                                    ? ' selected' : ''; ?>
                                <option value="<?= $d ?>"
                                    <?= $sel ?>><?= $d ?>日</option>
                            <?php endfor ?>
                        </select>
                    </div>
                    <div id="birth-error" class="error-msg2">
                        <?php if (isset($error_message['birth_date'])) : ?>
                            <?= htmlspecialchars($error_message['birth_date']) ?>
                        <?php endif ?>
                    </div>
                </div>
                <div>
                    <label>郵便番号<span>必須</span></label>
                    <div class="postal-row">
                        <input
                            class="half-width"
                            type="text"
                            name="postal_code"
                            id="postal_code"
                            placeholder="例）100-0001"
                            value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>">
                        <button type="button"
                            class="postal-code-search"
                            id="searchAddressBtn">住所検索</button>
                    </div>
                    <div class="error-msg2" id="postal-error">
                        <?php if (isset($error_message['postal_code'])) : ?>
                            <?= htmlspecialchars($error_message['postal_code']) ?>
                        <?php endif ?>
                    </div>
                </div>
                <div>
                    <label>住所<span>必須</span></label>
                    <input
                        type="text"
                        name="prefecture"
                        id="prefecture"
                        placeholder="都道府県"
                        value="<?= htmlspecialchars($old['prefecture'] ?? '') ?>">
                    <input
                        type="text"
                        name="city_town"
                        id="city_town"
                        placeholder="市区町村・番地"
                        value="<?= htmlspecialchars($old['city_town'] ?? '') ?>">
                    <input
                        type="text"
                        name="building"
                        id="building"
                        placeholder="建物名・部屋番号  **省略可**"
                        value="<?= htmlspecialchars($old['building'] ?? '') ?>">
                    <!-- <div id="address-error" class="error-msg2"></div> -->
                    <div>
                        <p id="address-error" class="error-msg">
                            <?= htmlspecialchars($error_message['address'] ?? '') ?>
                        </p>
                    </div>

                </div>
                <div>
                    <label>電話番号<span>必須</span></label>
                    <input
                        type="text"
                        name="tel"
                        id="tel"
                        placeholder="例）000-0000-0000"
                        value="<?= htmlspecialchars($old['tel']) ?>">
                    <div>
                        <p id="tel-error" class="error-msg">
                            <?= htmlspecialchars($error_message['tel'] ?? '') ?>
                        </p>
                    </div>

                </div>
                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input
                        type="text"
                        name="email"
                        id="email"
                        placeholder="例）guest@example.com"
                        value="<?= htmlspecialchars($old['email']) ?>">
                    <div>
                        <p id="email-error" class="error-msg">
                            <?= htmlspecialchars($error_message['email'] ?? '') ?>
                        </p>
                    </div>

                </div>
                <!-- パスワード入力欄 -->
                <div>
                    <label>パスワード<span>必須</span></label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="例）********"
                        value="<?= htmlspecialchars($old['password'] ?? '') ?>">
                    <div>
                        <p id="password-error" class="error-msg">
                            <?= htmlspecialchars($error_message['password'] ?? '') ?>
                        </p>
                    </div>


                    <!-- 確認用パスワード入力欄 -->
                    <div class=".password-confirm-group">
                        <label>パスワード（確認）<span>必須</span></label>
                        <input
                            type="password"
                            name="password_confirm"
                            id="password_confirm"
                            placeholder="もう一度入力してください"
                            value="<?= htmlspecialchars($old['password_confirm'] ?? '') ?>">
                        <div>
                            <p id="password-confirm-error" class="error-msg">
                                <?= htmlspecialchars($error_message['password_confirm'] ?? '') ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="document-upload-section">
                    <label>本人確認書類（表）</label>
                    <input type="file" name="document1" id="document1" accept="image/png, image/jpeg, image/jpg">
                    <div>
                        <p id="document1-error" class="error-msg">
                            <?= htmlspecialchars($errors['document1'] ?? '') ?>
                        </p>
                    </div>

                    <!-- <span id="filename1" class="filename-display">
                        <?= htmlspecialchars($file_names['document1'] ?? '') ?>
                    </span> -->
                    <div class="preview-container">
                        <img id="preview1" src="#" alt="プレビュー画像１" style="display: none; max-width: 200px;">
                    </div>
                </div>

                <div class="file-input-wrapper">
                    <label>本人確認書類（裏）</label>
                    <input type="file" name="document2" id="document2" accept="image/png, image/jpeg, image/jpg">
                    <div>
                        <p id="document2-error" class="error-msg">
                            <?= htmlspecialchars($errors['document2'] ?? '') ?>
                        </p>
                    </div>

                    <!-- <span id="filename2" class="filename-display">
                        <?= htmlspecialchars($file_names['document2'] ?? '') ?>
                    </span> -->
                    <div class="preview-container">
                        <img id="preview2" src="#" alt="プレビュー画像２" style="display: none; max-width: 200px; margin-top: 8px;">
                    </div>
                </div>



                <!-- ここに mode=create を追加 -->
                <input type="hidden" name="mode" value="create">

                <button type="submit">確認画面へ</button>
                <a href="index.php">
                    <button type="button">TOPに戻る</button>
                </a>
        </form>
    </div>
    <!-- ここでJSを読み込む -->
    <script src="validation.js"></script>
</body>

</html>