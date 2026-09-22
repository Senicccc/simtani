<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Models\Upah;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        $activeMembers = User::where('role', 'anggota')->where('status_aktif', true)->count();
        $totalSchedules = Jadwal::count();
        $tasksRunning = Penugasan::whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])->count();
        $pendingRequests = PermintaanPerubahan::where('status', 'pending')->count();
        $unpaidWages = Upah::where('status_pembayaran', 'belum_dibayar')->count();

        return view('admin.dashboard', compact(
            'activeMembers',
            'totalSchedules',
            'tasksRunning',
            'pendingRequests',
            'unpaidWages'
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

        return view('anggota.dashboard', compact(
            'activeTasks',
            'upcomingTasks',
            'completedTasks',
            'unpaidWages'
        ));
    }
}
