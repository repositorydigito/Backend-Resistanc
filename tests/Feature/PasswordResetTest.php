<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PasswordResetCode;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_password_reset_code()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/send-reset-code', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Se ha enviado un código de verificación a tu correo electrónico.',
                    'data' => [
                        'email' => 'test@example.com',
                        'expires_in' => 600,
                    ],
                ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'test@example.com',
            'used' => false,
        ]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);
    }

    public function test_cannot_send_reset_code_for_nonexistent_email()
    {
        $response = $this->postJson('/api/auth/send-reset-code', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'No se encontró un usuario con ese correo electrónico.',
                ]);
    }

    public function test_can_verify_valid_reset_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $resetCode = PasswordResetCode::createForEmail('test@example.com');

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => $resetCode->code,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Código verificado correctamente.',
                    'data' => [
                        'email' => 'test@example.com',
                        'verified' => true,
                    ],
                ]);
    }

    public function test_cannot_verify_invalid_reset_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'test@example.com',
            'code' => '9999',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Código inválido o expirado.',
                ]);
    }

    public function test_can_reset_password_with_valid_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $resetCode = PasswordResetCode::createForEmail('test@example.com');

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'code' => $resetCode->code,
            'password' => 'NewSecurePassword123!@#',
            'password_confirmation' => 'NewSecurePassword123!@#',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Contraseña restablecida correctamente.',
                    'data' => [
                        'email' => 'test@example.com',
                    ],
                ]);

        // Verificar que la contraseña fue cambiada
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!@#', $user->password));

        // Verificar que el código fue marcado como usado
        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'test@example.com',
            'code' => $resetCode->code,
            'used' => true,
        ]);
    }

    public function test_cannot_reset_password_with_invalid_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'code' => '9999',
            'password' => 'NewSecurePassword123!@#',
            'password_confirmation' => 'NewSecurePassword123!@#',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Código inválido o expirado.',
                ]);
    }

    public function test_validation_errors_for_send_reset_code()
    {
        $response = $this->postJson('/api/auth/send-reset-code', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_validation_errors_for_verify_reset_code()
    {
        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'invalid-email',
            'code' => '123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'code']);
    }

    public function test_validation_errors_for_reset_password()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'invalid-email',
            'code' => '123',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'code', 'password']);
    }
}
