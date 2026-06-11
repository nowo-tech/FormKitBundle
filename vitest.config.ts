/**
 * Vitest configuration for browser-oriented unit tests under `src/Resources/assets/src/`.
 */
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['src/Resources/assets/src/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html'],
      reportsDirectory: 'coverage-ts',
      include: ['src/Resources/assets/src/**/*.ts'],
      exclude: ['src/Resources/assets/src/**/*.test.ts'],
      thresholds: {
        lines: 100,
        branches: 100,
        functions: 100,
        statements: 100,
      },
    },
  },
});

