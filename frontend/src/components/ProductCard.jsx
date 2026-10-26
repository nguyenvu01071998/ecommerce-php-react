import { Link } from 'react-router-dom'
import { formatSold } from '../utils/format'
import Price from './Price'

const LOW_STOCK_THRESHOLD = 5

// Cả thẻ là một link để bấm vào đâu cũng được (vùng chạm lớn trên điện thoại)
export default function ProductCard({ product }) {
  const soldOut = product.stock === 0
  const lowStock = !soldOut && product.stock <= LOW_STOCK_THRESHOLD

  return (
    <li className="card">
      <Link to={`/products/${product.id}`} className="card-link">
        <div className="card-media">
          {/* width/height + aspect-ratio giữ sẵn chỗ cho ảnh, tránh nhảy bố cục khi ảnh tải xong.
              React tự escape name/alt nên không thể chèn HTML/script. */}
          <img src={product.image_url} alt={product.name} loading="lazy" width="300" height="300" />
          {soldOut && <span className="card-soldout">Hết hàng</span>}
        </div>
        <div className="card-body">
          <h3 className="card-title">{product.name}</h3>
          <div className="card-tags">{lowStock && <span className="tag">Chỉ còn {product.stock}</span>}</div>
          <div className="card-meta">
            <Price value={product.price} />
            {product.sold_count > 0 && <span className="sold">Đã bán {formatSold(product.sold_count)}</span>}
          </div>
        </div>
      </Link>
    </li>
  )
}
