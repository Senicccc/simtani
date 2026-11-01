@extends('layouts.backend')
@section('title', 'Tiket Bantuan')

@section('content')
<div class="page-head">
  <div>
    <h2>Tiket Bantuan</h2>
    <p>{{ $tiket->count() }} tiket ditampilkan.</p>
  </div>
</div>

<form method="get" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari subjek atau nama anggota" aria-label="Cari tiket">
  <select name="status" aria-label="Filter status">
    <option value="">Semua status</option>
    @foreach (['open' => 'Terbuka', 'in_progress' => 'Diproses', 'resolved' => 'Selesai'] as $nilai => $label)
      <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn-primary">Cari</button>
  @if (request('q') || request('status'))
    <a href="{{ route('admin.tiket.index') }}" class="btn-outline">Bersihkan</a>
  @endif
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Subjek</th>
          <th>Pengirim</th>
          <th>Status</th>
          <th>Diperbarui</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($tiket as $item)
          <tr>
            <td>
              <span class="cell-name">{{ $item->subjek }}</span>
              <span class="cell-sub">{{ $item->kategori }}</span>
            </td>
            <td>{{ $item->anggota->nama_lengkap }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'tiket', 'status' => $item->status])</td>
            <td class="cell-num">{{ $item->updated_at->format('d/m/Y H:i') }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.tiket.show', $item) }}" class="table-action">{{ $item->status === 'open' ? 'Proses' : 'Buka' }}</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty-state">{{ request('q') || request('status') ? 'Tidak ada tiket yang cocok.' : 'Belum ada tiket.' }}</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection