import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return (
    <div className="empty panel">
      <h1>404</h1>
      <p>Không tìm thấy trang bạn yêu cầu.</p>
      <Link to="/" className="btn btn-primary">
        Về trang chủ
      </Link>
    </div>
  )
}
