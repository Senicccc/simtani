@extends('layouts.backend')

@section('title', 'Riwayat Tugas')

@section('content')
<div class="page-head">
  <div>
    <h2>Riwayat Tugas</h2>
    <p>{{ $penugasan->count() }} tugas selesai.</p>
  </div>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Kembali ke Tugas Aktif</a>
</div>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>Selesai pada</th>
          <th>Upah</th>
          <th>Status Pembayaran</th>
          <th class="col-end">Detail</th>
        </tr>
      </thead>
      <tbody>
        @forelse($penugasan as $item)
          <tr>
            <td><span class="cell-name">{{ $item->jadwal->nama_kegiatan }}</span></td>
            <td class="cell-num">{{ $item->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="cell-num">{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum tercatat' }}</td>
            <td>
              @if($item->upah)
                @include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $item->upah->status_pembayaran])
              @else
                -
              @endif
            </td>
            <td>
              <div class="row-actions">
                <a href="{{ route('anggota.tugas.show', $item) }}" class="table-action">Lihat Tugas</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty-state">Belum ada tugas selesai.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection