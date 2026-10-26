<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // Chỉ lấy các trường đã validate. 'role' không có trong đây nên luôn là 'user' (mặc định của DB)
        // và mật khẩu được cast 'hashed' của model tự băm bcrypt.
        $user = User::create($request->validated());

        return response()->json([
            'user' => new UserResource($user->refresh()),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // validate() chỉ kiểm tra thông tin đăng nhập, không tạo session. Việc so sánh hash do Laravel xử lý.
        if (! Auth::validate($request->only('email', 'password'))) {
            // Cùng một thông báo cho "sai email" và "sai mật khẩu" để kẻ tấn công không dò được email nào tồn tại
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng.'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Chỉ thu hồi token đang dùng (đăng xuất thiết bị này), các thiết bị khác vẫn đăng nhập
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    public function me(Request $request): JsonResponse
    {
        // Cùng dạng {"user": {...}} với register/login để frontend dùng chung một cách xử lý
        return response()->json(['user' => new UserResource($request->user())]);
    }
}
