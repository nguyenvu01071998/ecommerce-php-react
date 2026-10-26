import { useCallback, useEffect, useState } from 'react'
import { getErrorMessage, isCanceled } from '../utils/errors'

/**
 * Gọi API khi component mount hoặc khi `deps` đổi. Trả về { data, loading, error, reload }.
 * - Dùng AbortController: đổi trang/bộ lọc nhanh thì request cũ bị huỷ, không ghi đè kết quả mới (race condition).
 * - Giữ `data` cũ trong lúc tải lại để danh sách không bị nhấp nháy khi chuyển trang.
 */
export function useApi(fetcher, deps) {
  const [state, setState] = useState({ data: null, loading: true, error: null })
  const [version, setVersion] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setState((previous) => ({ ...previous, loading: true, error: null }))

    fetcher(controller.signal)
      .then((data) => setState({ data, loading: false, error: null }))
      .catch((error) => {
        if (isCanceled(error)) return
        setState({ data: null, loading: false, error: getErrorMessage(error) })
      })

    return () => controller.abort()
    // eslint-disable-next-line react-hooks/exhaustive-deps -- fetcher đổi mỗi lần render, chỉ phụ thuộc vào `deps`
  }, [...deps, version])

  const reload = useCallback(() => setVersion((v) => v + 1), [])

  return { ...state, reload }
}
