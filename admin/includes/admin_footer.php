  </main>

  <!-- Toast Notification Container -->
  <div class="toast-container"></div>

  <!-- Safe POST actions: <a data-post-name="delete" data-post-value="5" data-confirm="Sure?"> -->
  <script>
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-post-name]') : null;
    if (!el) return;
    e.preventDefault();
    var msg = el.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) return;
    var meta = document.querySelector('meta[name="csrf-token"]');
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = window.location.pathname;
    [['csrf_token', meta ? meta.content : ''], [el.getAttribute('data-post-name'), el.getAttribute('data-post-value') || '1']].forEach(function (p) {
      var i = document.createElement('input'); i.type = 'hidden'; i.name = p[0]; i.value = p[1]; f.appendChild(i);
    });
    document.body.appendChild(f);
    f.submit();
  });
  </script>

  <!-- JavaScript Scripts -->
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/admin.js"></script>
</body>
</html>
