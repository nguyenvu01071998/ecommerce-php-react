import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ordersApi } from '../api/services'
import { CartIcon, TrashIcon } from '../components/icons'
import Price from '../components/Price'
import QuantityStepper from '../components/QuantityStepper'
import { useAuth } from '../context/useAuth'
import { useCart } from '../context/useCart'
import { flattenErrors, getErrorMessage, getFieldErrors } from '../utils/errors'

export default function CartPage() {
  const { items, count, subtotal, setQuantity, removeItem, clear } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()
  const [submitting, setSubmitting] = useState(false)
  const [errors, setErrors] = useState([])

  if (items.length === 0) {
    return (
      <div className="empty panel">
        <CartIcon size={64} />
        <h1>Giỏ hàng của bạn còn trống</h1>
        <Link to="/" className="btn btn-primary">
          Mua sắm ngay
        </Link>
      </div>
    )
  }

  const handleCheckout = async () => {
    setSubmitting(true)
    setErrors([])
    try {
      // Chỉ gửi id và số lượng. Giá, tổng tiền do server tính lại từ database.
      await ordersApi.create(items.map((item) => ({ product_id: item.id, quantity: item.quantity })))
      clear()
      navigate('/orders', { state: { placed: true } })
    } catch (error) {
      // 422 (vd hết hàng) có thông báo theo từng dòng; lỗi khác dùng thông báo chung
      const fieldErrors = flattenErrors(getFieldErrors(error))
      setErrors(fieldErrors.length > 0 ? fieldErrors : [getErrorMessage(error)])
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <>
      <h1 className="page-title">Giỏ hàng</h1>

      <div className="cart-table">
        <div className="cart-head" aria-hidden="true">
          <span>Sản phẩm</span>
          <span>Đơn giá</span>
          <span>Số lượng</span>
          <span>Số tiền</span>
          <span>Thao tác</span>
        </div>

        <ul className="cart-rows">
          {items.map((item) => (
            <li key={item.id} className="cart-row">
              <Link to={`/products/${item.id}`} className="cart-product">
                <img src={item.image_url} alt="" width="80" height="80" />
                <span className="cart-name">{item.name}</span>
              </Link>
              <div className="cart-cell cart-unit" data-label="Đơn giá">
                <Price value={item.price} />
              </div>
              <div className="cart-cell">
                <QuantityStepper
                  value={item.quantity}
                  max={Math.max(1, Math.min(item.stock, 100))}
                  onChange={(next) => setQuantity(item.id, next)}
                  label={`Số lượng ${item.name}`}
                />
              </div>
              <div className="cart-cell cart-line-total" data-label="Số tiền">
                <Price value={Number(item.price) * item.quantity} />
              </div>
              <div className="cart-cell">
                <button type="button" className="btn btn-small btn-ghost-danger" onClick={() => removeItem(item.id)}>
                  <TrashIcon size={16} />
                  Xoá
                </button>
              </div>
            </li>
          ))}
        </ul>
      </div>

      {errors.length > 0 && (
        <div className="alert alert-error" role="alert">
          <ul>
            {errors.map((message) => (
              <li key={message}>{message}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Thanh thanh toán dính ở đáy màn hình: luôn thấy tổng tiền và nút mua khi cuộn danh sách dài */}
      <div className="cart-bar">
        <div className="cart-bar-total">
          <span className="muted">
            Tạm tính ({count} sản phẩm)
          </span>
          <Price value={subtotal} size="lg" />
        </div>
        {user ? (
          <button type="button" className="btn btn-primary" onClick={handleCheckout} disabled={submitting}>
            {submitting ? 'Đang đặt hàng...' : 'Mua hàng'}
          </button>
        ) : (
          <Link to="/login" state={{ from: { pathname: '/cart' } }} className="btn btn-primary">
            Đăng nhập để mua hàng
          </Link>
        )}
      </div>
      <p className="muted cart-note">Tổng tiền cuối cùng do máy chủ tính theo giá hiện tại.</p>
    </>
  )
}
