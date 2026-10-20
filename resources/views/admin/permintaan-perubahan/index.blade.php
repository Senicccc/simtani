@extends('layouts.backend')

@section('title', 'Permintaan Perubahan Tugas')

@section('content')
<div class="page-head">
  <div>
    <h2>Permintaan Perubahan Tugas</h2>
    <p>{{ $permintaan->count() }} permintaan ditampilkan.</p>
  </div>
</div>

<form method="get" class="filter-bar">
  <label for="request-status" class="sr-only">Filter status</label>
  <select id="request-status" name="status">
    <option value="">Semua status</option>
    <option value="pending" @selected(request('status') === 'pending')>Menunggu diproses</option>
    <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
  </select>
  <button type="submit" class="btn-primary">Terapkan Filter</button>
  <a href="{{ route('admin.permintaan-perubahan.index') }}" class="btn-outline">Hapus Filter</a>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Pengaju</th>
          <th>Jenis</th>
          <th>Alasan</th>
          <th>Status</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($permintaan as $item)
          <tr>
            <td><span class="cell-name">{{ $item->diajukanOleh->nama_lengkap ?? '-' }}</span></td>
            <td>{{ ['tukar' => 'Pindah jadwal', 'sanggah' => 'Sanggah tugas'][$item->jenis_permintaan] ?? $item->jenis_permintaan }}</td>
            <td class="cell-reason">{{ \Illuminate\Support\Str::limit($item->alasan, 120) }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'permintaan', 'status' => $item->status])</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.permintaan-perubahan.show', $item) }}" class="table-action">Lihat Detail</a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5">
              <div class="empty-state">
                {{ request('status') ? 'Tidak ada permintaan yang sesuai dengan filter.' : 'Belum ada permintaan perubahan tugas.' }}
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection