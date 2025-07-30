<?php

class Validator
{
    private $error_message = [];

    // 呼び出し元で使う
    public function validate($data)
    {
        $this->error_message = [];

        // 名前
        if (empty($data['name'])) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        }

        // ふりがな
        if (empty($data['kana'])) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/[^ぁ-んー]/u', $data['kana'])) {
            $this->error_message['kana'] = 'ひらがなを入れてください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }


        // 生年月日
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } else {
            // 1. 日付文字列を作る（YYYY-MM-DD）
            $birth_date_str = sprintf(
                '%04d-%02d-%02d',
                $data['birth_year'],
                $data['birth_month'],
                $data['birth_day']
            );

            // 2. DateTimeオブジェクトに変換
            $birth_date = DateTime::createFromFormat('Y-m-d', $birth_date_str);

            // 3. 今日の日付
            $today = new DateTime('today');

            // 4. 未来日かチェック
            if ($birth_date >= $today) {
                //echo "未来日です。生年月日として正しくありません。";
                $this->error_message['birth_date'] = '未来日です。生年月日として正しくありません。';
            } else {
                //echo "問題なし。過去日か今日の日付です。";
                $this->error_message['birth_date'] = '問題なし。過去日か今日の日付です。';
            }
        }







        // 郵便番号
        // if (empty($data['postal_code'])) {
        //     $this->error_message['postal_code'] = '郵便番号が入力されていません';
        // } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'] ?? '')) {
        //     $this->error_message['postal_code'] = '郵便番号が正しくありません';
        // } else {
        // // 1. ユーザー入力
        //     $input_zip = $data['postal_code'];
        // // 2. ハイフンを除去
        //     $clean_zip = str_replace("-", "", $input_zip); // → "1234567"

        //     // 3. データベース接続（PDO）
        //     $host = 'localhost';
        //     $dbname = 'minisystem_relation';
        //     $user = 'root';
        //     $password = 'proclimb';
        //     $charset = 'utf8mb4';
        //     try {
        //         $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
        //         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //     // 4. 郵便番号が存在するかチェック
        //         $stmt = $pdo->prepare("SELECT COUNT(*) FROM address_master WHERE postal_code = $clean_zip");
        //         $stmt->execute([':postal_code' => $clean_zip]);
        //         $count = $stmt->fetchColumn();

        //         if ($count > 0) {
        //             //echo "郵便番号 {$clean_zip} は既に存在します。";
        //             $this->error_message['postal_code'] = '郵便番号 {$clean_zip} は既に存在します。';
        //         } else {
        //             //echo "郵便番号 {$clean_zip} は未登録です。";
        //             $this->error_message['postal_code'] = '郵便番号 {$clean_zip} は既に存在します。';
        //         }
        //     }
        // }
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        } else {
            // 1. ユーザー入力
            $input_zip = $data['postal_code'];
            // 2. ハイフンを除去
            $clean_zip = str_replace("-", "", $input_zip); // "1234567"

            // 3. データベース接続（PDO）
            $host = 'localhost';
            $dbname = 'minisystem_relation';
            $user = 'root';
            $password = 'proclimb';
            $charset = 'utf8mb4';

            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // 4. 郵便番号が存在するかチェック
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM address_master WHERE postal_code = :postal_code");
                $stmt->execute([':postal_code' => $clean_zip]);
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    //     $this->error_message['postal_code'] = "郵便番号 {$clean_zip} は既に存在します。";
                    // } else {
                    $this->error_message['postal_code'] = "郵便番号が見つかりません";
                }
            } catch (PDOException $e) {
                $this->error_message['postal_code'] = 'データベースエラー: ' . $e->getMessage();
            }
        }
        // //郵便番号検索
        // // 1. ユーザー入力
        // if (!empty($data['postal_code'])){
        //     $input_zip = $data['postal_code'];
        // // 2. ハイフンを除去
        //     $clean_zip = str_replace("-", "", $input_zip); // → "1234567"

        //     // 3. データベース接続（PDO）
        //     $host = 'localhost';
        //     $dbname = 'minisystem_relation';
        //     $user = 'root';
        //     $password = 'proclimb';
        //     $charset = 'utf8mb4';
        //     try {
        //         $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
        //         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //     // 4. 郵便番号が存在するかチェック
        //         $stmt = $pdo->prepare("SELECT COUNT(*) FROM address_master WHERE postal_code = $clean_zip");
        //         $stmt->execute([':postal_code' => $clean_zip]);
        //         $count = $stmt->fetchColumn();

        //         if ($count > 0) {
        //             echo "郵便番号 {$clean_zip} は既に存在します。";
        //         } else {
        //             echo "郵便番号 {$clean_zip} は未登録です。";
        //         }
        //     }
        // }






        // 住所
        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        }

        // 電話番号
        if (empty($data['tel'])) {
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel'] ?? '') ||
            mb_strlen($data['tel']) < 12 ||
            mb_strlen($data['tel']) > 13
        ) {
            $this->error_message['tel'] = '電話番号は12~13桁で正しく入力してください';
        }

        // メールアドレス
        if (empty($data['email'])) {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        }

        return empty($this->error_message);
    }


    // エラーメッセージ取得
    public function getErrors()
    {
        return $this->error_message;
    }

    // 生年月日の日付整合性チェック
    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}
