/**
 * Khung xương hiển thị trong lúc tải. Có kích thước giống nội dung thật nên trang không bị nhảy bố cục
 * (CLS) khi dữ liệu về. Animation tự tắt khi người dùng bật "giảm chuyển động" (xem styles.css).
 */
export function ProductGridSkeleton({ count = 12 }) {
  return (
    <ul className="grid" aria-hidden="true">
      {Array.from({ length: count }, (_, index) => (
        <li key={index} className="card card-skeleton">
          <div className="skeleton skeleton-media" />
          <div className="card-body">
            <div className="skeleton skeleton-line" />
            <div className="skeleton skeleton-line skeleton-short" />
            <div className="skeleton skeleton-line skeleton-price" />
          </div>
        </li>
      ))}
    </ul>
  )
}

export function DetailSkeleton() {
  return (
    <div className="detail panel" aria-hidden="true">
      <div className="skeleton detail-media" />
      <div className="detail-info">
        <div className="skeleton skeleton-line skeleton-title" />
        <div className="skeleton skeleton-line skeleton-short" />
        <div className="skeleton skeleton-block" />
      </div>
    </div>
  )
}
