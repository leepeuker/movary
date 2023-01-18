import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  base: '/frontend',
  server: {
    host: '0.0.0.0',
    port: 5173,
    origin: 'http://localhost:5173',
  },
  plugins: [react()],
  build: {
    outDir: path.resolve('../public/frontend'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: '/src/main.tsx',
    }
  }
})
