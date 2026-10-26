import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: true, // lắng nghe 0.0.0.0 để truy cập được từ ngoài container
    port: 5173,
    watch: {
      // Chỉ bật trong Docker trên Windows (xem docker-compose.yml)
      usePolling: process.env.VITE_USE_POLLING === 'true',
    },
  },
})
