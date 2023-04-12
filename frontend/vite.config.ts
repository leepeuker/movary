import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react-swc'
import { resolve } from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
  ],
  base: '/frontend',
  server: {
    host: '0.0.0.0',
    port: 5173,
    origin: 'http://localhost:5173',
  },
  build: {
    outDir: resolve('../public/frontend'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: '/src/main.tsx',
    }
  }
})
