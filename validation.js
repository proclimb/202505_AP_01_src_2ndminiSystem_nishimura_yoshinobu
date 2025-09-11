window.onload = () => {
    const nameInput = document.getElementById("name");
    const nameError = document.getElementById("name-error");

    if (nameInput && nameError) {
        nameInput.addEventListener("input", () => {
            let value = nameInput.value.trim(); // 前後空白を除去
            let error = "";

            // Validator.php のルールに合わせる
            if (!value) {
                error = "名前が入力されていません";
            } else if (value.length > 20) {
                error = "名前は20文字以内で入力してください";
            }

            nameError.textContent = error;
        });
    }
    const kanaInput = document.getElementById("kana");
    const kanaError = document.getElementById("kana-error");

    if (kanaInput && kanaError) {
        kanaInput.addEventListener("input", () => {
            let value = kanaInput.value.trim();
            let error = "";

            if (!value) {
                error = "ふりがなが入力されていません";
            }
            else if (!/^[ぁ-んー]+$/.test(value)) {
                error = "ひらがなを入れてください";
            }
            else if (value.length > 20) {
                error = "ふりがなは20文字以内で入力してください";
            }

            kanaError.textContent = error;
        });
    }
    const yearInput = document.getElementById("birth_year");
    const monthInput = document.getElementById("birth_month");
    const dayInput = document.getElementById("birth_day");
    const birthError = document.getElementById("birth-error");

    if (yearInput && monthInput && dayInput && birthError) {
        const inputs = [yearInput, monthInput, dayInput];

        // エラーメッセージ（validator.phpに合わせる）
        const MSG_EMPTY_BIRTH = "生年月日が入力されていません";
        const MSG_INVALID_BIRTH = "生年月日が正しくありません";
        const MSG_FUTURE_BIRTH = "生年月日が未来日です";

        // 入力途中の簡易チェック（リアルタイム）
        const partialCheck = () => {
            // 数字チェックなど軽いチェックのみ
            inputs.forEach(input => {
                if (input.value && !/^\d*$/.test(input.value)) {
                    input.value = input.value.replace(/\D/g, ""); // 数字以外は除去
                }
            });
            // エラーは出さない
        };

        // 入力完了時の本格チェック（フォーカス完了）
        const fullCheck = () => {
            const y = parseInt(yearInput.value, 10);
            const m = parseInt(monthInput.value, 10);
            const d = parseInt(dayInput.value, 10);
            let error = "";

            if (!y || !m || !d) {
                error = MSG_EMPTY_BIRTH;
            } else {
                const date = new Date(y, m - 1, d);
                if (date.getFullYear() !== y || date.getMonth() + 1 !== m || date.getDate() !== d) {
                    error = MSG_INVALID_BIRTH;
                } else {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (date >= today) error = MSG_FUTURE_BIRTH;
                }
            }

            birthError.textContent = error;
        };

        // リアルタイム入力チェック
        inputs.forEach(input => input.addEventListener("input", partialCheck));

        // フォーカス完了チェック（住所と同じ方式）
        let focusTimer = null;
        inputs.forEach(input => {
            input.addEventListener("blur", () => {
                if (focusTimer) clearTimeout(focusTimer);
                focusTimer = setTimeout(() => {
                    const active = document.activeElement;
                    if (!inputs.includes(active)) {
                        fullCheck();
                    }
                }, 10);
            });
        });
    }

    // 郵便番号チェック
    const postalInput = document.getElementById("postal_code");
    const postalError = document.getElementById("postal-error");

    if (postalInput && postalError) {
        // 入力中はエラーを出さない（必要であればここで簡単な形式チェックだけ可）
        postalInput.addEventListener("input", () => {
            postalError.textContent = "";
        });

        // フォーカスが外れた時にDBチェック
        postalInput.addEventListener("blur", async () => {
            const postal = (postalInput.value || "").trim();

            // 必須チェック
            if (!postal) {
                postalError.textContent = "郵便番号が入力されていません";
                return;
            }

            // 形式チェック
            if (!/^\d{3}-\d{4}$/.test(postal)) {
                postalError.textContent = "郵便番号が正しくありません";
                return;
            }

            // DBチェック
            const cleanZip = postal.replace("-", "");
            // console.log("DBチェック開始:", cleanZip);

            try {
                const res = await fetch(`ajax_check_postal.php?postal=${encodeURIComponent(postal)}`);
                const data = await res.json();
                // console.log("DBチェック結果:", data);

                if (!data.valid) {
                    postalError.textContent = data.message || "郵便番号が見つかりません";
                } else {
                    postalError.textContent = "";
                }
            } catch (e) {
                postalError.textContent = "郵便番号の確認中にエラーが発生しました";
                console.error(e);
            }
        });
    }

    const prefectureInput = document.getElementById("prefecture");
    const cityTownInput = document.getElementById("city_town");
    const buildingInput = document.getElementById("building");
    const addressError = document.getElementById("address-error");

    if (!prefectureInput || !cityTownInput || !buildingInput || !addressError) return;

    // -------------------------------
    // エラーメッセージ定数
    // -------------------------------
    const MSG_EMPTY_ADDR = "住所(都道府県もしくは市区町村・番地)が入力されていません";
    const MSG_PREF_LEN = "都道府県は10文字以内で入力してください";
    const MSG_CITY_BUILD_LEN = "市区町村・番地もしくは建物名は50文字以内で入力してください";
    const MSG_PREF_CHECK = "都道府県確認中にエラーが発生しました";

    // -------------------------------
    // DBチェック（都道府県のみ）
    // force=true で blur 時は必ず実行
    // -------------------------------
    let lastFetchedPref = "";
    const runDbCheck = async (pref, force = false) => {
        if (!pref || (!force && pref === lastFetchedPref)) return;

        const capturedPref = pref;
        try {
            const res = await fetch(`ajax_check_address.php?prefecture=${encodeURIComponent(capturedPref)}`);
            const data = await res.json();

            // 入力変更されていたら無視
            if ((prefectureInput.value || "").trim() !== capturedPref) return;

            if (!data.valid && !addressError.textContent) {
                addressError.textContent = data.message || MSG_PREF_CHECK;
            }

            lastFetchedPref = capturedPref;
        } catch {
            if (!addressError.textContent) addressError.textContent = MSG_PREF_CHECK;
        }
    };

    // -------------------------------
    // 住所チェック（必須・文字数・DBチェック）
    // -------------------------------
    const checkAddress = async (force = false) => {
        const pref = (prefectureInput.value || "").trim();
        const city = (cityTownInput.value || "").trim();
        const building = (buildingInput.value || "").trim();

        let error = "";

        // 必須チェック（都道府県・市区町村）
        if (!pref || !city) {
            error = MSG_EMPTY_ADDR;
        }
        // 文字数チェック
        else if (pref.length > 10) {
            error = MSG_PREF_LEN;
        } else if (city.length > 50 || building.length > 50) {
            error = MSG_CITY_BUILD_LEN;
        }

        // 最初の1つだけ表示
        addressError.textContent = error;

        // DBチェック（都道府県のみ）
        if (!error && pref.length > 0) {
            await runDbCheck(pref, force);
        }
    };

    // -------------------------------
    // 入力途中（リアルタイム）
    // -------------------------------
    let dbCheckTimer = null;
    const DB_DEBOUNCE_MS = 500;

    const inputs = [prefectureInput, cityTownInput, buildingInput];

    inputs.forEach(input => {
        input.addEventListener("input", () => {
            const pref = (prefectureInput.value || "").trim();
            const city = (cityTownInput.value || "").trim();
            const building = (buildingInput.value || "").trim();

            // 文字数チェックのみ
            if (pref.length > 10) {
                addressError.textContent = MSG_PREF_LEN;
            } else if (city.length > 50 || building.length > 50) {
                addressError.textContent = MSG_CITY_BUILD_LEN;
            } else {
                addressError.textContent = "";
            }

            // デバウンスで DB チェック
            if (dbCheckTimer) clearTimeout(dbCheckTimer);
            if (pref.length > 0 && city.length > 0) {
                dbCheckTimer = setTimeout(() => runDbCheck(pref), DB_DEBOUNCE_MS);
            }
        });
    });

    // -------------------------------
    // フォーカス完了（blur）
    // -------------------------------
    let focusTimer = null;

    inputs.forEach(input => {
        input.addEventListener("blur", () => {
            if (focusTimer) clearTimeout(focusTimer);
            focusTimer = setTimeout(() => {
                const active = document.activeElement;
                // 住所3項目すべてからフォーカスが外れた場合
                if (!inputs.includes(active)) {
                    checkAddress(true); // blur 時は force=true
                }
            }, 10);
        });
    });



    const telInput = document.getElementById("tel");
    const telError = document.getElementById("tel-error");

    if (telInput && telError) {
        // validator.php と同じエラーメッセージ
        const MSG_EMPTY_TEL = "電話番号が入力されていません";
        const MSG_INVALID_TEL = "電話番号は12~13桁で正しく入力してください";

        // 入力途中チェック（軽く数字・ハイフンのみ許可）
        const partialCheck = () => {
            telInput.value = telInput.value.replace(/[^\d-]/g, ""); // 数字とハイフン以外は削除
            telError.textContent = ""; // 入力途中はエラー表示しない
        };

        // 入力完了チェック（フォーカス完了）
        const fullCheck = () => {
            const val = (telInput.value || "").trim();
            let error = "";

            if (!val) {
                error = MSG_EMPTY_TEL;
            } else {
                const pattern = /^0\d{1,4}-\d{1,4}-\d{3,4}$/;
                if (!pattern.test(val) || val.length < 12 || val.length > 13) {
                    error = MSG_INVALID_TEL;
                }
            }

            telError.textContent = error;
        };

        // リアルタイム
        telInput.addEventListener("input", partialCheck);

        // フォーカス完了
        telInput.addEventListener("blur", fullCheck);
    }

    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("email-error");

    if (emailInput && emailError) {
        const MSG_EMPTY_EMAIL = "メールアドレスが入力されていません";
        const MSG_INVALID_EMAIL = "有効なメールアドレスを入力してください";

        // 入力途中チェック（前後スペース除去のみ）
        const partialCheck = () => {
            emailInput.value = emailInput.value.trim();
            emailError.textContent = ""; // 入力途中はエラー表示なし
        };

        // 入力完了チェック（フォーカスが外れたとき）
        const fullCheck = () => {
            const val = (emailInput.value || "").trim();
            let error = "";

            if (!val) {
                error = MSG_EMPTY_EMAIL;
            } else {
                const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!pattern.test(val)) error = MSG_INVALID_EMAIL;
            }

            emailError.textContent = error;
        };

        // リアルタイム
        emailInput.addEventListener("input", partialCheck);

        // フォーカス完了
        emailInput.addEventListener("blur", fullCheck);
    }

    // 本人確認書類(表)
    const document1Input = document.getElementById("document1");
    const document1Error = document.getElementById("document1-error");

    if (document1Input && document1Error) {
        document1Input.addEventListener("change", () => {
            const file = document1Input.files[0];
            let error = "";
            if (file) {
                const validTypes = ["image/png", "image/jpeg"];
                if (!validTypes.includes(file.type)) {
                    error = "ファイル形式は PNG または JPEG のみ許可されています";
                }
            }
            document1Error.textContent = error;
        });
    }

    const document2Input = document.getElementById("document2");
    const document2Error = document.getElementById("document2-error");

    if (document2Input && document2Error) {
        document2Input.addEventListener("change", () => {
            const file = document2Input.files[0];
            let error = "";
            if (file) {
                const validTypes = ["image/png", "image/jpeg"];
                if (!validTypes.includes(file.type)) {
                    error = "ファイル形式は PNG または JPEG のみ許可されています";
                }
            }
            document2Error.textContent = error;
        });
    }


    const isInputPage = window.location.pathname.includes("input.php");

    if (isInputPage) {
        const passwordInput = document.getElementById("password");
        const passwordConfirmInput = document.getElementById("password_confirm");
        const passwordError = document.getElementById("password-error");
        const passwordConfirmError = document.getElementById("password-confirm-error");

        if (passwordInput && passwordConfirmInput && passwordError && passwordConfirmError) {
            // 入力完了フラグ
            const touched = { password: false, confirm: false };

            // フォーカスチェック
            passwordInput.addEventListener("focus", () => touched.password = true);
            passwordConfirmInput.addEventListener("focus", () => touched.confirm = true);

            passwordInput.addEventListener("blur", validatePassword);
            passwordConfirmInput.addEventListener("blur", validatePassword);

            function validatePassword() {
                const password = (passwordInput.value || "").trim();
                const confirm = (passwordConfirmInput.value || "").trim();
                let error1 = "";
                let error2 = "";

                // 入力完了後のみ必須チェック／一致チェック
                if (touched.password || touched.confirm) {
                    // パスワード必須
                    if (!password) {
                        error1 = "パスワードが入力されていません";
                    } else if (password.length > 5) {
                        error1 = "パスワードは5文字以内で入力してください";
                    }

                    // 確認パスワード
                    if (confirm) {
                        if (password !== confirm) {
                            error2 = "パスワードと一致しません";
                        }
                    }
                }

                // validator.php に合わせて先に検知したエラーだけ表示
                passwordError.textContent = error1;
                passwordConfirmError.textContent = error2;
            }
        }
    }
    // });



};
