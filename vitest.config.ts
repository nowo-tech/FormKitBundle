/**
 * Vitest configuration for browser-oriented unit tests under `src/Resources/assets/src/`,
 * the opt-in Stimulus peers under `src/Resources/assets/stimulus-peers/` and the IIFE enhancers under
 * `src/Resources/assets/enhancers/` (2.22 character counter).
 */
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: [
      'src/Resources/assets/src/**/*.test.ts',
      'src/Resources/assets/stimulus-peers/**/*.test.ts',
      'src/Resources/assets/enhancers/**/*.test.ts',
    ],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html'],
      reportsDirectory: 'coverage-ts',
      include: [
        'src/Resources/assets/src/**/*.ts',
        'src/Resources/assets/stimulus-peers/**/*.ts',
        'src/Resources/assets/enhancers/**/*.ts',
      ],
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

