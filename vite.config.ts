/**
 * Vite build for the Form Kit IIFE scripts in `src/Resources/public/`:
 * - default mode: `help-modal.js` (injects `__FORM_KIT_HELP_MODAL_BUILD_TIME__` for console diagnostics);
 * - `--mode char-counter` (2.22): `char-counter.js` from `src/Resources/assets/enhancers/`.
 * An IIFE build has one entry, so `pnpm run build` runs Vite once per script.
 */
import { createHash } from 'node:crypto';
import { readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { defineConfig } from 'vite';

/** Every directory an entry bundles (help-modal: src/, char-counter: enhancers/). */
const BUNDLED_SOURCE_DIRS = ['src/Resources/assets/src', 'src/Resources/assets/enhancers'];

/**
 * Deterministic source hash: a hash of the TypeScript/CSS sources and pnpm-lock.yaml. It only changes when the code or
 * the dependencies change; it is the change detector of buildTimeLabel() below.
 */
function sourceBuildId(dirs: string[]): string {
  const hash = createHash('sha256');
  for (const dir of dirs) {
  const root = new URL(`${dir}/`, import.meta.url);
  const files = (readdirSync(root, { recursive: true }) as string[])
    .filter((file) => !file.includes('.test.') && statSync(new URL(file, root)).isFile())
    .sort();
  for (const file of files) {
    hash.update(`${dir}/${file}`).update(readFileSync(new URL(file, root)));
  }
  }
  hash.update(readFileSync(new URL('pnpm-lock.yaml', import.meta.url)));

  return hash.digest('hex').slice(0, 12);
}

/**
 * Readable, reproducible build time for the "script loaded" console line ("2026-11-23 14:05 UTC").
 * The committed `build-info.json` ({ sourceHash, builtAt }) keeps it stable: when the source hash equals the stored
 * one, the stored `builtAt` is reused (a rebuild of unchanged sources never rewrites the committed assets); when it
 * differs, the new hash is stored with the current UTC minute. Commit `build-info.json` with the rebuilt assets: a CI
 * dist-freshness rebuild of a clean checkout then reproduces the same bytes, and fails visibly if it was forgotten.
 */
function buildTimeLabel(dirs: string[]): string {
  const sourceHash = sourceBuildId(dirs);
  const file = new URL('build-info.json', import.meta.url);
  let info: { sourceHash?: string; builtAt?: string } = {};
  try {
    info = JSON.parse(readFileSync(file, 'utf8')) as typeof info;
  } catch {
    // Missing or unreadable: written below.
  }
  if (info.sourceHash !== sourceHash || typeof info.builtAt !== 'string') {
    info = { sourceHash, builtAt: `${new Date().toISOString().slice(0, 16)}Z` };
    writeFileSync(file, `${JSON.stringify(info, null, 2)}\n`);
    console.warn(`build-info.json updated (${info.builtAt}): commit it together with the rebuilt assets.`);
  }

  return (info.builtAt as string).replace('T', ' ').replace('Z', ' UTC');
}

const charCounter = defineConfig({
  define: {
    __FORM_KIT_HELP_MODAL_BUILD_TIME__: JSON.stringify(buildTimeLabel(BUNDLED_SOURCE_DIRS)),
  },
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
    __FORM_KIT_HELP_MODAL_BUILD_TIME__: JSON.stringify(buildTimeLabel(BUNDLED_SOURCE_DIRS)),
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
