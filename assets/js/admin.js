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
