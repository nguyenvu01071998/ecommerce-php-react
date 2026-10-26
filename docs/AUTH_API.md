# Auth API

Xác thực bằng **Bearer token** (Laravel Sanctum personal access token). Token hết hạn sau 7 ngày (`SANCTUM_TOKEN_EXPIRATION`, tính bằng phút).

| Method | Endpoint | Auth | Mô tả | Giới hạn |
| --- | --- | --- | --- | --- |
| POST | `/api/register` | - | Đăng ký. Body: `name`, `email`, `password`, `password_confirmation` | 10 lần/phút/IP |
| POST | `/api/login` | - | Đăng nhập. Body: `email`, `password` | 5 lần/phút cho mỗi cặp email + IP |
| POST | `/api/logout` | Bearer | Thu hồi token đang dùng | - |
| GET | `/api/me` | Bearer | Thông tin user hiện tại | - |

Response thành công của register/login: `{"user": {id, name, email, role, created_at}, "token": "1|abc..."}`.
Gọi API cần xác thực: header `Authorization: Bearer <token>` và `Accept: application/json`.

| Mã | Ý nghĩa |
| --- | --- |
| 401 | Chưa đăng nhập, token sai/hết hạn, hoặc sai email/mật khẩu |
| 403 | Đã đăng nhập nhưng không đủ quyền (middleware `admin`) |
| 422 | Dữ liệu không hợp lệ, kèm `errors` theo từng trường |
| 429 | Vượt giới hạn, xem header `Retry-After` (giây) |

Bảo vệ route admin: `Route::middleware(['auth:sanctum', 'admin'])->...`

## Phòng chống tấn công

**SQL Injection.** Mọi truy vấn đi qua Eloquent / Query Builder, giá trị luôn được bind qua PDO prepared statement, không nối chuỗi vào SQL. Chuỗi `Robert'); DROP TABLE users;--` chỉ được lưu như văn bản thường (có test). Quy tắc: không dùng `DB::raw()` / `whereRaw()` với dữ liệu người dùng; nếu bắt buộc thì phải truyền tham số `?`. Form Request còn validate kiểu dữ liệu trước khi chạm DB.

**XSS.** API chỉ trả JSON (`Content-Type: application/json`), không render HTML từ dữ liệu người dùng. Phía React, JSX tự escape mọi giá trị chèn vào, nên không dùng `dangerouslySetInnerHTML`. Dữ liệu lưu nguyên bản, việc escape thực hiện lúc hiển thị. Token đặt ở `localStorage` ở bước frontend nên XSS vẫn là rủi ro lớn nhất: giữ chặt CSP, không chèn HTML thô, không thêm script bên thứ ba không cần thiết. `UserResource` chỉ trả trường an toàn.

**CSRF.** CSRF lợi dụng việc trình duyệt tự gửi cookie. API này xác thực bằng header `Authorization: Bearer`, trình duyệt không tự gắn header này nên trang của kẻ tấn công không thể mạo danh. Route `api` cũng không dùng cookie session. Nếu sau này chuyển sang cookie (Sanctum SPA mode) thì phải bật `/sanctum/csrf-cookie` và `supports_credentials`.

**Khác.** Mật khẩu băm bcrypt (cast `hashed`), mass assignment bị chặn (`role` không trong `$fillable`), thông báo đăng nhập sai không tiết lộ email có tồn tại, rate limit chống dò mật khẩu, CORS chỉ cho đúng origin của frontend (`FRONTEND_URL`).
