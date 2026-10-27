
/* ===== いいね機能 (like.js) ===== */

document.addEventListener('click', async (event) => {
    // ① いいねボタンのクリックを検知
    const button = event.target.closest('.js-like-btn');

    if (!button) {
        return;
    }

    // ② 投稿IDとCSRFトークンを取得
    const postId = button.dataset.postId;
    const csrfToken =
        document.querySelector('input[name="csrf_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content;

    if (!postId) {
        console.error('いいね対象の投稿IDがありません。');
        return;
    }

    if (!csrfToken) {
        alert('セキュリティ情報を取得できません。ページを再読み込みしてください。');
        return;
    }

    // ③ 連続クリックによる二重送信を防ぐ
    if (button.disabled) {
        return;
    }

    button.disabled = true;

    try {
        // ④ like.php にPOST送信
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('csrf_token', csrfToken);

        const response = await fetch('like.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        const result = await response.json();

        // ⑤ 未ログインならログイン画面へ
        if (response.status === 401 && result.login) {
            window.location.href = result.login;
            return;
        }

        // ⑥ エラーが返された場合
        if (!response.ok || result.error) {
            alert(result.error || 'いいねの処理に失敗しました。');
            return;
        }

        // ⑦ いいね状態と件数を更新
        button.classList.toggle('is-liked', result.active);

        const icon = button.querySelector('.post__action-icon');
        if (icon) {
            icon.textContent = result.active ? '♥' : '♡';
        }

        const countElement = button.querySelector('.js-like-count');
        if (countElement) {
            countElement.textContent = result.count;
        }

        // アクセシビリティ向けの状態も更新
        button.setAttribute('aria-pressed', String(result.active));

    } catch (error) {
        console.error('いいね処理エラー:', error);
        alert('通信に失敗しました。もう一度お試しください。');
    } finally {
        // ⑧ 成功・失敗にかかわらず再び押せるようにする
        button.disabled = false;
    }
});
