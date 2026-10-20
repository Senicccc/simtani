@extends('layouts.backend')

@section('title', 'Hubungi Admin')

@section('content')
<div class="page-head">
  <div>
    <h2>Hubungi Admin</h2>
    <p>Hubungi admin lewat WhatsApp atau email bila Anda membutuhkan bantuan.</p>
  </div>
</div>

<div class="panel">
  @if (count($admins))
    <ul class="contact-list">
      @foreach ($admins as $admin)
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
            @if ($admin->nomor_wa)
              <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $admin->nomor_wa) }}" target="_blank" rel="noopener" class="btn-outline">WhatsApp</a>
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