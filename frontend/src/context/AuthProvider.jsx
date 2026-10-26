import { useCallback, useEffect, useMemo, useState } from 'react'
import { authApi } from '../api/services'
import { setUnauthorizedHandler, tokenStorage } from '../api/client'
import { AuthContext } from './authContext'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  // true trong lúc đang xác minh token đã lưu với server. ProtectedRoute phải đợi, nếu không sẽ đá người
  // đã đăng nhập về trang login mỗi lần F5.
  const [initializing, setInitializing] = useState(() => Boolean(tokenStorage.get()))

  useEffect(() => {
    // Interceptor báo token hết hạn -> xoá user, ProtectedRoute sẽ tự chuyển về /login
    setUnauthorizedHandler(() => setUser(null))
  }, [])

  useEffect(() => {
    if (!tokenStorage.get()) return

    authApi
      .me()
      .then(({ user }) => setUser(user))
      .catch(() => tokenStorage.clear())
      .finally(() => setInitializing(false))
  }, [])

  const login = useCallback(async (credentials) => {
    const { user, token } = await authApi.login(credentials)
    tokenStorage.set(token)
    setUser(user)
    return user
  }, [])

  const register = useCallback(async (payload) => {
    const { user, token } = await authApi.register(payload)
    tokenStorage.set(token)
    setUser(user)
    return user
  }, [])

  const logout = useCallback(async () => {
    try {
      await authApi.logout() // thu hồi token trên server
    } catch {
      /* dù server lỗi vẫn đăng xuất phía client */
    }
    tokenStorage.clear()
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({ user, initializing, isAdmin: user?.role === 'admin', login, register, logout }),
    [user, initializing, login, register, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
