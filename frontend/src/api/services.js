import client from './client'

// Bỏ các tham số rỗng để URL gọn ("?search=" vô nghĩa)
const clean = (params = {}) =>
  Object.fromEntries(Object.entries(params).filter(([, value]) => value !== '' && value != null))

const data = (response) => response.data

export const authApi = {
  register: (payload) => client.post('/register', payload).then(data),
  login: (payload) => client.post('/login', payload).then(data),
  logout: () => client.post('/logout').then(data),
  me: () => client.get('/me').then(data),
}

export const productsApi = {
  list: (params, signal) => client.get('/products', { params: clean(params), signal }).then(data),
  get: (id, signal) => client.get(`/products/${id}`, { signal }).then(data),
}

export const categoriesApi = {
  list: (signal) => client.get('/categories', { signal }).then(data),
}

export const ordersApi = {
  list: (params, signal) => client.get('/orders', { params: clean(params), signal }).then(data),
  // Chỉ gửi product_id và quantity: giá và tổng tiền do server tính
  create: (items) => client.post('/orders', { items }).then(data),
}

export const adminApi = {
  createProduct: (payload) => client.post('/admin/products', payload).then(data),
  updateProduct: (id, payload) => client.put(`/admin/products/${id}`, payload).then(data),
  deleteProduct: (id) => client.delete(`/admin/products/${id}`),
  listOrders: (params, signal) =>
    client.get('/admin/orders', { params: clean(params), signal }).then(data),
  updateOrderStatus: (id, status) =>
    client.patch(`/admin/orders/${id}/status`, { status }).then(data),
}
