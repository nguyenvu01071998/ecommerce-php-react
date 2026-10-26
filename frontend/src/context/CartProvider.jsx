import { useCallback, useEffect, useMemo, useReducer } from 'react'
import { CartContext } from './cartContext'

const STORAGE_KEY = 'cart'
const MAX_PER_ITEM = 100 // khớp luật validate của backend

// Dữ liệu trong localStorage không đáng tin (người dùng có thể sửa tay) nên kiểm tra kỹ trước khi dùng
function loadCart() {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY))
    if (!Array.isArray(parsed)) return []
    return parsed.filter(
      (item) =>
        Number.isInteger(item?.id) &&
        typeof item.name === 'string' &&
        Number.isInteger(item.quantity) &&
        item.quantity > 0,
    )
  } catch {
    return []
  }
}

const clampQuantity = (quantity, stock) =>
  Math.max(1, Math.min(Number(quantity) || 1, MAX_PER_ITEM, stock > 0 ? stock : 1))

function cartReducer(items, action) {
  switch (action.type) {
    case 'add': {
      const { product, quantity } = action
      const existing = items.find((item) => item.id === product.id)
      if (existing) {
        return items.map((item) =>
          item.id === product.id
            ? { ...item, stock: product.stock, quantity: clampQuantity(item.quantity + quantity, product.stock) }
            : item,
        )
      }
      // Giá lưu trong giỏ chỉ để HIỂN THỊ. Khi đặt hàng server tự lấy giá từ database.
      return [
        ...items,
        {
          id: product.id,
          name: product.name,
          price: product.price,
          image_url: product.image_url,
          stock: product.stock,
          quantity: clampQuantity(quantity, product.stock),
        },
      ]
    }
    case 'setQuantity':
      return items.map((item) =>
        item.id === action.id ? { ...item, quantity: clampQuantity(action.quantity, item.stock) } : item,
      )
    case 'remove':
      return items.filter((item) => item.id !== action.id)
    case 'clear':
      return []
    default:
      return items
  }
}

export function CartProvider({ children }) {
  const [items, dispatch] = useReducer(cartReducer, undefined, loadCart)

  // Mỗi lần giỏ đổi thì lưu lại để F5 hoặc mở lại trình duyệt vẫn còn
  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
    } catch {
      /* bỏ qua */
    }
  }, [items])

  const addItem = useCallback((product, quantity = 1) => dispatch({ type: 'add', product, quantity }), [])
  const setQuantity = useCallback((id, quantity) => dispatch({ type: 'setQuantity', id, quantity }), [])
  const removeItem = useCallback((id) => dispatch({ type: 'remove', id }), [])
  const clear = useCallback(() => dispatch({ type: 'clear' }), [])

  const value = useMemo(
    () => ({
      items,
      count: items.reduce((sum, item) => sum + item.quantity, 0),
      subtotal: items.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0),
      addItem,
      setQuantity,
      removeItem,
      clear,
    }),
    [items, addItem, setQuantity, removeItem, clear],
  )

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
