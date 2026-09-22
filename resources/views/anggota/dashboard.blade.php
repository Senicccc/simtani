@extends('layouts.backend')

@section('title', 'Dashboard Anggota')

@section('content')
    <h2>Dashboard Anggota</h2>
    <dl>
        <dt>Tugas aktif</dt><dd>{{ $activeTasks }}</dd>
        <dt>Tugas mendatang</dt><dd>{{ $upcomingTasks }}</dd>
        <dt>Tugas selesai</dt><dd>{{ $completedTasks }}</dd>
        <dt>Upah belum dibayar</dt><dd>{{ $unpaidWages }}</dd>
    </dl>
@endsection
