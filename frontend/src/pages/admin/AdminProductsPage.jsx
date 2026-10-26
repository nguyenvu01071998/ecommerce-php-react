import { useState } from 'react'
import { adminApi, categoriesApi, productsApi } from '../../api/services'
import { EmptyState, ErrorMessage, Loading } from '../../components/StatusViews'
import Pagination from '../../components/Pagination'
import { useApi } from '../../hooks/useApi'
import { getErrorMessage } from '../../utils/errors'
import { formatPrice } from '../../utils/format'
import ProductForm from './ProductForm'

export default function AdminProductsPage() {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  // null = ẩn form, 'new' = thêm mới, object = đang sửa sản phẩm đó
  const [editing, setEditing] = useState(null)
  const [actionError, setActionError] = useState('')
  const [notice, setNotice] = useState('')

  const products = useApi(
    (signal) => productsApi.list({ search, page, per_page: 10, sort: 'newest' }, signal),
    [search, page],
  )
  const categories = useApi((signal) => categoriesApi.list(signal), [])

  const handleSaved = () => {
    setEditing(null)
    setNotice('Đã lưu sản phẩm.')
    products.reload()
  }

  const handleDelete = async (product) => {
    if (!window.confirm(`Xoá sản phẩm "${product.name}"?`)) return
    setActionError('')
    setNotice('')
    try {
      await adminApi.deleteProduct(product.id)
      setNotice(`Đã xoá "${product.name}".`)
      products.reload()
    } catch (err) {
      // vd 409: sản phẩm đã có trong đơn hàng
      setActionError(getErrorMessage(err))
    }
  }

  const list = products.data?.data ?? []

  return (
    <>
      <div className="toolbar">
        <form
          className="search"
          role="search"
          onSubmit={(event) => {
            event.preventDefault()
            setPage(1)
            setSearch(new FormData(event.currentTarget).get('search').trim())
          }}
        >
          <input type="search" name="search" placeholder="Tìm sản phẩm..." aria-label="Tìm sản phẩm" maxLength={100} />
          <button type="submit" className="btn">
            Tìm
          </button>
        </form>
        <button type="button" className="btn btn-primary" onClick={() => setEditing('new')}>
          + Thêm sản phẩm
        </button>
      </div>

      {notice && (
        <p className="alert alert-success" role="status">
          {notice}
        </p>
      )}
      {actionError && (
        <p className="alert alert-error" role="alert">
          {actionError}
        </p>
      )}

      {editing && categories.data && (
        <ProductForm
          key={editing === 'new' ? 'new' : editing.id}
          product={editing === 'new' ? null : editing}
          categories={categories.data.data}
          onSaved={handleSaved}
          onCancel={() => setEditing(null)}
        />
      )}

      {products.error && <ErrorMessage message={products.error} onRetry={products.reload} />}
      {products.loading && !products.data && <Loading />}

      {products.data &&
        (list.length === 0 ? (
          <EmptyState>Không có sản phẩm nào.</EmptyState>
        ) : (
          <div className={products.loading ? 'is-refreshing' : undefined}>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>
                      <span className="sr-only">Ảnh</span>
                    </th>
                    <th>Tên</th>
                    <th>Danh mục</th>
                    <th className="num">Giá</th>
                    <th className="num">Kho</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {list.map((product) => (
                    <tr key={product.id}>
                      <td>{product.id}</td>
                      <td>
                        <img src={product.image_url} alt="" width="40" height="40" className="thumb" loading="lazy" />
                      </td>
                      <td>{product.name}</td>
                      <td>{product.category?.name}</td>
                      <td className="num">{formatPrice(product.price)}</td>
                      <td className="num">{product.stock}</td>
                      <td className="row-actions">
                        <button
                          type="button"
                          className="btn btn-small"
                          onClick={() => {
                            setNotice('')
                            setEditing(product)
                            window.scrollTo({ top: 0 })
                          }}
                        >
                          Sửa
                        </button>
                        <button type="button" className="btn btn-small btn-danger" onClick={() => handleDelete(product)}>
                          Xoá
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Pagination meta={products.data.meta} disabled={products.loading} onPageChange={setPage} />
          </div>
        ))}
    </>
  )
}
