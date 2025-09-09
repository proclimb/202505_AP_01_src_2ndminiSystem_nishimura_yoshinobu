<?php
// page.php
// ──────────────────────────────────────────
// ダッシュボードの「ページネーション」関連のロジックをまとめる。
// ──────────────────────────────────────────

/**
 * 1. GET パラメータから現在のページ番号を取得（デフォルト:1）
 */
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;

/**
 * 2. ページネ―ション用のパラメータを計算する関数
 *
 * @param int $totalCount   検索結果の総件数
 * @param int $limit        １ページあたりの表示件数
 * @return array            [ $page, $offset, $totalPages ]
 */
function getPaginationParams(int $totalCount, int $limit): array
{
    global $page;

    $totalPages = (int)ceil($totalCount / $limit);
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    // ページ番号が総ページ数を超えていた場合は補正
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $limit;
    return [$page, $offset, $totalPages];
}

/**
 * 3. ページネーションリンクを生成する関数
 *
 * @param int         $currentPage  現在のページ番号
 * @param int         $totalPages   総ページ数
 * @param string      $nameKeyword  検索キーワード
 * @param string|null $sortBy       ソート対象カラム
 * @param string|null $sortOrd      ソート順 ("asc"|"desc")
 * @return string                  HTML の <div class="pagination">…</div> 部分
 */
function paginationLinks(
    int $currentPage,
    int $totalPages,
    string $keyword = '',
    ?string $sortBy = null,
    ?string $sortOrd = 'asc',
    string $column = 'name',
    ?int $ageMin = null,
    ?int $ageMax = null
): string {
    $pageGroupSize = 5;
    $html = '';

    // ベースパラメータ
    $baseParams = [];
    if ($keyword !== '') {
        $baseParams['keyword'] = $keyword;
    }
    if ($sortBy !== null) {
        $baseParams['sort_by'] = $sortBy;
        $baseParams['sort_order'] = $sortOrd;
    }
    if (!empty($column)) {
        $baseParams['column'] = $column;
    }
    if ($ageMin !== null) {
        $baseParams['age_min'] = $ageMin;
    }
    if ($ageMax !== null) {
        $baseParams['age_max'] = $ageMax;
    }

    // グループ計算
    $groupStart = (int)(floor(($currentPage - 1) / $pageGroupSize) * $pageGroupSize) + 1;
    $groupEnd   = min($groupStart + $pageGroupSize - 1, $totalPages);

    // 「前へ」
    if ($currentPage > 1) {
        $prevParams = array_merge($baseParams, ['page' => $currentPage - 1]);
        $qs = http_build_query($prevParams, '', '&amp;');
        $html .= "<a href=\"dashboard.php?$qs\">&lt; 前へ</a> ";
    }

    // ページ番号
    for ($p = $groupStart; $p <= $groupEnd; $p++) {
        if ($p === $currentPage) {
            $html .= "<strong>$p</strong> ";
        } else {
            $linkParams = array_merge($baseParams, ['page' => $p]);
            $qs = http_build_query($linkParams, '', '&amp;');
            $html .= "<a href=\"dashboard.php?$qs\">$p</a> ";
        }
    }

    // 「次へ」
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($baseParams, ['page' => $currentPage + 1]);
        $qs = http_build_query($nextParams, '', '&amp;');
        $html .= "<a href=\"dashboard.php?$qs\">次へ &gt;</a>";
    }

    if ($totalPages <= 1) return '';

    return "<div class=\"pagination\" style=\"width:80%; margin: 10px auto; text-align:center;\">$html</div>";
}
