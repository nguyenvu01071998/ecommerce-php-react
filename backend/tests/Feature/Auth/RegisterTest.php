<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@example.com',
            'password' => 'Matkhau123',
            'password_confirmation' => 'Matkhau123',
        ], $overrides);
    }

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token'])
            ->assertJsonPath('user.email', 'a@example.com')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
    }

    public function test_password_is_stored_as_bcrypt_hash(): void
    {
        $this->postJson('/api/register', $this->validPayload());

        $stored = User::where('email', 'a@example.com')->value('password');

        $this->assertNotSame('Matkhau123', $stored);
        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertTrue(Hash::check('Matkhau123', $stored));
    }

    public function test_client_cannot_register_as_admin(): void
    {
        // Chống mass assignment: gửi role=admin lên thì vẫn chỉ là user thường
        $this->postJson('/api/register', $this->validPayload(['role' => 'admin']))->assertCreated();

        $this->assertSame(UserRole::User, User::where('email', 'a@example.com')->first()->role);
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $this->postJson('/api/register', $this->validPayload(['email' => '  A@Example.COM ']))->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->postJson('/api/register', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_register_validates_input(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        // Mật khẩu quá yếu / không có số
        $this->postJson('/api/register', $this->validPayload(['password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        // Xác nhận mật khẩu không khớp
        $this->postJson('/api/register', $this->validPayload(['password_confirmation' => 'Khac12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_sql_injection_payload_is_stored_as_plain_text(): void
    {
        // Tham số được bind qua PDO nên chuỗi này chỉ là dữ liệu, không bao giờ được thực thi như SQL
        $evilName = "Robert'); DROP TABLE users;--";

        $this->postJson('/api/register', $this->validPayload(['name' => $evilName]))->assertCreated();

        $this->assertDatabaseHas('users', ['name' => $evilName]);
        $this->assertDatabaseCount('users', 1); // bảng users vẫn còn nguyên
    }
}
