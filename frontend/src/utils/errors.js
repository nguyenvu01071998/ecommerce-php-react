import axios from 'axios'

// Request bị huỷ (đổi trang/unmount) không phải lỗi thật, không được hiển thị
export const isCanceled = (error) => axios.isCancel(error)

export function getErrorMessage(error) {
  if (!error.response) {
    return 'Không kết nối được máy chủ. Vui lòng thử lại.'
  }
  if (error.response.status === 429) {
    return 'Bạn thao tác quá nhanh. Vui lòng đợi một lát rồi thử lại.'
  }
  return error.response.data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.'
}

// Lỗi validate (422) của Laravel: { errors: { email: ['...'], 'items.0.quantity': ['...'] } }
export function getFieldErrors(error) {
  return error.response?.status === 422 ? (error.response.data?.errors ?? {}) : {}
}

export function flattenErrors(fieldErrors) {
  return Object.values(fieldErrors).flat()
}
