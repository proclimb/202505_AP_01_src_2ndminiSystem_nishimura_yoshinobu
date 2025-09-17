<?php

/**
 * ダッシュボード画面
 *
 * ** ダッシュボード画面は、TOPから遷移してきます
 *
 * ** ダッシュボードで行う処理は以下です
 * ** 1.DB接続情報、クラス定義をそれぞれのファイルから読み込む
 * ** 2.ユーザ情報を取得する
 * **   1.Userクラスをインスタスタンス化する
 * **     ＊User(設計図)に$user(実体)を付ける
 * **   2.メソッドを実行しユーザー情報を取得する
 * **     ＊システム開発演習Ⅰで、キーワード検索機能は実装しない
 * ** 3.html を描画
 * **   DBから取得した結果を <table>タグを使用して表示しています
 * **   $result が、0件の場合は、表を表示しない
 * **   ユーザ情報が有る場合は、foreach を使用して検索結果をします
 * **   編集のリンクに関しては、idの値をURLに設定してGET送信で「更新・削除」へidを渡します
 */
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none'); // 必要なら
    session_start();
}

// セッションを全消去
session_unset();

//  1.DB接続情報、クラス定義の読み込み
require_once 'Db.php';
require_once 'User.php';
require_once 'Sort.php';      // ソート関連の処理と sortLink() 関数を定義
require_once 'Page.php';      // ページネーション関連の処理と paginationLinks() 関数を定義

// ---------------------------------------------
// 1. リクエストパラメータ取得・初期化
// ---------------------------------------------
// $keyword = '';
// $sortBy      = $sortBy  ?? null;  // sort.php でセット済み
// $sortOrd     = $sortOrd ?? 'asc'; // sort.php でセット済み
// $page        = $page    ?? 1;     // page.php でセット済み

// // 検索フォームで「検索」ボタンが押された場合
// if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_submit'])) {
//     // $nameKeyword = trim($_GET['search_name'] ?? '');
//     $keyword = trim($_GET['keyword'] ?? '');

//     // 検索時は常に1ページ目、ソートもリセット
//     $sortBy  = null;
//     $sortOrd = 'asc';
//     $page    = 1;
// } else {
//     // 検索キーがある場合のみ受け取る
//     // $nameKeyword = trim($_GET['search_name'] ?? '');
//     $keyword = trim($_GET['keyword'] ?? '');
//     // ソートとページは sort.php / page.php により既にセット済み
//     // ソートパラメータ
//     $sortBy  = $_GET['sort_by'] ?? null;      // GETから取得
//     $sortOrd = $_GET['sort_order'] ?? 'asc';  // GETから取得

//     // ページ番号
//     $page = (int)($_GET['page'] ?? 1);
// }
//
$keyword = trim($_GET['keyword'] ?? '');
$column = $_GET['column'] ?? 'name'; // デフォルトは name

// ソートパラメータ
$sortBy  = $_GET['sort_by'] ?? null;
$sortOrd = $_GET['sort_order'] ?? 'asc';

// ページ番号
$page = (int)($_GET['page'] ?? 1);

$ageMin = isset($_GET['age_min']) && $_GET['age_min'] !== '' ? (int)$_GET['age_min'] : null;
$ageMax = isset($_GET['age_max']) && $_GET['age_max'] !== '' ? (int)$_GET['age_max'] : null;


// 検索ボタンを押した場合はページを1にリセット
if (isset($_GET['search_submit'])) {
    $page = 1;
}
//


// ---------------------------------------------
// 2. ページネーション用定数・総件数数取得
// ---------------------------------------------
$userModel  = new User($pdo);
$totalCount = $userModel->countUsersWithKeyword($keyword, $column, $ageMin, $ageMax);

// 1ページあたりの表示件数
$limit = 10;

// ページネーション用パラメータを取得 (update $page, $offset, $totalPages)
list($page, $offset, $totalPages) = getPaginationParams($totalCount, $limit);

// ---------------------------------------------
// 3. 実際のユーザー一覧を取得
// ---------------------------------------------
$users = $userModel->fetchUsersWithKeyword(
    $keyword,
    $sortBy,
    $sortOrd,
    $offset,
    $limit,
    $column,
    $ageMin,
    $ageMax
);

$role = $_SESSION['role'];
// var_dump($_SESSION['role']);
function calculateAge($birthDate)
{
    if (!$birthDate) return '';
    $birth = new DateTime($birthDate);
    $today = new DateTime('today');
    return $birth->diff($today)->y; // 満年齢
}
// 3.html の描画
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
        <h2>ダッシュボード</h2>
    </div>
    <form method="get" action="dashboard.php" class="name-search-form" style="width:80%; margin: 20px auto;">


        <div class="search-form-container">

            <!-- 1段目: 検索ワード左、プルダウン右 -->
            <div class="form-row first-row">
                <input type="text" name="keyword"
                    value="<?= htmlspecialchars($_GET['keyword'] ?? '', ENT_QUOTES) ?>"
                    placeholder="検索ワードを入力"
                    class="custom-input input-text">

                <select name="column" class="custom-input select-box">
                    <option value="name" <?= ($_GET['column'] ?? '') === 'name' ? 'selected' : '' ?>>名前</option>
                    <option value="kana" <?= ($_GET['column'] ?? '') === 'kana' ? 'selected' : '' ?>>ふりがな</option>
                    <option value="address" <?= ($_GET['column'] ?? '') === 'address' ? 'selected' : '' ?>>住所</option>
                </select>
            </div>

            <!-- 2段目: 年齢下限・～・上限 + ボタン -->
            <div class="form-row second-row">
                <label for="age_min" class="custom-label">下限</label>
                <input type="number" name="age_min"
                    value="<?= htmlspecialchars($_GET['age_min'] ?? '', ENT_QUOTES) ?>"
                    placeholder="年齢下限"
                    class="custom-input input-number">

                <label class="tilde">～</label> <!-- 独立 -->

                <label for="age_max" class="custom-label">上限</label>
                <input type="number" name="age_max"
                    value="<?= htmlspecialchars($_GET['age_max'] ?? '', ENT_QUOTES) ?>"
                    placeholder="年齢上限"
                    class="custom-input input-number">

                <input type="submit" name="search_submit" value="検索" class="custom-button">
                <a href="dashboard.php" class="custom-button">条件をクリア</a>
            </div>

        </div>

        <!-- <label for="search_name">名前で検索：</label>
        <input
            type="text"
            name="search_name"
            id="search_name"
            value="<?= htmlspecialchars($nameKeyword, ENT_QUOTES) ?>"
            placeholder="名前の一部を入力">
        <input type="submit" name="search_submit" value="検索"> -->
    </form>

    <!-- 5. 検索結果件数表示（テーブルの左上へ置きたいので、幅80%・中央寄せして左寄せテキスト） -->
    <div class="result-count" style="width:80%; margin: 5px auto 0;">
        検索結果：<strong><?= $totalCount ?></strong> 件
    </div>

    <!-- 6. 一覧テーブル -->
    <table class="common-table">
        <tr>
            <th>編集</th>
            <!-- <th>名前</th> -->
            <th>
                <?= sortLink('name', '名前', $sortBy, $sortOrd, $keyword, $column) ?>
            </th>
            <!-- ① ふりがな ソートリンク -->
            <th>
                <?= sortLink('kana', 'ふりがな', $sortBy, $sortOrd, $keyword, $column) ?>
            </th>
            <th>性別</th>
            <th>生年月日</th>
            <th>年齢</th>
            <!-- ② 郵便番号 ソートリンク -->
            <th>
                <?= sortLink('postal_code', '郵便番号', $sortBy, $sortOrd, $keyword, $column) ?>
            </th>
            <th>住所</th>
            <th>電話番号</th>
            <!-- ③ メールアドレス ソートリンク -->
            <th>
                <?= sortLink('email', 'メールアドレス', $sortBy, $sortOrd, $keyword, $column) ?>
            </th>
            <?php if ($role === 'admin'): ?>
                <th>パスワード</th>
            <?php else: ?>
                <!-- user の場合は非表示 -->
            <?php endif; ?>

            <th>画像①</th>
            <th>画像②</th>
        </tr>

        <?php if (count($users) === 0): ?>
            <tr>
                <td colspan="11" style="text-align:center; padding:10px 0;">
                    該当するデータがありません。
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $val): ?>
                <tr>
                    <td>
                        <a href="edit.php?id=<?= htmlspecialchars($val['id'], ENT_QUOTES) ?>">編集</a>
                    </td>
                    <td><?= htmlspecialchars($val['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($val['kana'], ENT_QUOTES) ?></td>
                    <!-- <td><?= $val['gender_flag'] == '1' ? '男性' : ($val['gender_flag'] == '2' ? '女性' : '未回答'); ?></td> -->
                    <td>
                        <?= $val['gender_flag'] == '1' ? '男性'
                            : ($val['gender_flag'] == '2' ? '女性'
                                : ($val['gender_flag'] == '3' ? 'その他' : '未回答')); ?>
                    </td>
                    <td><?= date('Y年n月j日', htmlspecialchars(strtotime($val['birth_date']))); ?></td>
                    <td><?= calculateAge($val['birth_date']); ?>歳</td>
                    <td><?= htmlspecialchars($val['postal_code']); ?></td>
                    <td><?= htmlspecialchars($val['prefecture'] . $val['city_town'] . $val['building']); ?></td>
                    <td><?= htmlspecialchars($val['tel']); ?></td>
                    <td><?= htmlspecialchars($val['email']); ?></td>
                    <?php if ($role === 'admin'): ?>
                        <!-- <?= htmlspecialchars($val['password'], ENT_QUOTES, 'UTF-8'); ?> -->
                        <td><?= htmlspecialchars($val['password']); ?></td>
                    <?php else: ?>
                        <!-- user の場合は非表示 -->
                    <?php endif; ?>
                    <!-- 追加した出力部分：書類①(front_image) -->
                    <!-- 書類①（front） -->
                    <td>
                        <?php if ((int)$val['has_front'] === 1): ?>
                            <a href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=front" target="_blank">
                                <?= htmlspecialchars($val['front_image_name'], ENT_QUOTES) ?>
                            </a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$val['has_back'] === 1): ?>
                            <a href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=back" target="_blank">
                                <?= htmlspecialchars($val['back_image_name'], ENT_QUOTES) ?>
                            </a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- 7. ページネーション -->
    <?= paginationLinks($page, $totalPages, $keyword, $sortBy, $sortOrd, $column, $ageMin, $ageMax) ?>

    <!-- 8. 「TOPに戻る」ボタン -->
    <!-- TOPに戻るボタン -->
    <button type="button" onclick="location.href='index.php'">TOPに戻る</button>

    <!-- ログアウトボタン -->
    <button type="button" onclick="location.href='logout.php'">ログアウト</button>

</body>

</html>