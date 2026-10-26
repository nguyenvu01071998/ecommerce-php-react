import axios from 'axios'

const TOKEN_KEY = 'token'

// localStorage có thể ném lỗi (chế độ riêng tư, bị chặn) nên luôn bọc try/catch
export const tokenStorage = {
  get() {
    try {
      return localStorage.getItem(TOKEN_KEY)
    } catch {
      return null
    }
  },
  set(token) {
    try {
      localStorage.setItem(TOKEN_KEY, token)
    } catch {
      /* bỏ qua: người dùng vẫn dùng được trong phiên hiện tại */
    }
  },
  clear() {
    try {
      localStorage.removeItem(TOKEN_KEY)
    } catch {
      /* bỏ qua */
    }
  },
}

// AuthContext đăng ký hàm này để biết khi nào token hết hạn/bị thu hồi
let onUnauthorized = () => {}
export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' }, // bắt buộc để Laravel trả lỗi dạng JSON
  timeout: 15000,
})

// Request interceptor: tự gắn Bearer token vào MỌI request, component không cần nhớ làm việc này
client.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor: 401 khi đang có token = token hết hạn/bị thu hồi -> đăng xuất phía client.
// Request không kèm token (vd đăng nhập sai mật khẩu) trả 401 thì KHÔNG kích hoạt, để form tự hiện lỗi.
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && error.config?.headers?.Authorization) {
      tokenStorage.clear()
      onUnauthorized()
    }
    return Promise.reject(error)
  },
)

export default client
