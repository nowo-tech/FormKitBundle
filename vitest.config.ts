/**
 * Vitest configuration for browser-oriented unit tests under `src/Resources/assets/src/`
 * and the opt-in Stimulus peers under `src/Resources/assets/stimulus-peers/`.
 */
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['src/Resources/assets/src/**/*.test.ts', 'src/Resources/assets/stimulus-peers/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html'],
      reportsDirectory: 'coverage-ts',
      include: ['src/Resources/assets/src/**/*.ts', 'src/Resources/assets/stimulus-peers/**/*.ts'],
      exclude: ['src/Resources/assets/**/*.test.ts'],
      thresholds: {
        lines: 100,
        branches: 100,
        functions: 100,
        statements: 100,
      },
    },
  },
});

