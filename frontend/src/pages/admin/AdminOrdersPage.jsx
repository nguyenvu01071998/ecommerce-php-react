import { useState } from 'react'
import { adminApi } from '../../api/services'
import { EmptyState, ErrorMessage, Loading } from '../../components/StatusViews'
import OrderStatusBadge from '../../components/OrderStatusBadge'
import Pagination from '../../components/Pagination'
import { useApi } from '../../hooks/useApi'
import { flattenErrors, getErrorMessage, getFieldErrors } from '../../utils/errors'
import { STATUS_LABELS, STATUS_TRANSITIONS, formatDate, formatPrice } from '../../utils/format'

function OrderRow({ order, onChanged }) {
  const nextStatuses = STATUS_TRANSITIONS[order.status] ?? []
  const [selected, setSelected] = useState(nextStatuses[0] ?? '')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  const handleUpdate = async () => {
    if (selected === 'cancelled' && !window.confirm(`Huỷ đơn #${order.id}? Tồn kho sẽ được hoàn lại.`)) return
    setSaving(true)
    setError('')
    try {
      await adminApi.updateOrderStatus(order.id, selected)
      onChanged()
    } catch (err) {
      const messages = flattenErrors(getFieldErrors(err))
      setError(messages.length > 0 ? messages.join(' ') : getErrorMessage(err))
    } finally {
      setSaving(false)
    }
  }

  return (
    <section className="order">
      <header className="order-head">
        <strong>Đơn #{order.id}</strong>
        <span>
          {order.user?.name} <span className="muted">({order.user?.email})</span>
        </span>
        <span className="muted">{formatDate(order.created_at)}</span>
        <OrderStatusBadge status={order.status} />
      </header>
      <ul className="order-items">
        {order.items.map((item) => (
          <li key={item.product_id}>
            <span>
              {item.product?.name} × {item.quantity}
            </span>
            <span>{formatPrice(item.subtotal)}</span>
          </li>
        ))}
      </ul>
      <div className="order-foot">
        <strong className="price">{formatPrice(order.total)}</strong>
        {nextStatuses.length > 0 && (
          <div className="status-change">
            <select value={selected} onChange={(e) => setSelected(e.target.value)} aria-label={`Trạng thái mới của đơn ${order.id}`}>
              {nextStatuses.map((status) => (
                <option key={status} value={status}>
                  {STATUS_LABELS[status]}
                </option>
              ))}
            </select>
            <button type="button" className="btn btn-small btn-primary" onClick={handleUpdate} disabled={saving}>
              {saving ? 'Đang lưu...' : 'Cập nhật'}
            </button>
          </div>
        )}
      </div>
      {error && (
        <p className="alert alert-error" role="alert">
          {error}
        </p>
      )}
    </section>
  )
}

export default function AdminOrdersPage() {
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const { data, loading, error, reload } = useApi(
    (signal) => adminApi.listOrders({ status, page }, signal),
    [status, page],
  )

  return (
    <>
      <div className="toolbar">
        <select
          value={status}
          onChange={(e) => {
            setPage(1)
            setStatus(e.target.value)
          }}
          aria-label="Lọc theo trạng thái"
        >
          <option value="">Tất cả trạng thái</option>
          {Object.entries(STATUS_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
      </div>

      {error && <ErrorMessage message={error} onRetry={reload} />}
      {loading && !data && <Loading />}

      {data &&
        (data.data.length === 0 ? (
          <EmptyState>Không có đơn hàng nào.</EmptyState>
        ) : (
          <div className={loading ? 'is-refreshing' : undefined}>
            {data.data.map((order) => (
              // key gồm status: đổi trạng thái xong thì dòng được tạo lại với danh sách lựa chọn mới
              <OrderRow key={`${order.id}-${order.status}`} order={order} onChanged={reload} />
            ))}
            <Pagination meta={data.meta} disabled={loading} onPageChange={setPage} />
          </div>
        ))}
    </>
  )
}
