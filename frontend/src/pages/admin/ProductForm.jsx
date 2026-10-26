import { useState } from 'react'
import { adminApi } from '../../api/services'
import FormField from '../../components/FormField'
import { getErrorMessage, getFieldErrors } from '../../utils/errors'

/**
 * Form thêm/sửa sản phẩm. `product` có giá trị = đang sửa, null = đang thêm mới.
 * Component cha nên đặt `key` theo id sản phẩm để form được khởi tạo lại khi đổi sản phẩm cần sửa.
 */
export default function ProductForm({ product, categories, onSaved, onCancel }) {
  const [values, setValues] = useState({
    name: product?.name ?? '',
    description: product?.description ?? '',
    price: product ? String(Math.round(Number(product.price))) : '',
    stock: product ? String(product.stock) : '0',
    image_url: product?.image_url ?? '',
    category_id: product ? String(product.category.id) : '',
  })
  const [fieldErrors, setFieldErrors] = useState({})
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const bind = (name) => ({
    name,
    value: values[name],
    onChange: (event) => setValues((previous) => ({ ...previous, [name]: event.target.value })),
    error: fieldErrors[name],
  })

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSubmitting(true)
    setError('')
    setFieldErrors({})

    const payload = {
      name: values.name,
      description: values.description || null,
      price: values.price === '' ? null : Number(values.price),
      stock: values.stock === '' ? null : Number(values.stock),
      image_url: values.image_url || null,
      category_id: values.category_id ? Number(values.category_id) : null,
    }

    try {
      if (product) {
        await adminApi.updateProduct(product.id, payload)
      } else {
        await adminApi.createProduct(payload)
      }
      onSaved()
    } catch (err) {
      setFieldErrors(getFieldErrors(err))
      setError(getErrorMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="panel" onSubmit={handleSubmit} noValidate>
      <h2>{product ? `Sửa sản phẩm #${product.id}` : 'Thêm sản phẩm'}</h2>

      <FormField label="Tên sản phẩm" required maxLength={255} {...bind('name')} />
      <FormField label="Giá (VNĐ)" type="number" min="0" step="1000" required {...bind('price')} />
      <FormField label="Tồn kho" type="number" min="0" step="1" required {...bind('stock')} />
      <FormField label="Danh mục" as="select" required {...bind('category_id')}>
        <option value="">-- Chọn danh mục --</option>
        {categories.map((category) => (
          <option key={category.id} value={category.id}>
            {category.name}
          </option>
        ))}
      </FormField>
      <FormField label="Link ảnh (http/https)" type="url" {...bind('image_url')} />
      <FormField label="Mô tả" as="textarea" rows={4} maxLength={5000} {...bind('description')} />

      {error && Object.keys(fieldErrors).length === 0 && (
        <p className="alert alert-error" role="alert">
          {error}
        </p>
      )}

      <div className="form-actions">
        <button type="submit" className="btn btn-primary" disabled={submitting}>
          {submitting ? 'Đang lưu...' : 'Lưu'}
        </button>
        <button type="button" className="btn" onClick={onCancel} disabled={submitting}>
          Huỷ
        </button>
      </div>
    </form>
  )
}
