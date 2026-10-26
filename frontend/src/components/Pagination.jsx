import { ChevronLeftIcon, ChevronRightIcon } from './icons'

// Trả về danh sách dạng [1, 'start-ellipsis', 4, 5, 6, 'end-ellipsis', 20]: luôn có trang đầu, trang cuối, và các trang quanh trang hiện tại
function getPageItems(current, last) {
  if (last <= 7) return Array.from({ length: last }, (_, index) => index + 1)

  const items = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)

  if (start > 2) items.push('start-ellipsis')
  for (let page = start; page <= end; page++) items.push(page)
  if (end < last - 1) items.push('end-ellipsis')
  items.push(last)

  return items
}

/**
 * meta: đối tượng `meta` của Laravel paginator. Ẩn hẳn khi chỉ có 1 trang.
 */
export default function Pagination({ meta, onPageChange, disabled = false }) {
  if (!meta || meta.last_page <= 1) return null

  const { current_page: current, last_page: last } = meta

  return (
    <nav className="pagination" aria-label="Phân trang">
      <button
        type="button"
        className="page-btn"
        disabled={disabled || current <= 1}
        onClick={() => onPageChange(current - 1)}
        aria-label="Trang trước"
      >
        <ChevronLeftIcon size={18} />
      </button>

      {getPageItems(current, last).map((item) =>
        typeof item === 'string' ? (
          <span key={item} className="page-ellipsis" aria-hidden="true">
            …
          </span>
        ) : (
          <button
            key={item}
            type="button"
            className={item === current ? 'page-btn is-active' : 'page-btn'}
            disabled={disabled}
            onClick={() => onPageChange(item)}
            aria-label={`Trang ${item}`}
            aria-current={item === current ? 'page' : undefined}
          >
            {item}
          </button>
        ),
      )}

      <button
        type="button"
        className="page-btn"
        disabled={disabled || current >= last}
        onClick={() => onPageChange(current + 1)}
        aria-label="Trang sau"
      >
        <ChevronRightIcon size={18} />
      </button>
    </nav>
  )
}
