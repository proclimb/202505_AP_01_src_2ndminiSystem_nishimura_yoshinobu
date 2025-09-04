<?php
session_start();
require_once 'Db.php'; // DB接続($pdo)が定義されているファイル

// デバッグ用：エラー表示をON
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 入力値を取得
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// 1. 入力チェック
if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'ユーザー名またはパスワードが未入力です。';
    header('Location: login.php');
    exit; // ここで確実に処理終了
}

// 2. DBでユーザーを検索
$sql = "SELECT id, email, password,role FROM user_base WHERE email = :email LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':email', $username, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// var_dump($username, $password, $user);
// echo '<pre>';     // 見やすく整形
// exit;

// 3. 認証
if (!$user || !password_verify($password, $user['password'])) {
    // if (!$user || $user['password'] !== $password) {
    $_SESSION['login_error'] = 'メールアドレスまたはパスワードが間違っています。';
    header('Location: login.php');
    exit; // 失敗時はここで必ず終了
}
// var_dump($user['role']);
// echo '<pre>';     // 見やすく整形
// exit;
$_SESSION['role'] = $user['role']; // admin または user
// 4. ログイン成功時
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['name'];

header('Location: dashboard.php');
exit;
