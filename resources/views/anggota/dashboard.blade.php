@extends('layouts.backend')

@section('title', 'Dashboard Anggota')

@section('content')
<h2>Dashboard Anggota</h2>
<div class="stat-row">
  <div class="stat-card">
    <div class="k">Tugas aktif</div>
    <div class="v">{{ $activeTasks }}</div>
  </div>
  <div class="stat-card">
    <div class="k">Tugas mendatang</div>
    <div class="v">{{ $upcomingTasks }}</div>
  </div>
</div>
<div class="stat-row">
  <div class="stat-card">
    <div class="k">Tugas selesai</div>
    <div class="v">{{ $completedTasks }}</div>
  </div>
  <div class="stat-card">
    <div class="k">Upah belum dibayar</div>
    <div class="v">{{ $unpaidWages }}</div>
  </div>
</div>
@endsection
