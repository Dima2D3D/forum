<?php require_once __DIR__.'/../config.php'; ?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <img src="/banners/IMG_20260727_215431_065.jpg" alt="GREFFRLEND" class="site-icon">
      <div><strong>GREFFRLEND</strong><span>Minecraft Community</span></div>
    </div>
    <nav class="footer-links">
      <a href="/rules.php">Правила сообщества</a>
      <a href="/offer.php">Оферта</a>
      <a href="/privacy.php">Конфиденциальность</a>
      <a href="mailto:admins@greffrlend.fun">Контакты</a>
    </nav>
  </div>
  <div class="copyright">© 2025 — 2026 GREFFRLEND. Все права защищены.</div>
</footer>
<script>
function copyIP(){navigator.clipboard?.writeText('play.greffrlend.fun').then(()=>alert('IP скопирован')).catch(()=>prompt('Скопируйте IP','play.greffrlend.fun'));}
function insertEmoji(v){const t=document.querySelector('textarea[name="content"],textarea[name="message"]');if(!t)return;t.value+=v;t.focus();}
</script>
</body></html>
