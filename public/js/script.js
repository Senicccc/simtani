// Sidebar drawer (mobile) — satu-satunya JS yang masih dibutuhkan.
// Logika switch-halaman lama sudah tidak perlu: sekarang tiap halaman
// adalah route Laravel beneran (Blade), bukan div yang di-toggle JS.

const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
const btnMenu = document.getElementById('btn-menu');

function openSidebar(){
  sidebar.classList.add('open');
  backdrop.classList.add('open');
  document.body.classList.add('menu-open');
  sidebar.inert = false;
  sidebar.setAttribute('aria-hidden', 'false');
  btnMenu.setAttribute('aria-expanded', 'true');
  btnMenu.setAttribute('aria-label', 'Tutup menu');
}
function closeSidebar(restoreFocus = false){
  sidebar.classList.remove('open');
  backdrop.classList.remove('open');
  document.body.classList.remove('menu-open');
  const isMobile = window.matchMedia('(max-width: 768px)').matches;
  sidebar.inert = isMobile;
  sidebar.setAttribute('aria-hidden', String(isMobile));
  btnMenu.setAttribute('aria-expanded', 'false');
  btnMenu.setAttribute('aria-label', 'Buka menu');
  if (restoreFocus) btnMenu.focus();
}

if (btnMenu && sidebar && backdrop) {
  closeSidebar();
  btnMenu.addEventListener('click', () => {
    if (sidebar.classList.contains('open')) {
      closeSidebar();
      return;
    }

    openSidebar();
  });
  backdrop.addEventListener('click', () => closeSidebar());
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar.classList.contains('open')) {
      closeSidebar(true);
    }
  });
  window.matchMedia('(max-width: 768px)').addEventListener('change', () => closeSidebar());
}

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => closeSidebar());
});
