import { useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import FormField from '../components/FormField'
import { useAuth } from '../context/useAuth'
import { getErrorMessage, getFieldErrors } from '../utils/errors'

export default function RegisterPage() {
  const { user, register } = useAuth()
  const navigate = useNavigate()
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState({})

  if (user) return <Navigate to="/" replace />

  const handleSubmit = async (event) => {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSubmitting(true)
    setError('')
    setFieldErrors({})
    try {
      await register({
        name: form.get('name'),
        email: form.get('email'),
        password: form.get('password'),
        password_confirmation: form.get('password_confirmation'),
      })
      navigate('/', { replace: true })
    } catch (err) {
      setFieldErrors(getFieldErrors(err))
      setError(getErrorMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1>Đăng ký</h1>
        <form onSubmit={handleSubmit} noValidate>
          <FormField label="Họ tên" name="name" autoComplete="name" required error={fieldErrors.name} />
          <FormField label="Email" type="email" name="email" autoComplete="email" required error={fieldErrors.email} />
          <FormField
            label="Mật khẩu (tối thiểu 8 ký tự, có chữ và số)"
            type="password"
            name="password"
            autoComplete="new-password"
            required
            error={fieldErrors.password}
          />
          <FormField
            label="Nhập lại mật khẩu"
            type="password"
            name="password_confirmation"
            autoComplete="new-password"
            required
          />
          {error && Object.keys(fieldErrors).length === 0 && (
            <p className="alert alert-error" role="alert">
              {error}
            </p>
          )}
          <button type="submit" className="btn btn-primary btn-block" disabled={submitting}>
            {submitting ? 'Đang tạo tài khoản...' : 'Đăng ký'}
          </button>
        </form>
        <p className="auth-switch">
          Bạn đã có tài khoản? <Link to="/login">Đăng nhập</Link>
        </p>
      </div>
    </div>
  )
}
