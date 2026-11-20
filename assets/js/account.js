document.querySelectorAll('.account-page input[type="password"]').forEach((input) => {
  const wrapper = document.createElement('div');
  wrapper.className = 'account-password';
  input.before(wrapper);
  wrapper.append(input);

  const toggle = document.createElement('button');
  toggle.className = 'account-password-toggle';
  toggle.type = 'button';
  toggle.textContent = 'Show';
  toggle.setAttribute('aria-label', 'Show password');
  toggle.setAttribute('aria-controls', input.id);
  toggle.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
  wrapper.append(toggle);
});
