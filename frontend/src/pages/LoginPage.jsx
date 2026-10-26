import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import FormField from '../components/FormField'
import { useAuth } from '../context/useAuth'
import { getErrorMessage, getFieldErrors } from '../utils/errors'

export default function LoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState({})

  // Quay lại trang đang định vào trước khi bị chuyển về /login
  const redirectTo = location.state?.from?.pathname || '/'

  if (user) return <Navigate to={redirectTo} replace />

  const handleSubmit = async (event) => {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSubmitting(true)
    setError('')
    setFieldErrors({})
    try {
      await login({ email: form.get('email'), password: form.get('password') })
      navigate(redirectTo, { replace: true })
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
        <h1>Đăng nhập</h1>
        <form onSubmit={handleSubmit} noValidate>
          <FormField label="Email" type="email" name="email" autoComplete="email" required error={fieldErrors.email} />
          <FormField
            label="Mật khẩu"
            type="password"
            name="password"
            autoComplete="current-password"
            required
            error={fieldErrors.password}
          />
          {error && Object.keys(fieldErrors).length === 0 && (
            <p className="alert alert-error" role="alert">
              {error}
            </p>
          )}
          <button type="submit" className="btn btn-primary btn-block" disabled={submitting}>
            {submitting ? 'Đang đăng nhập...' : 'Đăng nhập'}
          </button>
        </form>
        <p className="auth-switch">
          Bạn mới biết đến Ecommerce Demo? <Link to="/register">Đăng ký</Link>
        </p>
      </div>
    </div>
  )
}
