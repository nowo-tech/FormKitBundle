import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { fillContactStep, waitForToolbar } from './helpers';

/** REQ-DEMO-013 — README screenshots (captured as PNG, converted to WebP by scripts/to-webp.mjs). */
const outDir = process.env.SCREENSHOT_DIR
  ? resolve(process.env.SCREENSHOT_DIR)
  : resolve(__dirname, '../../../../docs/images/demo');

test.use({ viewport: { width: 1280, height: 960 } });

test.beforeAll(() => {
  mkdirSync(outDir, { recursive: true });
});

test.describe('Form Kit screenshots', () => {
  test('multistep — wizard step 2 with convention labels and placeholders', async ({ page }) => {
    await fillContactStep(page);
    await page.getByLabel('Street').fill('Calle Mayor');
    await page.getByLabel('Number').fill('1');
    await page.getByLabel('City').fill('Madrid');
    await page.getByLabel('City').blur();
    await waitForToolbar(page);
    await page.screenshot({ path: resolve(outDir, 'multistep.png') });
  });

  test('locale-tabs — per-locale tabs with invalid languages flagged', async ({ page }) => {
    await page.goto('/en/locale-tabs?errors=1');
    await expect(page.getByRole('tab').first()).toBeVisible();
    await waitForToolbar(page);
    await page.screenshot({ path: resolve(outDir, 'locale-tabs.png') });
  });
});
