@extends('layouts.backend')
@section('title', 'Tiket Saya')

@section('content')
<div class="page-head">
  <div>
    <h2>Tiket Saya</h2>
    <p>{{ $tiket->count() }} tiket.</p>
  </div>
  <a href="{{ route('anggota.tiket.create') }}" class="btn-outline">+ Buat Tiket</a>
</div>

<div class="panel">
  @if ($tiket->isEmpty())
    <div class="empty-state">Belum ada tiket. Klik Buat Tiket jika ada kendala.</div>
  @else
    <ul class="contact-list">
      @foreach ($tiket as $item)
        <li>
          <div>
            <a href="{{ route('anggota.tiket.show', $item) }}" class="cell-name">{{ $item->subjek }}</a>
            <span class="cell-sub">{{ $item->kategori }} · diperbarui {{ $item->updated_at->format('d/m/Y H:i') }}</span>
          </div>
          @include('admin.partials.status-badge', ['type' => 'tiket', 'status' => $item->status])
        </li>
      @endforeach
    </ul>
  @endif
</div>
@endsection