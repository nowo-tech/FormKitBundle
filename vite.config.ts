/**
 * Vite build for the Form Kit help-modal IIFE (`help-modal.js` → `src/Resources/public/`).
 * Injects `__FORM_KIT_HELP_MODAL_BUILD_TIME__` for console diagnostics.
 */
import { defineConfig } from 'vite';

export default defineConfig({
  define: {
    __FORM_KIT_HELP_MODAL_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
  },
  build: {
    outDir: 'src/Resources/public',
    emptyOutDir: false,
    rollupOptions: {
      input: 'src/Resources/assets/src/help-modal.ts',
      output: {
        format: 'iife',
        entryFileNames: 'help-modal.js',
      },
    },
    minify: true,
    sourcemap: false,
  },
});

