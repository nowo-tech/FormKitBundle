/**
 * Vite build for the Form Kit IIFE scripts in `src/Resources/public/`:
 * - default mode: `help-modal.js` (injects `__FORM_KIT_HELP_MODAL_BUILD_TIME__` for console diagnostics);
 * - `--mode char-counter` (2.22): `char-counter.js` from `src/Resources/assets/enhancers/`.
 * An IIFE build has one entry, so `pnpm run build` runs Vite once per script.
 */
import { createHash } from 'node:crypto';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { defineConfig } from 'vite';

/**
 * Deterministic build id: a hash of the TypeScript/CSS sources and pnpm-lock.yaml. It only changes when the code or
 * the dependencies change, so rebuilding unchanged sources (e.g. in `make release-check`) leaves the committed bundle
 * byte-identical (the former wall-clock stamp rewrote it on every build).
 */
function sourceBuildId(dir: string): string {
  const root = new URL(`${dir}/`, import.meta.url);
  const hash = createHash('sha256');
  const files = (readdirSync(root, { recursive: true }) as string[])
    .filter((file) => !file.includes('.test.') && statSync(new URL(file, root)).isFile())
    .sort();
  for (const file of files) {
    hash.update(file).update(readFileSync(new URL(file, root)));
  }
  hash.update(readFileSync(new URL('pnpm-lock.yaml', import.meta.url)));

  return hash.digest('hex').slice(0, 12);
}

const charCounter = defineConfig({
  build: {
    outDir: 'src/Resources/public',
    emptyOutDir: false,
    rollupOptions: {
      input: 'src/Resources/assets/enhancers/char-counter-entry.ts',
      output: {
        format: 'iife',
        entryFileNames: 'char-counter.js',
      },
    },
    minify: true,
    sourcemap: false,
  },
});

const helpModal = defineConfig({
  define: {
    __FORM_KIT_HELP_MODAL_BUILD_TIME__: JSON.stringify(sourceBuildId('src/Resources/assets/src')),
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

export default defineConfig(({ mode }) => (mode === 'char-counter' ? charCounter : helpModal));
