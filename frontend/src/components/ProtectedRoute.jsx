import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/useAuth'
import { Loading } from './StatusViews'

/**
 * Route layout bảo vệ các trang con.
 * - Chưa đăng nhập -> chuyển tới /login, nhớ trang đang định vào để quay lại sau khi đăng nhập.
 * - adminOnly mà không phải admin -> trang 403.
 * Lưu ý: đây chỉ là UX. Bảo mật thật nằm ở backend (middleware admin), vì mã JS chạy trên máy người dùng nên sửa được.
 */
export default function ProtectedRoute({ adminOnly = false }) {
  const { user, initializing, isAdmin } = useAuth()
  const location = useLocation()

  if (initializing) return <Loading label="Đang kiểm tra đăng nhập..." />

  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  if (adminOnly && !isAdmin) {
    return (
      <div className="empty panel">
        <h1>403</h1>
        <p>Bạn không có quyền truy cập trang này.</p>
      </div>
    )
  }

  return <Outlet />
}
