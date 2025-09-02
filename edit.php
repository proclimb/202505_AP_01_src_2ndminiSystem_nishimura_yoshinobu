<?php

/**
 * 更新・削除画面
 *
 * ** 更新・削除画面は、ダッシュボード、更新・削除確認の2画面から遷移してきます
 * * **
 * ** 【説明】
 * **   更新・削除では、入力チェックと画面遷移をjavascriptで行います
 * **   そのため、登録の時とは違い、セッションを使用しないパターンのプログラムになります
 * **
 * ** 各画面毎の処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.DBからユーザ情報を取得する為、$_GETからID情報を取得する
 * ** 3.ユーザ情報を取得する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行じユーザー情報を取得する
 * ** 4.html を描画
 */

//1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Validator.php';

// session_cache_limiter('none');
// session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none');
    session_start();
}

$_SESSION['source'] = 'edit';

// 2.変数の初期化
// *$_POSTの値があるときは初期化しない
$error_message = [];
$old = $_POST ?? $originalData;
$inputs = $old;

// 2.ダッシュボードから送信した変数を設定
// $id = $_GET['id'];
$id = $_GET['id'];

// 3-1.Userクラスをインスタンス化
$user = new User($pdo);

// 3-2.UserクラスのfindById()メソッドで1件検索
// $_POST = $user->findById($id);
// echo ($id);
$originalData = $user->findById($id);
// バリデーション処理
if (!empty($_POST)) {
    $data = array_merge($_POST, $_FILES);
    $data['source'] = $_SESSION['source'] ?? '';

    $validator = new Validator($pdo);

    if ($validator->validate($data)) {
        // echo "バリデーション成功";

        $_SESSION['input_data'] = $_POST;
        // $_SESSION['files'] = $_FILES;

        if (!empty($_FILES['document1']['tmp_name'])) {
            $_SESSION['files']['document1'] = $_FILES['document1'];
        }
        if (!empty($_FILES['document2']['tmp_name'])) {
            $_SESSION['files']['document2'] = $_FILES['document2'];
        }
        // 表示用ファイル名も保存
        $_SESSION['file_names'] = [
            'document1' => $_FILES['document1']['name'] ?? ($_SESSION['file_names']['document1'] ?? ''),
            'document2' => $_FILES['document2']['name'] ?? ($_SESSION['file_names']['document2'] ?? ''),
        ];


        echo "edit.php";
        var_dump($_FILES);
        var_dump($_POST);

        // ▼ここに追加
        $tmpDir = __DIR__ . '/tmp_uploads/';
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $files = [];
        foreach (['document1', 'document2'] as $key) {
            if (!empty($_FILES[$key]['tmp_name']) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
                $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                $newPath = $tmpDir . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES[$key]['tmp_name'], $newPath);
                $files[$key] = $newPath;
            }
        }
        $_SESSION['files'] = $files;
        // ▲ここまで追加


        // ← ここに追加
        // include 'update.php';
        header('Location: confirm.php');
        // include 'confirm.php';
        // header('Location: confirm.php?id=' . urlencode($id));
        exit();
    } else {
        // echo "バリデーション失敗";

        $_SESSION['errors'] = $validator->getErrors();
        $_SESSION['inputs'] = $data;

        $_SESSION['file_names'] = [
            'document1' => $_FILES['document1']['name'] ?? '',
            'document2' => $_FILES['document2']['name'] ?? '',
        ];


        header('Location: edit.php?id=' . urlencode($id));
        exit();
    }
}
// バリデーション失敗時の入力データとエラー取得
$errors = $_SESSION['errors'] ?? [];
$inputs = $_SESSION['inputs'] ?? $originalData;
$file_names = $_SESSION['file_names'] ?? [];
// ファイル名だけ保存


// 一度使ったらクリア
// unset($_SESSION['errors'], $_SESSION['inputs']);
// 4.html の描画

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
    <!-- <script src="contact.js"></script> -->
</head>
<!-- <?php var_dump($inputs); ?> -->

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>更新・削除画面</h2>
    </div>
    <div>
        <form action="edit.php?id=<?= htmlspecialchars($id) ?>" method="post" name="edit" enctype="multipart/form-data">
            <!-- <?php if (!empty($errors)) : ?>
                <div class="error-box" style="color: red; margin-bottom: 1em;">
                    <ul>
                        <?php foreach ($errors as $field => $message) : ?>
                            <li><?= htmlspecialchars($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?> -->

            <!-- mode フラグを追加 -->
            <input type="hidden" name="mode" value="edit">

            <!-- <input type="hidden" name="id" value="<?= htmlspecialchars($inputs['id'] ?? $originalData['id'] ?? '') ?>"> -->
            <input type="hidden" name="id" value="<?= htmlspecialchars($inputs['id'] ?? $originalData['id'] ?? '') ?>">

            <h1 class="contact-title">更新内容入力</h1>
            <p>更新内容をご入力の上、「更新」ボタンをクリックしてください。</p>
            <p>削除する場合は「削除」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前<span>必須</span></label>
                    <input type="text" name="name" placeholder="例）山田太郎" value="<?= htmlspecialchars($inputs['name']) ?>">
                    <?php if (isset($errors['name'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input type="text" name="kana" placeholder="例）やまだたろう" value="<?= htmlspecialchars($inputs['kana']) ?>">
                    <?php if (isset($errors['kana'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['kana']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>性別<span>必須</span></label>
                    <label class="gender">
                        <input type="radio" name="gender_flag" value='1' <?= ($inputs['gender_flag'] ?? '1') == '1' ? 'checked' : '' ?>>男性
                    </label>
                    <label class="gender">
                        <input type="radio" name="gender_flag" value='2' <?= ($inputs['gender_flag'] ?? '') == '2' ? 'checked' : '' ?>>女性
                    </label>
                    <label class="gender">
                        <input type="radio" name="gender_flag" value='3' <?= ($inputs['gender_flag'] ?? '') == '3' ? 'checked' : '' ?>>その他
                    </label>
                </div>

                <div>
                    <label>生年月日<span>必須</span></label>
                    <input type="text" name="birth_date" value="<?= htmlspecialchars($inputs['birth_date']) ?>" readonly class="readonly-field">
                    <?php if (isset($errors['birth_date'])) : ?>
                        <div class="error-msg2"><?= htmlspecialchars($errors['birth_date']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>郵便番号<span>必須</span></label>
                    <div class="postal-row">
                        <input class="half-width" type="text" name="postal_code" id="postal_code" placeholder="例）100-0001" value="<?= htmlspecialchars($inputs['postal_code'] ?? '') ?>">
                        <button type="button" class="postal-code-search" id="searchAddressBtn">住所検索</button>
                    </div>
                    <?php if (isset($errors['postal_code'])) : ?>
                        <div class="error-msg2"><?= htmlspecialchars($errors['postal_code']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>住所<span>必須</span></label>
                    <input type="text" name="prefecture" id="prefecture" placeholder="都道府県" value="<?= htmlspecialchars($inputs['prefecture'] ?? '') ?>">
                    <input type="text" name="city_town" id="city_town" placeholder="市区町村・番地" value="<?= htmlspecialchars($inputs['city_town'] ?? '') ?>">
                    <input type="text" name="building" placeholder="建物名・部屋番号  **省略可**" value="<?= htmlspecialchars($inputs['building'] ?? '') ?>">
                    <?php if (isset($errors['address'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['address']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>電話番号<span>必須</span></label>
                    <input type="text" name="tel" placeholder="例）000-000-0000" value="<?= htmlspecialchars($inputs['tel']) ?>">
                    <?php if (isset($errors['tel'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['tel']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input type="text" name="email" placeholder="例）guest@example.com" value="<?= htmlspecialchars($inputs['email']) ?>">
                    <?php if (isset($errors['email'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif ?>
                </div>

                <div>
                    <label>本人確認書類（表）</label>
                    <input type="file" name="document1" id="document1" accept="image/png, image/jpeg, image/jpg">
                    <?php if (isset($errors['document1'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['document1']) ?></div>
                    <?php endif ?>

                    <!-- <span id="filename1" class="filename-display">
                        <?= htmlspecialchars($file_names['document1'] ?? '') ?>
                    </span> -->
                    <div class="preview-container">
                        <img id="preview1" src="#" alt="プレビュー画像１" style="display: none; max-width: 200px; margin-top: 8px;">
                    </div>
                </div>

                <div>
                    <label>本人確認書類（裏）</label>
                    <input type="file" name="document2" id="document2" accept="image/png, image/jpeg, image/jpg">
                    <?php if (isset($errors['document2'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($errors['document2']) ?></div>
                    <?php endif ?>

                    <!-- <span id="filename2" class="filename-display">
                        <?= htmlspecialchars($file_names['document2'] ?? '') ?>
                    </span> -->
                    <div class="preview-container">
                        <img id="preview2" src="#" alt="プレビュー画像２" style="display: none; max-width: 200px; margin-top: 8px;">
                    </div>
                </div>

                <button type="submit">更新</button>
                <input type="button" value="ダッシュボードに戻る" onclick="location.href='dashboard.php'">
        </form>

        <form action="delete.php" method="post" name="delete">
            <input type="hidden" name="id" value="<?= htmlspecialchars($inputs['id'] ?? $originalData['id'] ?? '') ?>">
            <!-- <button type="submit">削除</button> -->
            <button type="button" id="deleteBtn">削除</button>
        </form>
        <!-- ここにモーダルを追加 -->
        <div id="confirmModal" class="modal">
            <div class="modal-content">
                <p>本当に削除してもよろしいですか？</p>
                <div class="modal-buttons">
                    <button type="button" id="cancelBtn" class="btn-cancel">いいえ</button>
                    <button type="button" id="confirmBtn" class="btn-danger">はい</button>
                </div>
            </div>
        </div>

        <script>
            const modal = document.getElementById('confirmModal');
            const deleteBtn = document.getElementById('deleteBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const confirmBtn = document.getElementById('confirmBtn');

            // フォーカス可能要素
            const focusableElements = [cancelBtn, confirmBtn];

            // 削除ボタンクリック → モーダル表示
            deleteBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
                cancelBtn.focus(); // 最初に「いいえ」にフォーカス
            });

            // 「いいえ」クリック → モーダル閉じる
            cancelBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // 「はい」クリック → 削除フォーム送信
            confirmBtn.addEventListener('click', () => {
                document.forms['delete'].submit();
            });

            // モーダル内のキー操作
            modal.addEventListener('keydown', (e) => {
                // Enterキーでアクティブなボタンを実行
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (document.activeElement === confirmBtn) {
                        confirmBtn.click();
                    } else if (document.activeElement === cancelBtn) {
                        cancelBtn.click();
                    }
                }

                // Escapeキーでモーダル閉じる
                if (e.key === 'Escape') {
                    modal.style.display = 'none';
                }

                // Tabキーで「はい ↔ いいえ」のみ移動
                if (e.key === 'Tab') {
                    e.preventDefault();
                    let idx = focusableElements.indexOf(document.activeElement);
                    if (e.shiftKey) {
                        // Shift+Tab → 前へ
                        idx = (idx - 1 + focusableElements.length) % focusableElements.length;
                    } else {
                        // Tab → 次へ
                        idx = (idx + 1) % focusableElements.length;
                    }
                    focusableElements[idx].focus();
                }
            });
        </script>
    </div>
</body>


<?php
// unset($_SESSION['errors'], $_SESSION['inputs']);
unset($_SESSION['errors'], $_SESSION['inputs'], $_SESSION['file_names']);
?>

</html>