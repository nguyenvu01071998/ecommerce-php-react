const priceFormatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })
const numberFormatter = new Intl.NumberFormat('vi-VN')
const dateFormatter = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' })

// API trả giá dạng chuỗi "199000.00"; chỉ chuyển sang số để HIỂN THỊ, không dùng để tính tiền thật
export const formatPrice = (value) => priceFormatter.format(Number(value))
export const formatNumber = (value) => numberFormatter.format(Math.round(Number(value)))
export const formatDate = (value) => dateFormatter.format(new Date(value))

// "Đã bán 1,2k": rút gọn số lượng từ 1000 trở lên theo kiểu quen thuộc ở Việt Nam
export function formatSold(count) {
  if (count < 1000) return String(count)
  const thousands = Math.floor(count / 100) / 10
  return `${String(thousands).replace('.', ',')}k`
}

export const STATUS_LABELS = {
  pending: 'Chờ thanh toán',
  paid: 'Đã thanh toán',
  shipped: 'Đã giao',
  cancelled: 'Đã huỷ',
}

// Phản chiếu luồng trạng thái của backend để chỉ hiện lựa chọn hợp lệ. Server vẫn là nơi quyết định cuối cùng.
export const STATUS_TRANSITIONS = {
  pending: ['paid', 'cancelled'],
  paid: ['shipped', 'cancelled'],
  shipped: [],
  cancelled: [],
}
