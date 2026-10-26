import { useId } from 'react'

/**
 * Ô nhập có nhãn và thông báo lỗi. `error` là mảng chuỗi lỗi của Laravel cho trường đó.
 * Lỗi được gắn với input qua aria-describedby để trình đọc màn hình đọc được.
 */
export default function FormField({ label, error, as: Tag = 'input', children, ...props }) {
  const id = useId()
  const errorId = `${id}-error`

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <Tag id={id} aria-invalid={Boolean(error)} aria-describedby={error ? errorId : undefined} {...props}>
        {children}
      </Tag>
      {error && (
        <p id={errorId} className="field-error">
          {error[0]}
        </p>
      )}
    </div>
  )
}
