@extends('layouts.backend')

@section('title', 'Dashboard Admin')

@section('content')
    <h2>Dashboard Admin</h2>
    <dl>
        <dt>Anggota aktif</dt><dd>{{ $activeMembers }}</dd>
        <dt>Total jadwal</dt><dd>{{ $totalSchedules }}</dd>
        <dt>Tugas berjalan</dt><dd>{{ $tasksRunning }}</dd>
        <dt>Permintaan pending</dt><dd>{{ $pendingRequests }}</dd>
        <dt>Upah belum dibayar</dt><dd>{{ $unpaidWages }}</dd>
    </dl>
@endsection
