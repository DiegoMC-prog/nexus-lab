<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolSeeder::class);
    }

    /**
     * Test static normalizeEmail method directly.
     */
    public function test_normalize_email_helper_method(): void
    {
        $this->assertEquals('camilotapia1983@gmail.com', User::normalizeEmail('camilo.tapia.1983@gmail.com'));
        $this->assertEquals('camilotapia1983@gmail.com', User::normalizeEmail('camilotapia1983@gmail.com'));
        $this->assertEquals('camilotapia1983@gmail.com', User::normalizeEmail('camilotapia1983+alias@gmail.com'));
        $this->assertEquals('camilotapia1983@gmail.com', User::normalizeEmail(' CAMILOTAPIA1983@GMAIL.COM '));
        $this->assertEquals('camilotapia1983@googlemail.com', User::normalizeEmail('camilo.tapia.1983@googlemail.com'));
        
        // Non-gmail emails should not have dots removed or alias truncated, but lowercase and trim should apply
        $this->assertEquals('camilo.tapia.1983@example.com', User::normalizeEmail(' CAMILO.TAPIA.1983@EXAMPLE.COM '));
    }

    /**
     * Test User model mutator normalizes email.
     */
    public function test_user_model_mutator_normalizes_email(): void
    {
        $user = User::factory()->create([
            'email' => 'camilo.tapia.1983@gmail.com',
        ]);

        $this->assertEquals('camilotapia1983@gmail.com', $user->email);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'camilotapia1983@gmail.com',
        ]);
    }

    /**
     * Test validation prevents duplicate user registration with equivalent gmail addresses.
     */
    public function test_validation_prevents_duplicate_registration_with_equivalent_gmail(): void
    {
        // Create an admin user to perform the API requests (since UserController has middleware permission checks)
        $admin = User::factory()->create([
            'email' => 'admin@nexuslab.com',
        ]);
        $admin->assignRole('admin');

        // Retrieve student role ID
        $estudianteRole = Role::where('name', 'estudiante')->first();

        // 1. Register a user via the API
        $response = $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Camilo Tapia',
                'email' => 'camilotapia1983@gmail.com',
                'role' => $estudianteRole->id,
                'estado' => 'activo',
            ]);

        $response->assertStatus(200);

        // 2. Attempt to register equivalent email address
        $response2 = $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Camilo Tapia 2',
                'email' => 'camilo.tapia.1983@gmail.com',
                'role' => $estudianteRole->id,
                'estado' => 'activo',
            ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that user can login using an equivalent email.
     */
    public function test_login_works_with_equivalent_gmail(): void
    {
        // Create user
        $user = User::factory()->create([
            'email' => 'camilotapia1983@gmail.com',
            'password' => Hash::make('password123'),
            'estado' => 'activo',
        ]);
        $user->assignRole('estudiante');

        // Add a known device to avoid OTP
        $user->dispositivos()->create([
            'fingerprint' => 'test-fingerprint',
            'nombre_dispositivo' => 'test-device',
        ]);

        // Attempt login using equivalent email
        $response = $this->postJson('/api/login', [
            'email' => 'camilo.tapia.1983@gmail.com',
            'password' => 'password123',
            'fingerprint' => 'test-fingerprint',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
        $this->assertEquals('camilotapia1983@gmail.com', $response->json('user.email'));
    }
}
