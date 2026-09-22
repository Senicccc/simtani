<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nomor_anggota' => 'ANG-' . $this->faker->unique()->numberBetween(100, 999),
            'nama_lengkap' => $this->faker->name(),
            'foto' => null,
            'nomor_wa' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'anggota',
            'status_aktif' => true,
            'alamat' => $this->faker->address(),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'nomor_anggota' => 'ADM-001',
            'nama_lengkap' => 'Admin SIMTANI',
            'role' => 'admin',
            'email' => 'admin@simtani.test',
            'status_aktif' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status_aktif' => false,
        ]);
    }
}
