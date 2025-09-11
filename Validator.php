<?php

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('none'); // 必要なら
    session_start();
}
// session_start(); // ★ここを追加！

// date_default_timezone_set('Asia/Tokyo');


class Validator
{

    private $pdo;

    //DB接続情報
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private $error_message = [];

    // 呼び出し元で使う
    public function validate($data)
    {
        $this->error_message = [];

        $source = $_SESSION['source'] ?? '';

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
        // edit.php、input.phpのどのプログラムから呼び出されているか判定
        //$source == ""の時、input.phpから
        //$source == "edit"の時、edit.phpから
        // if ($source == "") {
        //     // 生年月日
        //     if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
        //         $this->error_message['birth_date'] = '生年月日が入力されていません';
        //     } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
        //         $this->error_message['birth_date'] = '生年月日が正しくありません';
        //     } else {
        //         // 1. 日付文字列を作る（YYYY-MM-DD）
        //         $birth_date_str = sprintf(
        //             '%04d-%02d-%02d',
        //             $data['birth_year'],
        //             $data['birth_month'],
        //             $data['birth_day']
        //         );

        //         // 2. DateTimeオブジェクトに変換
        //         $birth_date = DateTime::createFromFormat('Y-m-d', $birth_date_str);

        //         // 3. 今日の日付
        //         $today = new DateTime('today');

        //         // 4. 未来日かチェック
        //         if ($birth_date >= $today) {
        //             $this->error_message['birth_date'] = '生年月日が未来日です';
        //         } else {
        //         }
        //     }
        // }
        if ($source == "input") {
            $year  = isset($data['birth_year']) ? (int)$data['birth_year'] : 0;
            $month = isset($data['birth_month']) ? (int)$data['birth_month'] : 0;
            $day   = isset($data['birth_day']) ? (int)$data['birth_day'] : 0;

            // checkdateで有効な日付か確認
            if (!checkdate($month, $day, $year)) {
                $this->error_message['birth_date'] = '生年月日が正しくありません';
            } else {
                $birth_date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $birth_date = DateTime::createFromFormat('Y-m-d', $birth_date_str);
                if (!$birth_date) {
                    $this->error_message['birth_date'] = '生年月日が正しく解析できません';
                } else {
                    $today = new DateTime('today');
                    if ($birth_date >= $today) {
                        $this->error_message['birth_date'] = '生年月日が未来日です';
                    }
                }
            }
        }




        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が正しくありません';
        } else {
            // 1. ユーザー入力
            $input_zip = $data['postal_code'];
            $clean_zip = str_replace("-", "", $input_zip); // "1234567"
            $response = ['valid' => false];


            try {
                // ここで再接続せず、$this->pdo を使ってクエリを実行
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM address_master WHERE postal_code = :postal_code");
                $stmt->execute([':postal_code' => $clean_zip]);
                $count = $stmt->fetchColumn();
                $response['valid'] = $count > 0;

                if ($count == 0) {
                    $this->error_message['postal_code'] = "郵便番号が見つかりません";
                }
            } catch (PDOException $e) {
                $response['valid'] = false;
                $this->error_message['postal_code'] = "郵便番号の確認中にエラーが発生しました";
            }
        }

        // 住所

        if (empty($data['prefecture']) || empty($data['city_town'])) {
            $this->error_message['address'] = '住所(都道府県もしくは市区町村・番地)が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        } else {

            try {
                // ここで再接続せず、$this->pdo を使ってクエリを実行
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM address_master WHERE prefecture = :prefecture");
                $stmt->execute([':prefecture' => $data['prefecture']]);
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    $this->error_message['address'] = "有効な都道府県ではありません";
                }
            } catch (PDOException $e) {
                $this->error_message['address'] = "都道府県の確認中にエラーが発生しました";
            }
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

        if ($source == "input") {
            // パスワード
            if (empty($data['password'])) {
                $this->error_message['password'] = 'パスワードが入力されていません';
            } elseif (mb_strlen($data['password']) > 5) {
                $this->error_message['password'] = 'パスワードは5文字以内で入力してください';
            }
            // パスワード（確認）
            if (empty($data['password_confirm'])) {
                $this->error_message['password_confirm'] = 'パスワード（確認）が入力されていません';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $this->error_message['password_confirm'] = 'パスワードと一致しません';
            }
        };

        // ファイルのMIMEタイプをチェックする処理
        if (isset($data['document1']) && is_array($data['document1']) && $data['document1']['error'] === UPLOAD_ERR_OK) {
            $mime1 = mime_content_type($data['document1']['tmp_name']);
            if ($mime1 !== 'image/png' && $mime1 !== 'image/jpeg') {
                $this->error_message['document1'] = 'ファイル形式は PNG または JPEG のみ許可されています';
                echo "document1";
                // var_dump(error_message['document1']);
            }
        }

        if (isset($data['document2']) && is_array($data['document2']) && $data['document2']['error'] === UPLOAD_ERR_OK) {
            $mime2 = mime_content_type($data['document2']['tmp_name']);
            if ($mime2 !== 'image/png' && $mime2 !== 'image/jpeg') {
                $this->error_message['document2'] = 'ファイル形式は PNG または JPEG のみ許可されています';
                echo "document2";
            }
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
