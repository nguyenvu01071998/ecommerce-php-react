import { formatNumber } from '../utils/format'

/**
 * Giá kiểu sàn thương mại: ký hiệu ₫ nhỏ đứng trước số. `size` = "lg" cho giá nổi bật ở trang chi tiết.
 */
export default function Price({ value, size }) {
  return (
    <span className={size ? `price price-${size}` : 'price'}>
      <span className="price-cur">₫</span>
      {formatNumber(value)}
    </span>
  )
}
