// ── Popover de changement de rôle ──────────────────────

document.querySelectorAll('[data-role-trigger]').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const cell = btn.closest('[data-role-cell]');
    const isOpen = cell.classList.contains('is-open');
    document.querySelectorAll('[data-role-cell].is-open').forEach(c => c.classList.remove('is-open'));
    if (!isOpen) cell.classList.add('is-open');
  });
});

document.querySelectorAll('[data-role-pick]').forEach(pill => {
  pill.addEventListener('click', () => {
    const cell = pill.closest('[data-role-cell]');
    const currentRole = cell.dataset.currentRole;
    const pickedRole = pill.dataset.rolePick;

    cell.querySelectorAll('[data-role-pick]').forEach(p => p.classList.remove('is-selected'));
    pill.classList.add('is-selected');

    cell.querySelector('.new-role-input').value = pickedRole;
    cell.querySelector('.role-apply').classList.toggle('is-visible', pickedRole !== currentRole);
  });
});

document.addEventListener('click', e => {
  if (!e.target.closest('[data-role-cell]')) {
    document.querySelectorAll('[data-role-cell].is-open').forEach(c => c.classList.remove('is-open'));
  }
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('[data-role-cell].is-open').forEach(c => c.classList.remove('is-open'));
  }
});

// ── Toggle bloquer / débloquer (AJAX) ─────────────────

function showFlash(msg, type) {
  const existing = document.querySelector('.staff-flash');
  if (existing) existing.remove();
  const flash = document.createElement('div');
  flash.className = 'staff-flash staff-flash--' + type;
  flash.textContent = msg;
  const inner = document.querySelector('.staff-inner');
  const header = inner.querySelector('.staff-header');
  header.insertAdjacentElement('afterend', flash);
  requestAnimationFrame(() => flash.classList.add('is-visible'));
  setTimeout(() => {
    flash.classList.remove('is-visible');
    setTimeout(() => flash.remove(), 300);
  }, 4000);
}

document.querySelectorAll('[data-block-toggle]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const userId = parseInt(btn.dataset.userId);
    const currentBlocked = parseInt(btn.dataset.blocked);
    const newBlocked = currentBlocked ? 0 : 1;

    // Mise à jour optimiste
    btn.dataset.blocked = newBlocked;
    btn.classList.toggle('is-blocked', newBlocked === 1);
    const label = btn.querySelector('.block-toggle-label');
    if (label) label.textContent = newBlocked ? 'Bloqué' : 'Actif';
    btn.setAttribute('aria-label', (newBlocked ? 'Débloquer' : 'Bloquer') + ' ' + btn.getAttribute('aria-label').replace(/^(Débloquer|Bloquer) /, ''));

    try {
      const formData = new FormData();
      formData.append('action', 'toggle_block');
      formData.append('user_id', userId);
      formData.append('blocked', newBlocked);

      const resp = await fetch('admin.php', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!data.success) {
        revertToggle(btn, currentBlocked, label);
        showFlash(data.error || 'Erreur lors du blocage.', 'error');
      }
    } catch (e) {
      revertToggle(btn, currentBlocked, label);
      showFlash('Erreur réseau.', 'error');
    }
  });
});

function revertToggle(btn, originalBlocked, label) {
  btn.dataset.blocked = originalBlocked;
  btn.classList.toggle('is-blocked', originalBlocked === 1);
  if (label) label.textContent = originalBlocked ? 'Bloqué' : 'Actif';
  btn.setAttribute('aria-label', (originalBlocked ? 'Débloquer' : 'Bloquer') + ' ' + btn.getAttribute('aria-label').replace(/^(Débloquer|Bloquer) /, ''));
}
