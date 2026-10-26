import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { ordersApi } from '../api/services'
import Pagination from '../components/Pagination'
import Price from '../components/Price'
import { ErrorMessage, Loading } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { STATUS_LABELS, formatDate } from '../utils/format'

const TABS = [['', 'Tất cả'], ...Object.entries(STATUS_LABELS)]

export default function OrdersPage() {
  const location = useLocation()
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const { data, loading, error, reload } = useApi(
    (signal) => ordersApi.list({ status, page }, signal),
    [status, page],
  )

  return (
    <>
      <h1 className="page-title">Đơn mua</h1>

      {location.state?.placed && (
        <p className="alert alert-success" role="status">
          Đặt hàng thành công! Cảm ơn bạn.
        </p>
      )}

      <nav className="tabs" aria-label="Lọc đơn hàng theo trạng thái">
        {TABS.map(([value, label]) => (
          <button
            key={value}
            type="button"
            className={status === value ? 'tab is-active' : 'tab'}
            aria-pressed={status === value}
            onClick={() => {
              setStatus(value)
              setPage(1)
            }}
          >
            {label}
          </button>
        ))}
      </nav>

      {error && <ErrorMessage message={error} onRetry={reload} />}
      {loading && !data && <Loading />}

      {data &&
        (data.data.length === 0 ? (
          <div className="empty panel">
            <h2>Chưa có đơn hàng</h2>
            <p>{status ? 'Không có đơn nào ở trạng thái này.' : 'Bạn chưa đặt đơn hàng nào.'}</p>
            <Link to="/" className="btn btn-primary">
              Mua sắm ngay
            </Link>
          </div>
        ) : (
          <div className={loading ? 'is-refreshing' : undefined}>
            {data.data.map((order) => (
              <section key={order.id} className="order panel">
                <header className="order-head">
                  <strong>Đơn #{order.id}</strong>
                  <span className="muted">{formatDate(order.created_at)}</span>
                  <span className={`order-status order-status-${order.status}`}>{STATUS_LABELS[order.status]}</span>
                </header>
                <ul className="order-lines">
                  {order.items.map((item) => (
                    <li key={item.product_id} className="order-line">
                      <img src={item.product?.image_url} alt="" width="64" height="64" className="order-thumb" />
                      <span className="order-line-name">{item.product?.name}</span>
                      <span className="muted">x{item.quantity}</span>
                      <Price value={item.subtotal} />
                    </li>
                  ))}
                </ul>
                <p className="order-total">
                  Thành tiền: <Price value={order.total} size="lg" />
                </p>
              </section>
            ))}
            <Pagination meta={data.meta} disabled={loading} onPageChange={setPage} />
          </div>
        ))}
    </>
  )
}
