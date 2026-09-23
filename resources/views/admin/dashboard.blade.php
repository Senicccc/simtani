@extends('layouts.backend')

@section('title', 'Dashboard Admin')

@section('content')
<h2>Dashboard Admin</h2>
<div class="stat-row">
  <div class="stat-card">
    <div class="k">Anggota aktif</div>
    <div class="v">{{ $activeMembers }} orang</div>
  </div>
  <div class="stat-card">
    <div class="k">Total jadwal</div>
    <div class="v">{{ $totalSchedules }}</div>
  </div>
  <div class="stat-card">
    <div class="k">Tugas berjalan</div>
    <div class="v">{{ $tasksRunning }}</div>
  </div>
</div>
<div class="stat-row">
  <div class="stat-card">
    <div class="k">Permintaan pending</div>
    <div class="v">{{ $pendingRequests }}</div>
  </div>
  <div class="stat-card">
    <div class="k">Upah belum dibayar</div>
    <div class="v">{{ $unpaidWages }}</div>
  </div>
</div>
@endsection
