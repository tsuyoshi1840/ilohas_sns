document.addEventListener('DOMContentLoaded', () => {

  // ==========================================
  // ① 復活した切り替えボタンでダークモード制御
  // ==========================================
  const themeToggle = document.querySelector('#theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', nextTheme);
      try { localStorage.setItem('theme', nextTheme); } catch (e) {}  // 選んだモードを保存
    });
  }
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('postForm');
  const textarea = document.getElementById('postContent');
  const counter = document.getElementById('charCounter');
  const submitBtn = document.getElementById('submitBtn');
  const maxLength = 140;

  if (!form || !textarea || !counter || !submitBtn) return;

  // 1. 文字数カウント
  textarea.addEventListener('input', () => {
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    counter.textContent = remaining;

    if (remaining < 0) {
      counter.style.color = '#e0245e';
    } else {
      counter.style.color = '';
    }
  });

  // 2. 連打（二重送信）防止
  form.addEventListener('submit', (e) => {
    if (submitBtn.disabled) {
      e.preventDefault();
      return;
    }
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
  });
});
// 
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('postForm');
  const textarea = document.getElementById('postContent');
  const counter = document.getElementById('charCounter');
  const submitBtn = document.getElementById('submitBtn');
  const maxLength = 140;

  if (!form || !textarea || !counter || !submitBtn) return;

  // 1. 文字数カウント
  textarea.addEventListener('input', () => {
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    counter.textContent = remaining;

    if (remaining < 0) {
      counter.style.color = '#e0245e';
    } else {
      counter.style.color = '';
    }
  });

  // 2. 連打（二重送信）防止
  form.addEventListener('submit', (e) => {
    if (submitBtn.disabled) {
      e.preventDefault();
      return;
    }
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
  });
});
  // ==========================================
  // ② スクロールでヘッダー（見出し）を縮める処理
  // ==========================================
  const pageHeader = document.querySelector('.page-header');
  if (pageHeader) {
    window.addEventListener('scroll', () => {
      pageHeader.classList.toggle('is-small', window.scrollY > 50);
    });
  }

  // ==========================================
  // ③ リアルタイム投稿追加 ＆ DB保存（fetch連携）
  // ==========================================
  const postTextarea = document.querySelector('#js-post-textarea');
  const postSubmit = document.querySelector('#js-post-submit');
  const timeline = document.querySelector('#js-timeline');

  if (postTextarea && postSubmit && timeline) {
    postSubmit.addEventListener('click', async (e) => {
      e.preventDefault(); // 画面リロードをキャンセル
      const text = postTextarea.value.trim();
      if (text === "") return;

      // 1. 裏側で `post.php` にデータを送信してデータベースに保存する
      try {
        const response = await fetch('post.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'body=' + encodeURIComponent(text)
        });

        // ログインしていない等の理由でリダイレクトされた場合
        if (response.redirected) {
          window.location.href = response.url;
          return;
        }
      } catch (error) {
        console.error('保存に失敗しました', error);
        alert('投稿に失敗しました。');
        return;
      }

      // 2. 成功したら、今まで通り画面上にも即座に追加する
      const newPost = document.createElement('article');
      newPost.className = 'post';
      newPost.setAttribute('data-post-id', Date.now());
      newPost.innerHTML = `
        <img src="img/default-icon.png" alt="" class="post__icon">
        <div class="post__content">
          <div class="post__header">
            <a href="profile.php" class="post__user-name">あなた</a>
            <span class="post__user-id">@my_account</span>
            <time class="post__time">今</time>
          </div>
          <p class="post__body">${text}</p>
          <div class="post__actions">
            <button type="button" class="post__action post__action--like js-like-btn">
              <span class="post__action-icon">♡</span>
              <span class="post__action-count js-like-count">0</span>
            </button>
            <button type="button" class="post__action post__action--delete js-delete-btn">
              削除
            </button>
          </div>
        </div>
      `;
      timeline.insertBefore(newPost, timeline.firstChild);
      postTextarea.value = "";
      const counter = document.querySelector('#js-post-counter');
      if (counter) counter.textContent = "140";
    });
  }

  // ==========================================
  // ④ スマホ用：下部メニューへのボタン自動引っ越し処理
  // ==========================================
  const sidebarMenu = document.querySelector('.sidebar__menu');
  const pcThemeToggle = document.querySelector('#theme-toggle');

  if (sidebarMenu && pcThemeToggle) {
    if (window.innerWidth <= 500) {
      const newLi = document.createElement('li');
      newLi.className = 'sidebar__item js-moved-theme-btn';
      newLi.appendChild(pcThemeToggle);
      sidebarMenu.appendChild(newLi);
    }

    window.addEventListener('resize', () => {
      if (window.innerWidth <= 500) {
        if (!sidebarMenu.contains(pcThemeToggle)) {
          const newLi = document.createElement('li');
          newLi.className = 'sidebar__item js-moved-theme-btn';
          newLi.appendChild(pcThemeToggle);
          sidebarMenu.appendChild(newLi);
        }
      } else {
        const logo = document.querySelector('.sidebar__logo');
        const movedLi = document.querySelector('.js-moved-theme-btn');
        if (logo && movedLi) {
          logo.parentNode.insertBefore(pcThemeToggle, logo.nextSibling);
          movedLi.remove();
        }
      }
    });
  }
});

// 全体お知らせ機能（一度消したものは非表示 ＆ 新着分だけカルーセル表示）
document.addEventListener('DOMContentLoaded', () => {
  const banner = document.getElementById('noticeBanner');
  if (!banner) return;

  const track = document.getElementById('noticeTrack');
  const items = Array.from(track.querySelectorAll('.notice-item'));
  const STORAGE_KEY = 'ilohas_notice_closed_id';
  const closedId = Number(localStorage.getItem(STORAGE_KEY) || 0);

  // 1. 過去に「×」で消したID以下の古いお知らせは非表示（削除）にする
  items.forEach(item => {
    const noticeId = Number(item.getAttribute('data-notice-id'));
    if (noticeId <= closedId) {
      item.remove();
    }
  });

  // 2. 残った「新着のお知らせ」がなければ、バナー自体を非表示にする
  const remainingItems = track.querySelectorAll('.notice-item');
  if (remainingItems.length === 0) {
    banner.style.display = 'none';
    return;
  }

  const dotsContainer = document.getElementById('noticeDots');
  const closeBtn = document.getElementById('noticeCloseBtn');
  const prevBtn = document.getElementById('noticePrevBtn');
  const nextBtn = document.getElementById('noticeNextBtn');

  let currentIndex = 0;
  const totalItems = remainingItems.length;

  // 3. ×ボタンで閉じたとき、今表示されている中で一番新しいIDを保存する
  closeBtn.addEventListener('click', () => {
    banner.style.display = 'none';
    const latestVisibleId = remainingItems[0].getAttribute('data-notice-id');
    localStorage.setItem(STORAGE_KEY, latestVisibleId);
  });

  if (totalItems <= 1) {
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    return;
  }

  // 4. ドットインジケーターの自動生成（残った新着分に対して）
  remainingItems.forEach((_, index) => {
    const dot = document.createElement('div');
    dot.classList.add('notice-dot');
    if (index === 0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(index));
    dotsContainer.appendChild(dot);
  });

  const dots = dotsContainer.querySelectorAll('.notice-dot');

  function goToSlide(index) {
    currentIndex = index;
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
    });
  }

  // 5. 矢印ボタンのクリックイベント
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentIndex > 0) {
        goToSlide(currentIndex - 1);
      } else {
        goToSlide(totalItems - 1);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (currentIndex < totalItems - 1) {
        goToSlide(currentIndex + 1);
      } else {
        goToSlide(0);
      }
    });
  }

  // 6. スマホのスワイプ（タッチ）対応
  let startX = 0;
  let currentX = 0;
  let isSwiping = false;

  track.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
    isSwiping = true;
  });

  track.addEventListener('touchmove', (e) => {
    if (!isSwiping) return;
    currentX = e.touches[0].clientX;
  });

  track.addEventListener('touchend', () => {
    if (!isSwiping) return;
    const diffX = startX - currentX;
    
    if (Math.abs(diffX) > 50) {
      if (diffX > 0 && currentIndex < totalItems - 1) {
        goToSlide(currentIndex + 1);
      } else if (diffX < 0 && currentIndex > 0) {
        goToSlide(currentIndex - 1);
      }
    }
    isSwiping = false;
  });
});