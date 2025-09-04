<?php
session_start();

$_SESSION['source'] = 'login';

// エラーメッセージ取得
$message = '';
if (!empty($_SESSION['login_error'])) {
    $message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <!-- ヘッダー -->
    <header class="header">
        <h1>MiniSystem</h1>
    </header>

    <!-- ログインフォームラッパー -->
    <div class="wrapper">
        <h2>ログイン</h2>


        <form action="login_check.php" method="post">
            <div class="form-group">
                <label for="username">メールアドレス</label>
                <input type="text" id="username" name="username">
            </div>
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" class="btn-submit">ログイン</button>

            <?php if ($message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

        </form>

        <div class="register-link">
            <a href="input.php">新規登録の方はこちら</a>
        </div>
    </div>
</body>

</html>