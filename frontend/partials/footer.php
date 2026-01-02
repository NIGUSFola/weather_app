<?php
// frontend/partials/footer.php
?>
<footer class="footer">
  <small>
    &copy; <?= date("Y"); ?> 
    <span data-i18n="footer_brand">Weather Aggregator Ethiopia</span>
  </small>
</footer>

<script>
// ✅ Ensure footer respects theme toggle
window.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('theme') === 'dark') {
    document.querySelector('.footer').classList.add('dark-theme');
  }
});
</script>
