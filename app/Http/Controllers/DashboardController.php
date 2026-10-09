<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Models\Upah;
use App\Models\User;
use App\Services\JadwalStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(JadwalStatusService $statuses): View
    {
        $schedules = Jadwal::query()->whereNotIn('status', ['completed', 'cancelled'])->get();
        $schedules->each(fn (Jadwal $schedule) => $statuses->refresh($schedule));
        $scheduledSchedules = Jadwal::where('status', 'scheduled')->count();
        $ongoingSchedules = Jadwal::where('status', 'ongoing')->count();
        $completedSchedules = Jadwal::where('status', 'completed')->count();
        $leaveActions = $schedules->sum(fn (Jadwal $schedule): int => $statuses->pendingReplacementSlots($schedule));
        $activeMembers = User::where('role', 'anggota')->where('status_aktif', true)->count();
        $totalSchedules = Jadwal::count();
        $tasksRunning = Penugasan::whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])->count();
        $tasksToVerify = Penugasan::where('status', 'waiting_verification')->count();
        $pendingRequests = PermintaanPerubahan::where('status', 'pending')->count();
        $unpaidWages = Upah::where('status_pembayaran', 'belum_dibayar')->count();
        $unpaidWageItems = Upah::with(['penugasan.anggota', 'penugasan.jadwal'])
            ->where('status_pembayaran', 'belum_dibayar')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'activeMembers',
            'totalSchedules',
            'scheduledSchedules',
            'ongoingSchedules',
            'completedSchedules',
            'leaveActions',
            'tasksRunning',
            'tasksToVerify',
            'pendingRequests',
            'unpaidWages',
            'unpaidWageItems',
        ));
    }

    public function anggota(): View
    {
        $user = Auth::user();

        $activeTasks = Penugasan::where('anggota_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])
            ->count();
        $upcomingTasks = Penugasan::where('anggota_id', $user->id)
            ->where('status', 'assigned')
            ->count();
        $completedTasks = Penugasan::where('anggota_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $unpaidWages = Upah::whereHas('penugasan', function ($query) use ($user) {
            $query->where('anggota_id', $user->id);
        })->where('status_pembayaran', 'belum_dibayar')->count();
        $pendingRequests = PermintaanPerubahan::where('diajukan_oleh', $user->id)->where('status', 'pending')->count();
        $memberTasks = Penugasan::with(['jadwal.jenisPekerjaan', 'presensiHarian'])
            ->where('anggota_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])
            ->orderBy('assigned_at')
            ->get();

        return view('anggota.dashboard', compact(
            'activeTasks',
            'upcomingTasks',
            'completedTasks',
            'unpaidWages',
            'pendingRequests',
            'memberTasks',
        ));
    }
}
