<?php
// ===== いたずら返しページ =====
// post.php で <script が検出されたらここに飛んでくる。
// 猫の窓の×を押すと窓が増えていき、一定数で「ダメにゃ」と出て終わる。
//
// 用意するファイル(なくても動く):
//   img/neko.png … 猫の画像(なければ 🐱 の絵文字で代用)
//   uploads/music.mp3 … BGM(ページを開いたら再生。ブラウザに止められたら最初の×で再生開始。ループ)
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>にゃーん</title>
<style>
  /* このページ専用。style.css には影響しない */
  html, body {
    margin: 0;
    height: 100%;
    overflow: hidden;
    background: #1d9bf0;
    font-family: system-ui, sans-serif;
  }

  /* 猫の窓 */
  .neko-win {
    position: fixed;
    width: 280px;
    background: #fff;
    border: 1px solid #999;
    border-radius: 6px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, .35);
    overflow: hidden;
    animation: neko-pop .18s ease-out;
  }
  @keyframes neko-pop {
    from { transform: scale(.6); opacity: 0; }
    to   { transform: scale(1);  opacity: 1; }
  }

  /* タブ・タイトルバー風 */
  .neko-win__bar {
    display: flex;
    align-items: center;
    height: 32px;
    background: #dee1e6;
    padding-left: 8px;
    user-select: none;
  }
  .neko-win__tab {
    flex: 1;
    font-size: 13px;
    background: #fff;
    border-radius: 6px 6px 0 0;
    padding: 6px 10px;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .neko-win__btn {
    width: 40px;
    height: 32px;
    border: none;
    background: transparent;
    font-size: 16px;
    cursor: pointer;
  }
  .neko-win__btn--close:hover {
    background: #e81123;
    color: #fff;
  }

  /* 中身 */
  .neko-win__body {
    padding: 16px;
    text-align: center;
  }
  .neko-win__img {
    width: 100%;
    max-height: 180px;
    object-fit: contain;
  }
  .neko-win__emoji {
    font-size: 96px;
    line-height: 1.2;
  }
  .neko-win__text {
    margin: 8px 0 0;
    font-weight: bold;
  }

  /* 最後の画面 */
  .neko-end {
    position: fixed;
    inset: 0;
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fff;
    text-align: center;
  }
  .neko-end.is-show {
    display: flex;
  }
  .neko-end__emoji {
    font-size: 140px;
  }
  .neko-end__title {
    font-size: 48px;
    margin: 0 0 24px;
  }
  .neko-end__back {
    padding: 12px 32px;
    border: none;
    border-radius: 999px;
    background: #1d9bf0;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
  }
</style>
</head>
<body>

<!-- 窓の見本(JSがコピーして増やす) -->
<template id="neko-template">
  <div class="neko-win">
    <div class="neko-win__bar">
      <span class="neko-win__tab">🐱 警告 - にゃーん</span>
      <button type="button" class="neko-win__btn">－</button>
      <button type="button" class="neko-win__btn">□</button>
      <button type="button" class="neko-win__btn neko-win__btn--close js-neko-close">✕</button>
    </div>
    <div class="neko-win__body">
      <img src="img/neko.png" alt="" class="neko-win__img js-neko-img">
      <p class="neko-win__text">今の入力は書けないにゃ</p>
    </div>
  </div>
</template>

<!-- 最後の画面 -->
<div class="neko-end" id="neko-end">
  <div class="neko-end__emoji">😾</div>
  <h1 class="neko-end__title">いたずらはダメにゃ</h1>
  <button type="button" class="neko-end__back" id="neko-back">タイムラインに戻る</button>
</div>

<audio id="neko-se" src="uploads/music.mp3" preload="auto" loop></audio>

<script>
(() => {
  const MAX_WINDOWS = 16;   // この数を超えたら終わり
  const TIME_LIMIT  = 30000; // 念のため30秒で強制終了

  const template = document.getElementById('neko-template');
  const endScreen = document.getElementById('neko-end');
  const se = document.getElementById('neko-se');
  let finished = false;

  // 窓を1つ作る
  function spawn(x, y) {
    const win = template.content.firstElementChild.cloneNode(true);

    // 画像がなければ絵文字に差し替え
    const img = win.querySelector('.js-neko-img');
    img.addEventListener('error', () => {
      const emoji = document.createElement('div');
      emoji.className = 'neko-win__emoji';
      emoji.textContent = '🐱';
      img.replaceWith(emoji);
    });

    // 画面からはみ出さない位置に置く
    const maxX = Math.max(0, window.innerWidth - 290);
    const maxY = Math.max(0, window.innerHeight - 280);
    win.style.left = Math.min(Math.max(0, x), maxX) + 'px';
    win.style.top  = Math.min(Math.max(0, y), maxY) + 'px';

    win.querySelector('.js-neko-close').addEventListener('click', () => onClose(win));
    document.body.appendChild(win);
  }

  // ×を押したとき:閉じずに2つに増える
  function onClose(win) {
    if (finished) return;

    // 最初の1回だけ全画面にする(Escでいつでも解除できる)
    if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen().catch(() => {});
    }

    playSE();

    const rect = win.getBoundingClientRect();
    win.remove();

    for (let i = 0; i < 2; i++) {
      spawn(
        rect.left + (Math.random() * 400 - 200),
        rect.top  + (Math.random() * 300 - 150)
      );
    }

    if (document.querySelectorAll('.neko-win').length > MAX_WINDOWS) {
      setTimeout(finish, 600);
    }
  }

  // BGMを再生(すでに流れていれば何もしない。最初からやり直さない)
  function playSE() {
    try {
      if (se.paused) {
        se.play().catch(() => {}); // 止められても・ファイルがなくてもエラーにしない
      }
    } catch (e) {}
  }

  // 全部消して「ダメにゃ」
  function finish() {
    if (finished) return;
    finished = true;
    document.querySelectorAll('.neko-win').forEach(w => w.remove());
    endScreen.classList.add('is-show');
    playSE();
  }

  // タイムラインに戻る
  document.getElementById('neko-back').addEventListener('click', () => {
    se.pause();
    if (document.fullscreenElement) {
      document.exitFullscreen().catch(() => {});
    }
    location.href = 'index.php';
  });

  // 最初の1つを真ん中に
  spawn(window.innerWidth / 2 - 140, window.innerHeight / 2 - 140);

  // ページ側の音量は最大に(PC本体の音量はブラウザからは変えられない)
  se.volume = 1.0;

  // 開いた瞬間にBGMを試す(投稿ボタンを押した直後なので、Chromeなら鳴ることが多い)
  playSE();

  // 安全装置
  setTimeout(finish, TIME_LIMIT);
})();
</script>
</body>
</html>
