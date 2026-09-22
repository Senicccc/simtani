// Sidebar drawer (mobile) — satu-satunya JS yang masih dibutuhkan.
// Logika switch-halaman lama sudah tidak perlu: sekarang tiap halaman
// adalah route Laravel beneran (Blade), bukan div yang di-toggle JS.

const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
const btnMenu = document.getElementById('btn-menu');

function openSidebar(){
  sidebar.classList.add('open');
  backdrop.classList.add('open');
}
function closeSidebar(){
  sidebar.classList.remove('open');
  backdrop.classList.remove('open');
}

if (btnMenu) btnMenu.addEventListener('click', openSidebar);
if (backdrop) backdrop.addEventListener('click', closeSidebar);

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', closeSidebar);
});
