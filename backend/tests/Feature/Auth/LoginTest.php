<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'a@example.com',
            'password' => 'Matkhau123', // cast 'hashed' của model tự băm
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->createUser();

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'Matkhau123'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token'])
            ->assertJsonPath('user.email', 'a@example.com');
    }

    public function test_login_is_case_insensitive_for_email(): void
    {
        $this->createUser();

        $this->postJson('/api/login', ['email' => 'A@EXAMPLE.com', 'password' => 'Matkhau123'])->assertOk();
    }

    public function test_login_fails_with_same_message_for_wrong_password_and_unknown_email(): void
    {
        $this->createUser();

        $wrongPassword = $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'SaiMatKhau1']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'khong@example.com', 'password' => 'Matkhau123']);

        $wrongPassword->assertUnauthorized();
        $unknownEmail->assertUnauthorized();
        // Không để lộ email có tồn tại hay không
        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        // Payload kiểu SQL injection bị chặn ngay ở bước validate email
        $this->postJson('/api/login', ['email' => "' OR 1=1 --", 'password' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $this->createUser();
        $payload = ['email' => 'a@example.com', 'password' => 'SaiMatKhau1'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $payload)->assertUnauthorized();
        }

        // Lần thứ 6 bị chặn, kể cả khi dùng đúng mật khẩu
        $this->postJson('/api/login', $payload)->assertTooManyRequests()->assertHeader('Retry-After');
        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'Matkhau123'])->assertTooManyRequests();
    }

    public function test_rate_limit_is_per_email(): void
    {
        $this->createUser();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'SaiMatKhau1']);
        }

        // Email khác không bị ảnh hưởng bởi bộ đếm của email a@example.com
        $this->postJson('/api/login', ['email' => 'b@example.com', 'password' => 'x'])->assertUnauthorized();
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        // Không gửi header Accept vẫn phải trả JSON 401, không được redirect/500
        $this->get('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_current_user_with_bearer_token(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'a@example.com')
            ->assertJsonMissingPath('user.password');
    }

    public function test_logout_revokes_current_token_only(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('thiet-bi-1')->plainTextToken;
        $user->createToken('thiet-bi-2');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'thiet-bi-1']);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        // Đẩy thời gian đi qua hạn 7 ngày
        $this->travel(8)->days();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_admin_middleware_separates_401_403_and_200(): void
    {
        // Route tạm chỉ dùng trong test, để kiểm tra middleware 'admin' độc lập với các API thật
        Route::middleware(['auth:sanctum', 'admin'])->get('/api/test-admin-only', fn () => response()->json(['ok' => true]));

        $this->getJson('/api/test-admin-only')->assertUnauthorized();

        $user = User::factory()->create();
        $this->withToken($user->createToken('t')->plainTextToken)
            ->getJson('/api/test-admin-only')->assertForbidden();

        // Guard giữ lại user của request trước trong cùng 1 test, phải xoá đi để xác thực lại bằng token mới
        $this->app['auth']->forgetGuards();

        $admin = User::factory()->admin()->create();
        $this->withToken($admin->createToken('t')->plainTextToken)
            ->getJson('/api/test-admin-only')->assertOk();
    }
}
