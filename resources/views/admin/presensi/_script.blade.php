@push('scripts')
<script>
(function () {
  const status = document.getElementById('status');
  const blok = document.getElementById('blok-waktu');
  const masuk = document.getElementById('waktu_check_in');
  const keluar = document.getElementById('waktu_check_out');
  if (!status || !blok || !masuk || !keluar) return;

  function perbarui(bersihkan) {
    const hadir = status.value === 'hadir';
    blok.hidden = !hadir;
    if (!hadir && bersihkan) { masuk.value = ''; keluar.value = ''; }
  }
  function batasKeluar() { keluar.min = masuk.value || ''; }

  status.addEventListener('change', function () { perbarui(true); });
  masuk.addEventListener('change', batasKeluar);
  perbarui(false);
  batasKeluar();
})();
</script>
@endpush