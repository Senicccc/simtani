<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_root_redirects_to_role_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status_aktif' => true,
        ]);

        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
    }

    public function test_admin_can_login(): void
    {
        User::factory()->create([
            'nomor_anggota' => 'ADMIN-001',
            'nama_lengkap' => 'Admin SIMTANI',
            'role' => 'admin',
            'status_aktif' => true,
            'email' => 'admin@simtani.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'login' => 'admin@simtani.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_inactive_member_cannot_login(): void
    {
        User::factory()->create([
            'nomor_anggota' => 'ANG-001',
            'nama_lengkap' => 'Anggota Tidak Aktif',
            'role' => 'anggota',
            'status_aktif' => false,
            'email' => 'anggota.inactive@simtani.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'anggota.inactive@simtani.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
