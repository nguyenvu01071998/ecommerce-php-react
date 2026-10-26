import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { productsApi } from '../api/services'
import { CartIcon, ChevronRightIcon } from '../components/icons'
import Price from '../components/Price'
import QuantityStepper from '../components/QuantityStepper'
import { DetailSkeleton } from '../components/Skeleton'
import { ErrorMessage } from '../components/StatusViews'
import { useCart } from '../context/useCart'
import { useApi } from '../hooks/useApi'
import { formatSold } from '../utils/format'

export default function ProductDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { data, loading, error, reload } = useApi((signal) => productsApi.get(id, signal), [id])
  const { addItem } = useCart()
  const [quantity, setQuantity] = useState(1)
  const [added, setAdded] = useState(false)

  if (loading && !data) return <DetailSkeleton />
  if (error) {
    return (
      <>
        <ErrorMessage message={error} onRetry={reload} />
        <Link to="/">← Về trang chủ</Link>
      </>
    )
  }

  const product = data.data
  const soldOut = product.stock === 0
  const maxQuantity = Math.max(1, Math.min(product.stock, 100))

  const handleAdd = () => {
    addItem(product, quantity)
    setAdded(true)
  }

  const handleBuyNow = () => {
    addItem(product, quantity)
    navigate('/cart')
  }

  return (
    <>
      <nav className="crumbs" aria-label="Đường dẫn">
        <Link to="/">Trang chủ</Link>
        <ChevronRightIcon size={14} />
        {product.category && (
          <>
            <Link to={`/?category=${product.category.slug}`}>{product.category.name}</Link>
            <ChevronRightIcon size={14} />
          </>
        )}
        <span aria-current="page">{product.name}</span>
      </nav>

      <div className="detail panel">
        <div className="detail-media">
          <img src={product.image_url} alt={product.name} width="600" height="600" />
        </div>

        <div className="detail-info">
          <h1 className="detail-title">{product.name}</h1>
          <p className="detail-stats">
            <span>
              <strong>{formatSold(product.sold_count ?? 0)}</strong> Đã bán
            </span>
            {product.category && <span className="muted">{product.category.name}</span>}
          </p>

          <div className="price-bar">
            <Price value={product.price} size="lg" />
          </div>

          {soldOut ? (
            <p className="alert alert-error" role="status">
              Sản phẩm tạm hết hàng.
            </p>
          ) : (
            <>
              <div className="buy-row">
                <span className="buy-label">Số lượng</span>
                <QuantityStepper
                  value={quantity}
                  max={maxQuantity}
                  onChange={(next) => {
                    setAdded(false)
                    setQuantity(next)
                  }}
                  label="Số lượng muốn mua"
                />
                <span className="muted">{product.stock} sản phẩm có sẵn</span>
              </div>

              <div className="detail-actions">
                <button type="button" className="btn btn-outline" onClick={handleAdd}>
                  <CartIcon size={20} />
                  Thêm vào giỏ hàng
                </button>
                <button type="button" className="btn btn-primary" onClick={handleBuyNow}>
                  Mua ngay
                </button>
              </div>
            </>
          )}

          {added && (
            <p className="alert alert-success" role="status">
              Đã thêm vào giỏ hàng. <Link to="/cart">Xem giỏ hàng</Link>
            </p>
          )}
        </div>
      </div>

      <section className="panel" aria-labelledby="desc-title">
        <h2 id="desc-title" className="panel-title">
          Mô tả sản phẩm
        </h2>
        {/* Hiển thị dạng văn bản thuần: React tự escape, nên mô tả không thể chứa HTML/script thực thi */}
        <p className="description">{product.description || 'Chưa có mô tả cho sản phẩm này.'}</p>
      </section>
    </>
  )
}
