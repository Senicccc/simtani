// Sidebar drawer (mobile)
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
const btnMenu = document.getElementById('btn-menu');

function openSidebar(){
  if (!sidebar) return;
  sidebar.classList.add('open');
  backdrop?.classList.add('open');
  document.body.classList.add('no-scroll');
  btnMenu?.setAttribute('aria-expanded', 'true');
}
function closeSidebar(){
  if (!sidebar) return;
  sidebar.classList.remove('open');
  backdrop?.classList.remove('open');
  document.body.classList.remove('no-scroll');
  btnMenu?.setAttribute('aria-expanded', 'false');
}

if (btnMenu) btnMenu.addEventListener('click', openSidebar);
if (backdrop) backdrop.addEventListener('click', closeSidebar);

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', closeSidebar);
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
window.matchMedia('(min-width:769px)').addEventListener('change', e => { if (e.matches) closeSidebar(); });

// Pencarian di dalam <select>. Opsi dibangun ulang (bukan disembunyikan)
// supaya jalan di Safari iOS. Opsi terpilih dan placeholder selalu tampil.
function pasangPencarianSelect(inputId, selectId) {
  const input = document.getElementById(inputId);
  const select = document.getElementById(selectId);
  if (!input || !select) return;
  const semua = Array.from(select.options).map(function (o) { return { value: o.value, text: o.text }; });
  input.addEventListener('input', function () {
    const kata = input.value.trim().toLocaleLowerCase();
    const terpilih = select.value;
    select.innerHTML = '';
    semua.forEach(function (o) {
      if (o.value === '' || o.value === terpilih || o.text.toLocaleLowerCase().includes(kata)) {
        const opt = new Option(o.text, o.value);
        opt.selected = o.value === terpilih;
        select.add(opt);
      }
    });
  });
}

// Cegah kirim ganda: matikan tombol kirim setelah form dikirim.
document.addEventListener('submit', function (e) {
  if (e.defaultPrevented) return; // mis. confirm() dibatalkan
  const tombol = e.target.querySelectorAll('button[type="submit"]');
  setTimeout(function () { tombol.forEach(function (b) { b.disabled = true; }); }, 0);
});
window.addEventListener('pageshow', function (e) {
  if (!e.persisted) return;
  document.querySelectorAll('button[type="submit"]:disabled').forEach(function (b) { b.disabled = false; });
});
