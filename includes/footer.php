<?php
// ===== 共通フッター =====
// 各ページの最後で require 'includes/footer.php'; する
?>
  </main>
<?php if (!($isAuthPage ?? false)): ?>
</div><!-- /.layout -->
<?php endif; ?>

<script src="js/main.js"></script>
</body>
</html>
