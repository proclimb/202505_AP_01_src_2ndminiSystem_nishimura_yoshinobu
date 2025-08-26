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

// 検索ボタンを押した場合はページを1にリセット
if (isset($_GET['search_submit'])) {
    $page = 1;
}
//


// ---------------------------------------------
// 2. ページネーション用定数・総件数数取得
// ---------------------------------------------
$userModel  = new User($pdo);
$totalCount = $userModel->countUsersWithKeyword($keyword, $column);

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
    $column
);

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

        <!-- 検索ボタン -->
        <input type="submit" name="search_submit" value="検索" class="form-control btn">

        <!-- 検索ワード入力 -->
        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES) ?>" class="form-control" placeholder="検索ワードを入力">

        <!-- 検索対象プルダウン -->
        <select name="column" class="form-select">
            <option value="name" <?= $column === 'name' ? 'selected' : '' ?>>名前</option>
            <option value="kana" <?= $column === 'kana' ? 'selected' : '' ?>>ふりがな</option>
            <option value="address" <?= $column === 'address' ? 'selected' : '' ?>>住所</option>
        </select>



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
                    <td><?= htmlspecialchars($val['postal_code']); ?></td>
                    <td><?= htmlspecialchars($val['prefecture'] . $val['city_town'] . $val['building']); ?></td>
                    <td><?= htmlspecialchars($val['tel']); ?></td>
                    <td><?= htmlspecialchars($val['email']); ?></td>
                    <!-- 追加した出力部分：書類①(front_image) -->
                    <td><?php if ((int)$val['has_front'] === 1): ?>
                            <a
                                class="dl-link"
                                href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=front"
                                target="_blank">DL</a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                    <!-- 追加した出力部分：書類②(back_image) -->
                    <td>
                        <?php if ((int)$val['has_back'] === 1): ?>
                            <a
                                class="dl-link"
                                href="Showdocument.php?user_id=<?= urlencode($val['id']) ?>&type=back"
                                target="_blank">DL</a>
                        <?php else: ?>
                            無し
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- 7. ページネーション -->
    <?= paginationLinks($page, $totalPages, $keyword, $sortBy, $sortOrd, $column) ?>

    <!-- 8. 「TOPに戻る」ボタン -->
    <a href="index.php">
        <button type="button">TOPに戻る</button>
    </a>
</body>

</html>