import { NavLink, Outlet } from 'react-router-dom'

export default function AdminLayout() {
  return (
    <>
      <h1 className="page-title">Quản trị</h1>
      <nav className="subnav" aria-label="Quản trị">
        <NavLink to="/admin" end>
          Sản phẩm
        </NavLink>
        <NavLink to="/admin/orders">Đơn hàng</NavLink>
      </nav>
      <Outlet />
    </>
  )
}
