
document.addEventListener('click', async (event) => {
    // リポストボタンを取得
    const button = event.target.closest('.js-repost-btn');
    if (!button) return;

    // 投稿IDとCSRFトークンを取得
    const postId = button.dataset.postId;
    const csrfToken =
        document.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content;

    // 必要な情報があるか確認
    if (!postId) {
        console.error('投稿IDが取得できません。');
        return;
    }

    if (!csrfToken) {
        alert('CSRFトークンが見つかりません。');
        return;
    }

    // 二重送信を防ぐ
    if (button.disabled) return;
    button.disabled = true;

    try {
        // PHPに送信するデータを作成
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('csrf_token', csrfToken);

        // repost.phpに送信
        const response = await fetch('repost.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        // 未ログインならログイン画面へ移動
        if (response.status === 401 && result.login) {
            window.location.href = result.login;
            return;
        }

        // エラー処理
        if (!response.ok || result.error) {
            alert(result.error || 'リポストに失敗しました。');
            return;
        }

        // リポスト状態を更新
        button.classList.toggle('is-reposted', result.active);
        button.setAttribute('aria-pressed', String(result.active));

        // リポスト件数を更新
        const countElement =
            button.querySelector('.js-repost-count');

        if (countElement) {
            countElement.textContent = result.count;
        }

    } catch (error) {
        console.error('リポスト処理エラー:', error);
        alert('通信に失敗しました。もう一度お試しください。');

    } finally {
        button.disabled = false;
    }
});
