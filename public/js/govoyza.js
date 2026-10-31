// Progressive enhancement only — every form works without JS.
document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-pw-toggle]');
  if (!t) return;
  const input = document.getElementById(t.dataset.pwToggle);
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  t.textContent = show ? 'Hide' : 'Show';
  t.setAttribute('aria-pressed', String(show));
});

document.addEventListener('change', (e) => {
  const input = e.target.closest('[data-file-input]');
  if (!input) return;
  const label = document.getElementById(input.dataset.fileInput);
  const f = input.files[0];
  label.textContent = f ? `${f.name} · ${(f.size / 1024 / 1024).toFixed(2)} MB` : 'PDF, JPG or PNG · max 5 MB';
});

// Prevent double submits
document.addEventListener('submit', (e) => {
  const btn = e.target.querySelector('button[type=submit]');
  if (btn) setTimeout(() => { btn.disabled = true; }, 0);
});
