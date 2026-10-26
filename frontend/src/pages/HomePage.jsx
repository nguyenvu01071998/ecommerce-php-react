import { Link, useSearchParams } from 'react-router-dom'
import { categoriesApi, productsApi } from '../api/services'
import CategoryIcon from '../components/CategoryIcon'
import { BoxIcon } from '../components/icons'
import Pagination from '../components/Pagination'
import ProductCard from '../components/ProductCard'
import { ProductGridSkeleton } from '../components/Skeleton'
import { ErrorMessage } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { formatNumber } from '../utils/format'

const PER_PAGE = 18 // chia hết cho 2, 3 và 6 cột nên hàng cuối luôn đầy
const PRICE_SORTS = ['price_asc', 'price_desc']

export default function HomePage() {
  // Bộ lọc nằm trên URL (?search=&category=&sort=&page=): chia sẻ được link, nút Back/Forward hoạt động đúng
  const [params, setParams] = useSearchParams()
  const search = params.get('search') ?? ''
  const category = params.get('category') ?? ''
  const sort = params.get('sort') ?? 'newest'
  const page = Number(params.get('page')) || 1

  const products = useApi(
    (signal) => productsApi.list({ search, category, sort, page, per_page: PER_PAGE }, signal),
    [search, category, sort, page],
  )
  const categories = useApi((signal) => categoriesApi.list(signal), [])

  const categoryList = categories.data?.data ?? []
  const categoriesLoading = categories.loading && !categories.data
  const activeCategory = categoryList.find((item) => item.slug === category)
  const hasFilter = Boolean(search || category)
  const list = products.data?.data ?? []
  const meta = products.data?.meta

  const updateParams = (changes) => {
    const next = new URLSearchParams(params)
    for (const [key, value] of Object.entries(changes)) {
      if (value && !(key === 'sort' && value === 'newest') && !(key === 'page' && value === 1)) {
        next.set(key, value)
      } else {
        next.delete(key)
      }
    }
    // Đổi bộ lọc thì quay về trang 1
    if (!('page' in changes)) next.delete('page')
    setParams(next)
  }

  return (
    <>
      {hasFilter ? (
        <div className="result-head">
          <h1>
            {search ? (
              <>
                Kết quả tìm kiếm cho “<strong>{search}</strong>”
              </>
            ) : (
              activeCategory?.name
            )}
          </h1>
          {search && activeCategory && <span className="chip is-active">{activeCategory.name}</span>}
          <Link to="/" className="link-button">
            Xoá bộ lọc
          </Link>
        </div>
      ) : (
        <section className="hero" aria-label="Giới thiệu">
          <div className="hero-main">
            <p className="hero-kicker">Chào mừng đến Ecommerce Demo</p>
            <h1>Mua sắm điện tử, gia dụng và phụ kiện</h1>
            {meta && <p>{formatNumber(meta.total)} sản phẩm đang được bán</p>}
            <a href="#products" className="btn btn-light">
              Mua sắm ngay
            </a>
          </div>
          <div className="hero-side">
            {categoriesLoading
              ? // Giữ chỗ trong lúc tải để bố cục không nhảy khi danh mục về
                [0, 1].map((index) => <div key={index} className="hero-tile skeleton" aria-hidden="true" />)
              : categoryList.slice(0, 2).map((item) => (
                  <Link key={item.id} to={`/?category=${item.slug}`} className="hero-tile">
                    <CategoryIcon slug={item.slug} size={32} />
                    <strong>{item.name}</strong>
                    <span>{item.products_count} sản phẩm</span>
                  </Link>
                ))}
          </div>
        </section>
      )}

      <section className="panel cats" aria-labelledby="cats-title">
        <h2 id="cats-title" className="panel-title">
          Danh mục
        </h2>
        <ul className="cats-list">
          <li>
            <Link to="/" className="cat-tile" aria-current={!category ? 'true' : undefined}>
              <span className="cat-icon">
                <BoxIcon size={28} />
              </span>
              <span className="cat-name">Tất cả</span>
            </Link>
          </li>
          {categoriesLoading &&
            [0, 1, 2, 3, 4].map((index) => (
              <li key={index} aria-hidden="true">
                <div className="cat-tile">
                  <span className="cat-icon skeleton" />
                  <span className="skeleton skeleton-line cat-skeleton-name" />
                </div>
              </li>
            ))}
          {categoryList.map((item) => (
            <li key={item.id}>
              <Link
                to={`/?category=${item.slug}`}
                className="cat-tile"
                aria-current={item.slug === category ? 'true' : undefined}
              >
                <span className="cat-icon">
                  <CategoryIcon slug={item.slug} />
                </span>
                <span className="cat-name">{item.name}</span>
              </Link>
            </li>
          ))}
        </ul>
      </section>

      <div className="sortbar" id="products">
        <span className="sortbar-label">Sắp xếp theo</span>
        <button
          type="button"
          className={sort === 'newest' ? 'chip is-active' : 'chip'}
          aria-pressed={sort === 'newest'}
          onClick={() => updateParams({ sort: 'newest' })}
        >
          Mới nhất
        </button>
        <button
          type="button"
          className={sort === 'best_selling' ? 'chip is-active' : 'chip'}
          aria-pressed={sort === 'best_selling'}
          onClick={() => updateParams({ sort: 'best_selling' })}
        >
          Bán chạy
        </button>
        <select
          className={PRICE_SORTS.includes(sort) ? 'chip-select is-active' : 'chip-select'}
          value={PRICE_SORTS.includes(sort) ? sort : ''}
          onChange={(event) => updateParams({ sort: event.target.value })}
          aria-label="Sắp xếp theo giá"
        >
          <option value="" disabled>
            Giá
          </option>
          <option value="price_asc">Giá: Thấp đến Cao</option>
          <option value="price_desc">Giá: Cao đến Thấp</option>
        </select>
        {meta && <span className="sortbar-count">{formatNumber(meta.total)} sản phẩm</span>}
      </div>

      {products.error && <ErrorMessage message={products.error} onRetry={products.reload} />}
      {products.loading && !products.data && <ProductGridSkeleton count={12} />}

      {products.data &&
        (list.length === 0 ? (
          <div className="empty panel">
            <h2>Không tìm thấy sản phẩm phù hợp</h2>
            <p>Hãy thử từ khoá ngắn hơn, kiểm tra chính tả hoặc chọn một danh mục khác.</p>
            <Link to="/" className="btn btn-primary">
              Xem tất cả sản phẩm
            </Link>
          </div>
        ) : (
          <div className={products.loading ? 'is-refreshing' : undefined}>
            <ul className="grid">
              {list.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </ul>
            <Pagination
              meta={meta}
              disabled={products.loading}
              onPageChange={(next) => {
                updateParams({ page: next })
                document.getElementById('products')?.scrollIntoView()
              }}
            />
          </div>
        ))}
    </>
  )
}
