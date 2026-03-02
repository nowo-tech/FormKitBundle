import { defineConfig } from 'vite';

export default defineConfig({
  base: '/build/',
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: 'assets/app.js',
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',
      },
    },
  },
});
