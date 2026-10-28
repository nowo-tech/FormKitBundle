import { expect, type Page } from '@playwright/test';

/** Fills step 1 of the multi-step wizard (labels/placeholders come from the Form Kit convention keys). */
export async function fillContactStep(page: Page): Promise<void> {
  await page.goto('/en/multistep?reset=1');
  await expect(page.locator('.nav-pills .nav-link.active')).toContainText('Contact');
  await page.getByLabel('Full name').fill('Ada Lovelace');
  await page.getByLabel('Email').fill('ada@example.com');
  await page.getByRole('button', { name: 'Next' }).click();
  await expect(page.locator('.nav-pills .nav-link.active')).toContainText('Address');
}

/** Waits for the Symfony WebProfiler toolbar so it is part of the capture. */
export async function waitForToolbar(page: Page): Promise<void> {
  await expect(page.locator('.sf-toolbar .sf-toolbarreset').first()).toBeVisible({ timeout: 15_000 });
  await page.waitForTimeout(300);
}
