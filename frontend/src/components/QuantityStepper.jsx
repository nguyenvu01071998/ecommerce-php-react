import { MinusIcon, PlusIcon } from './icons'

/**
 * Ô chọn số lượng dạng [-] 1 [+]. Luôn giữ giá trị trong khoảng [min, max].
 * Nút bấm cao 40px+ và cách nhau để dễ chạm trên điện thoại.
 */
export default function QuantityStepper({ value, min = 1, max, onChange, label }) {
  const clamp = (n) => Math.max(min, Math.min(Number.isFinite(n) ? n : min, max))

  return (
    <div className="stepper" role="group" aria-label={label}>
      <button
        type="button"
        className="stepper-btn"
        onClick={() => onChange(clamp(value - 1))}
        disabled={value <= min}
        aria-label="Giảm số lượng"
      >
        <MinusIcon size={16} />
      </button>
      <input
        className="stepper-input"
        type="number"
        inputMode="numeric"
        min={min}
        max={max}
        value={value}
        onChange={(event) => onChange(clamp(parseInt(event.target.value, 10)))}
        aria-label={label}
      />
      <button
        type="button"
        className="stepper-btn"
        onClick={() => onChange(clamp(value + 1))}
        disabled={value >= max}
        aria-label="Tăng số lượng"
      >
        <PlusIcon size={16} />
      </button>
    </div>
  )
}
