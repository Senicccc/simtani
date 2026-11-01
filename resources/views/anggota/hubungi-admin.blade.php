@extends('layouts.backend')

@section('title', 'Hubungi Admin')

@section('content')
<div class="page-head">
  <div>
    <h2>Hubungi Admin</h2>
    <p>Hubungi admin lewat WhatsApp atau email, atau buat tiket untuk kendala yang perlu dicatat.</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('anggota.tiket.create') }}" class="btn-primary">Buat Tiket</a>
    <a href="{{ route('anggota.tiket.index') }}" class="btn-outline">Tiket Saya</a>
  </div>
</div>

<div class="panel">
  @if (count($admins))
    <ul class="contact-list">
      @foreach ($admins as $admin)
        @php
          $wa = preg_replace('/\D+/', '', (string) $admin->nomor_wa);
          if (str_starts_with($wa, '0')) { $wa = '62'.substr($wa, 1); }
        @endphp
        <li>
          <div>
            <span class="cell-name">{{ $admin->nama_lengkap }}</span>
            @if ($admin->nomor_wa || $admin->email)
              <span class="cell-sub meta-row">
                @if ($admin->nomor_wa)<span>{{ $admin->nomor_wa }}</span>@endif
                @if ($admin->email)<span>{{ $admin->email }}</span>@endif
              </span>
            @endif
          </div>
          <div class="contact-actions">
            @if ($wa !== '')
              <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-outline">WhatsApp</a>
            @endif
            @if ($admin->email)
              <a href="mailto:{{ $admin->email }}" class="btn-outline">Email</a>
            @endif
          </div>
        </li>
      @endforeach
    </ul>
  @else
    <div class="empty-state">Kontak admin belum tersedia.</div>
  @endif
</div>
@endsection