document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-auto-submit]').forEach(input => {
    input.addEventListener('change', () => input.form.requestSubmit());
  });
  for (const form of document.querySelectorAll('[data-pairs]')) {
    const pairs = JSON.parse(form.dataset.pairs);
    const make = form.elements.namedItem('make');
    const model = form.elements.namedItem('model');
    const sync = reset => {
      const allowed = pairs[make.value] || [];
      for (const option of model.options) {
        option.hidden = Boolean(make.value && option.value && !allowed.includes(option.value));
      }
      if (reset && model.selectedOptions[0]?.hidden) model.value = '';
    };
    sync(false);
    make.addEventListener('change', () => sync(true));
    window.addEventListener('pageshow', () => sync(false));
  }
  document.addEventListener('keydown', event => {
    const open = document.querySelector('.mobile-nav[open]');
    if (open && event.key === 'Escape') {
      open.open = false;
      open.querySelector('summary').focus();
    }
  });
  document.querySelectorAll('.radiator-photo img,.product-image img,.acc-detail-photo img').forEach(img => {
    const fallback = () => {
      const label = document.createElement('span');
      label.className = 'image-note';
      label.textContent = 'Image unavailable';
      img.replaceWith(label);
    };
    img.addEventListener('error', fallback, {once:true});
    if (img.complete && !img.naturalWidth) fallback();
  });
});
