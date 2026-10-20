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
          <tr>
            <td>
              <span class="cell-name">{{ $item->nama_pekerjaan }}</span>
              @if ($item->deskripsi)
                <span class="cell-sub">{{ \Illuminate\Support\Str::limit($item->deskripsi, 80) }}</span>
              @endif
            </td>
            <td>{{ $item->satuan }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.jenis-pekerjaan.edit', $item) }}" class="table-action">Edit</a>
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
@endsection