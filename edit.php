<?php

/**
 * 更新・削除画面
 *
 * ダッシュボードまたは確認画面から遷移
 */

session_cache_limiter('none');
session_start();

// 1. 必要ファイル読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Validator.php';

// 遷移元をセッションで保持
$_SESSION['source'] = 'edit';

// 2. GETからID取得
$id = $_GET['id'] ?? null;
if (!$id) {
    die('IDが指定されていません');
}

// 3. UserクラスでDBからユーザー情報を取得
$user = new User($pdo);
$originalData = $user->findById($id);
if (!$originalData) {
    die('指定されたユーザーは存在しません');
}

// ========================
// バリデーション処理
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array_merge($_POST, $_FILES);
    $data['source'] = $_SESSION['source'] ?? '';

    $validator = new Validator($pdo);

    if ($validator->validate($data)) {
        // バリデーション成功時
        $_SESSION['input_data'] = $_POST;

        // アップロードファイルをセッションに保存
        if (!empty($_FILES['document1']['tmp_name'])) {
            $_SESSION['files']['document1'] = $_FILES['document1'];
        }
        if (!empty($_FILES['document2']['tmp_name'])) {
            $_SESSION['files']['document2'] = $_FILES['document2'];
        }

        // 表示用のファイル名も保存
        $_SESSION['file_names'] = [
            'document1' => $_FILES['document1']['name'] ?? ($_SESSION['file_names']['document1'] ?? ''),
            'document2' => $_FILES['document2']['name'] ?? ($_SESSION['file_names']['document2'] ?? ''),
        ];
        // echo ("DB name");
        // var_dump($_SESSION['file_names']);
        // 一時アップロードディレクトリ作成
        $tmpDir = __DIR__ . '/tmp_uploads/';
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // ファイル移動
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
        // --- 表画像(document1)のSESSION設定 ---

        // 新規ファイルが選択されているか確認
        // 表画像アップロード処理
        if (!empty($_FILES['document1']['tmp_name']) && is_uploaded_file($_FILES['document1']['tmp_name'])) {
            $ext = pathinfo($_FILES['document1']['name'], PATHINFO_EXTENSION);
            $newPath = $tmpDir . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['document1']['tmp_name'], $newPath);

            // セッションに保存
            $_SESSION['files']['document1'] = $newPath;              // フルパス（表示用）
            $_SESSION['file_names']['document1'] = $_FILES['document1']['name']; // 元のファイル名（表示用）
        }
        // DBに画像がある場合は別セッションで保持
        if (!empty($originalData['front_image_name'])) {
            $_SESSION['db_files']['document1'] = 'Showdocument.php?user_id=' . $id . '&type=front';
            $_SESSION['db_file_names']['document1'] = $originalData['front_image_name'];
        } else {
            $_SESSION['db_files']['document1'] = '';
            $_SESSION['db_file_names']['document1'] = '';
        }

        // --- 裏画像(document2)のSESSION設定 ---

        // 新規ファイルが選択されているか確認
        // 裏画像アップロード処理
        if (!empty($_FILES['document2']['tmp_name']) && is_uploaded_file($_FILES['document2']['tmp_name'])) {
            $ext = pathinfo($_FILES['document2']['name'], PATHINFO_EXTENSION);
            $newPath = $tmpDir . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['document2']['tmp_name'], $newPath);

            // セッションに保存
            $_SESSION['files']['document2'] = $newPath;              // フルパス（表示用）
            $_SESSION['file_names']['document2'] = $_FILES['document2']['name']; // 元のファイル名（表示用）
        }

        // DBに画像がある場合は別セッションで保持
        if (!empty($originalData['back_image_name'])) {
            $_SESSION['db_files']['document2'] = 'Showdocument.php?user_id=' . $id . '&type=back';
            $_SESSION['db_file_names']['document2'] = $originalData['back_image_name'];
        } else {
            $_SESSION['db_files']['document2'] = '';
            $_SESSION['db_file_names']['document2'] = '';
        }
        $_SESSION['delete_flags']['front'] = $_POST['delete_front'] ?? 0;
        $_SESSION['delete_flags']['back']  = $_POST['delete_back']  ?? 0;


        // 確認画面へ
        header('Location: confirm.php');
        exit();
    } else {
        // バリデーション失敗
        $_SESSION['errors'] = $validator->getErrors();
        $_SESSION['inputs'] = $data;

        $_SESSION['file_names'] = [
            'document1' => $_FILES['document1']['name'] ?? '',
            'document2' => $_FILES['document2']['name'] ?? '',
        ];
        // 自画面に戻る
        header('Location: edit.php?id=' . urlencode($id));
        exit();
    }
}

// ========================
// 表示用データの選択
// ========================
// confirm.phpから戻ってきた場合はセッション優先
if (!empty($_SESSION['input_data'])) {
    $inputs = $_SESSION['input_data'];
} elseif (!empty($_SESSION['inputs'])) {
    $inputs = $_SESSION['inputs'];
} else {
    $inputs = $originalData;
}
// ↓ ここに挿入
// $frontFilename = $originalData['front_filename'] ?? '';
// $backFilename  = $originalData['back_filename'] ?? '';

// DB の名前を優先して取得
$file_names = [
    'document1' => $originalData['front_image_name'] ?? '',
    'document2' => $originalData['back_image_name'] ?? '',
];

// var_dump($file_names);
// exit;

$errors = $_SESSION['errors'] ?? [];
// $file_names = $_SESSION['file_names'] ?? [];

// 一度使ったら削除
// unset($_SESSION['errors'], $_SESSION['inputs']);
// DBから取得したファイル名をセッションにセット
// $_SESSION['file_names']['document1'] = $originalData['front_image_name'] ?? '';
// $_SESSION['file_names']['document2'] = $originalData['back_image_name'] ?? '';


// ここからHTMLを描画
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
            <!-- <?php var_dump($inputs); ?> -->
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
                    <input type="text" name="name" id="name" placeholder="例）山田太郎" value="<?= htmlspecialchars($inputs['name']) ?>">
                    <div>
                        <p id="name-error" class="error-msg">
                            <?= htmlspecialchars($errors['name'] ?? '') ?>
                        </p>
                    </div>
                </div>
                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input type="text" name="kana" id="kana" placeholder="例）やまだたろう" value="<?= htmlspecialchars($inputs['kana']) ?>">
                    <div>
                        <p id="kana-error" class="error-msg">
                            <?= htmlspecialchars($errors['kana'] ?? '') ?>
                        </p>
                    </div>
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
                    <div>
                        <p id="postal-error" class="error-msg2">
                            <?= htmlspecialchars($errors['postal_code'] ?? '') ?>
                        </p>
                    </div>

                </div>

                <div>
                    <label>住所<span>必須</span></label>
                    <input type="text" name="prefecture" id="prefecture" placeholder="都道府県" value="<?= htmlspecialchars($inputs['prefecture'] ?? '') ?>">
                    <input type="text" name="city_town" id="city_town" placeholder="市区町村・番地" value="<?= htmlspecialchars($inputs['city_town'] ?? '') ?>">
                    <input type="text" name="building" id="building" placeholder="建物名・部屋番号  **省略可**" value="<?= htmlspecialchars($inputs['building'] ?? '') ?>">
                    <div>
                        <p id="address-error" class="error-msg">
                            <?= htmlspecialchars($errors['address'] ?? '') ?>
                        </p>
                    </div>
                </div>

                <div>
                    <label>電話番号<span>必須</span></label>
                    <input type="text" name="tel" id="tel" placeholder="例）000-000-0000" value="<?= htmlspecialchars($inputs['tel']) ?>">
                    <div>
                        <p id="tel-error" class="error-msg">
                            <?= htmlspecialchars($errors['tel'] ?? '') ?>
                        </p>
                    </div>
                </div>

                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input type="text" name="email" id="email" placeholder="例）guest@example.com" value="<?= htmlspecialchars($inputs['email']) ?>">
                    <div>
                        <p id="email-error" class="error-msg">
                            <?= htmlspecialchars($errors['email'] ?? '') ?>
                        </p>
                    </div>

                </div>
                <!-- 表画像 -->
                <div>
                    <label>本人確認書類（表）</label>

                    <!-- ファイル選択 -->
                    <input type="file" name="document1" id="document1" accept="image/png, image/jpeg, image/jpg">

                    <p id="document1-error" class="error-msg">
                        <?= htmlspecialchars($errors['document1'] ?? '') ?>
                    </p>

                    <div class="preview-container">
                        <?php if (!empty($file_names['document1'])): ?>
                            <img id="preview1"
                                src="Showdocument.php?user_id=<?= $id ?>&type=front"
                                alt="表画像"
                                style="max-width:200px; display:block; margin:0 auto;">

                            <!-- 削除チェックボックス -->
                            <div class="delete-checkbox-container">
                                <label>
                                    <input type="checkbox" name="delete_front" id="delete_front" value="1">
                                    この画像を削除する
                                </label>
                            </div>
                        <?php else: ?>
                            <p style="margin:0;">画像は登録されていません</p>
                        <?php endif; ?>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const fileInput = document.getElementById('document1');
                        const deleteCheckbox = document.getElementById('delete_front');

                        if (deleteCheckbox) {
                            // 削除チェックされたらファイル選択を無効化
                            deleteCheckbox.addEventListener('change', function() {
                                if (this.checked) {
                                    fileInput.value = ''; // 選択中のファイルをクリア
                                    fileInput.disabled = true;
                                } else {
                                    fileInput.disabled = false;
                                }
                            });
                        }

                        if (fileInput) {
                            // ファイル選択されたら削除チェックを無効化
                            fileInput.addEventListener('change', function() {
                                if (this.files.length > 0 && deleteCheckbox) {
                                    deleteCheckbox.checked = false;
                                    deleteCheckbox.disabled = true;
                                } else if (deleteCheckbox) {
                                    deleteCheckbox.disabled = false;
                                }
                            });
                        }
                    });
                </script>

                <!-- 裏画像 -->
                <div>
                    <label>本人確認書類（裏）</label>

                    <!-- ファイル選択 -->
                    <input type="file" name="document2" id="document2" accept="image/png, image/jpeg, image/jpg">

                    <p id="document2-error" class="error-msg">
                        <?= htmlspecialchars($errors['document2'] ?? '') ?>
                    </p>

                    <div class="preview-container">
                        <?php if (!empty($file_names['document2'])): ?>
                            <img id="preview2"
                                src="Showdocument.php?user_id=<?= $id ?>&type=back"
                                alt="裏画像"
                                style="max-width:200px; display:block; margin:0 auto;">

                            <!-- 削除チェックボックス -->
                            <div class="delete-checkbox-container">
                                <label>
                                    <input type="checkbox" name="delete_back" id="delete_back" value="1">
                                    この画像を削除する
                                </label>
                            </div>
                        <?php else: ?>
                            <p style="margin:0;">画像は登録されていません</p>
                        <?php endif; ?>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const fileInputBack = document.getElementById('document2');
                        const deleteCheckboxBack = document.getElementById('delete_back');

                        if (deleteCheckboxBack) {
                            // 削除チェックされたらファイル選択を無効化
                            deleteCheckboxBack.addEventListener('change', function() {
                                if (this.checked) {
                                    fileInputBack.value = ''; // 選択中のファイルをクリア
                                    fileInputBack.disabled = true;
                                } else {
                                    fileInputBack.disabled = false;
                                }
                            });
                        }

                        if (fileInputBack) {
                            // ファイル選択されたら削除チェックを無効化
                            fileInputBack.addEventListener('change', function() {
                                if (this.files.length > 0 && deleteCheckboxBack) {
                                    deleteCheckboxBack.checked = false;
                                    deleteCheckboxBack.disabled = true;
                                } else if (deleteCheckboxBack) {
                                    deleteCheckboxBack.disabled = false;
                                }
                            });
                        }
                    });
                </script>
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

    <!-- ここでJSを読み込む -->
    <script src="validation.js"></script>
</body>


<?php
// unset($_SESSION['errors'], $_SESSION['inputs']);
unset($_SESSION['errors'], $_SESSION['inputs']);
?>

</html>