import { Link, NavLink, Outlet, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/useAuth'
import { useCart } from '../context/useCart'
import { BagIcon, CartIcon, GridIcon, HomeIcon, LogoutIcon, ReceiptIcon, SearchIcon, UserIcon } from './icons'

export default function Layout() {
  const { user, isAdmin, logout } = useAuth()
  const { count } = useCart()
  const navigate = useNavigate()
  const location = useLocation()
  const [params] = useSearchParams()

  // Ô tìm kiếm ở header phản ánh từ khoá đang lọc ở trang chủ
  const currentSearch = location.pathname === '/' ? (params.get('search') ?? '') : ''

  const handleSearch = (event) => {
    event.preventDefault()
    const term = new FormData(event.currentTarget).get('search').trim()
    navigate(term ? `/?search=${encodeURIComponent(term)}` : '/')
  }

  return (
    <>
      <header className="header">
        {/* Thanh nhỏ phía trên: chỉ hiện trên màn hình rộng. Trên điện thoại dùng thanh điều hướng dưới cùng. */}
        <div className="topbar">
          <nav className="container topbar-inner" aria-label="Tài khoản">
            {isAdmin && <Link to="/admin">Quản trị</Link>}
            {user ? (
              <>
                <Link to="/orders">Đơn mua</Link>
                <span className="topbar-user">
                  <UserIcon size={16} />
                  {user.name}
                </span>
                <button type="button" className="link-button" onClick={logout}>
                  Đăng xuất
                </button>
              </>
            ) : (
              <>
                <Link to="/register">Đăng ký</Link>
                <span className="divider" aria-hidden="true" />
                <Link to="/login">Đăng nhập</Link>
              </>
            )}
          </nav>
        </div>

        <div className="container mainbar">
          <Link to="/" className="brand" aria-label="Ecommerce Demo, về trang chủ">
            <BagIcon size={34} />
            <span className="brand-text">Ecommerce Demo</span>
          </Link>

          {/* key={currentSearch}: tạo lại form khi từ khoá trên URL đổi (vd bấm Back) để ô nhập luôn khớp */}
          <form key={currentSearch} className="searchbar" role="search" onSubmit={handleSearch}>
            <input
              type="search"
              name="search"
              defaultValue={currentSearch}
              placeholder="Tìm sản phẩm theo tên..."
              aria-label="Tìm kiếm sản phẩm"
              maxLength={100}
            />
            <button type="submit" aria-label="Tìm kiếm">
              <SearchIcon size={20} />
            </button>
          </form>

          <Link to="/cart" className="cart-link" aria-label="Giỏ hàng">
            <CartIcon size={28} />
            {count > 0 && (
              <span className="cart-count" aria-hidden="true">
                {count > 99 ? '99+' : count}
              </span>
            )}
          </Link>
          {/* Thông báo số lượng cho trình đọc màn hình mỗi khi giỏ hàng thay đổi (không cướp focus) */}
          <span className="sr-only" role="status" aria-atomic="true">
            {count} sản phẩm trong giỏ hàng
          </span>
        </div>
      </header>

      <main className="container main">
        <Outlet />
      </main>

      <footer className="footer">
        <div className="container">
          <p>Ecommerce Demo: dự án mẫu Laravel + React. Giao diện lấy cảm hứng từ các sàn thương mại điện tử.</p>
        </div>
      </footer>

      <nav className="bottom-nav" aria-label="Điều hướng nhanh">
        <NavLink to="/" end>
          <HomeIcon size={22} />
          <span>Cửa hàng</span>
        </NavLink>
        <NavLink to="/cart">
          <span className="bottom-nav-icon">
            <CartIcon size={22} />
            {count > 0 && (
              <span className="bottom-nav-badge" aria-hidden="true">
                {count > 99 ? '99+' : count}
              </span>
            )}
          </span>
          <span>Giỏ hàng</span>
        </NavLink>
        {user ? (
          <>
            <NavLink to="/orders">
              <ReceiptIcon size={22} />
              <span>Đơn mua</span>
            </NavLink>
            {isAdmin && (
              <NavLink to="/admin">
                <GridIcon size={22} />
                <span>Quản trị</span>
              </NavLink>
            )}
            <button type="button" onClick={logout}>
              <LogoutIcon size={22} />
              <span>Đăng xuất</span>
            </button>
          </>
        ) : (
          <>
            <NavLink to="/login">
              <UserIcon size={22} />
              <span>Đăng nhập</span>
            </NavLink>
            <NavLink to="/register">
              <UserIcon size={22} />
              <span>Đăng ký</span>
            </NavLink>
          </>
        )}
      </nav>
    </>
  )
}
