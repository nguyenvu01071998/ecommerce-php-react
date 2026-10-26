import { STATUS_LABELS } from '../utils/format'

export default function OrderStatusBadge({ status }) {
  return <span className={`badge badge-${status}`}>{STATUS_LABELS[status] ?? status}</span>
}
