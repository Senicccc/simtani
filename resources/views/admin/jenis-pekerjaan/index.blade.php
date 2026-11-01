@extends('layouts.backend')
@section('title', 'Jenis Pekerjaan')

@section('content')
<div class="page-head">
  <div>
    <h2>Jenis Pekerjaan</h2>
    <p>{{ $jenisPekerjaan->count() }} jenis pekerjaan.</p>
  </div>
  <a href="{{ route('admin.jenis-pekerjaan.create') }}" class="btn-outline">+ Tambah Jenis Pekerjaan</a>
</div>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Satuan</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($jenisPekerjaan as $item)
          @php
            $pemakaian = collect([
              $item->jadwal_count ? $item->jadwal_count.' jadwal' : null,
              $item->kompetensi_count ? $item->kompetensi_count.' kompetensi anggota' : null,
            ])->filter()->implode(' dan ');
          @endphp
          <tr>
            <td>
              <span class="cell-name">{{ $item->nama_pekerjaan }}</span>
              @if ($item->deskripsi)
                <span class="cell-sub">{{ \Illuminate\Support\Str::limit($item->deskripsi, 80) }}</span>
              @endif
              @if ($pemakaian !== '')
                <span class="cell-sub">Dipakai di {{ $pemakaian }}</span>
              @endif
            </td>
            <td>{{ $item->satuan }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.jenis-pekerjaan.edit', $item) }}" class="table-action">Edit</a>
                @if ($pemakaian === '')
                  <form method="POST" action="{{ route('admin.jenis-pekerjaan.destroy', $item) }}"
                        onsubmit="return confirm({{ \Illuminate\Support\Js::from('Hapus jenis pekerjaan "'.$item->nama_pekerjaan.'"? Tindakan ini tidak dapat dibatalkan.') }})">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="table-action danger">Hapus</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3">
              <div class="empty-state">Belum ada jenis pekerjaan. Klik Tambah Jenis Pekerjaan untuk mulai.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<p class="section-note">
  Jenis pekerjaan yang masih dipakai pada jadwal tidak dapat dihapus. Jika masih ada kompetensi anggota, hapus dulu lewat halaman Edit Anggota.
</p>
@endsection