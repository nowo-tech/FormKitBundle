import { expect, type Page } from '@playwright/test';

/** Fills step 1 of the multi-step wizard (labels/placeholders come from the Form Kit convention keys). */
export async function fillContactStep(page: Page): Promise<void> {
  await page.goto('/en/multistep?reset=1');
  await expect(page.locator('.nowo-ui-wizard-steps__item--active')).toContainText('Contact');
  await page.getByLabel('Full name').fill('Ada Lovelace');
  await page.getByLabel('Email').fill('ada@example.com');
  await page.getByRole('button', { name: 'Next' }).click();
  await expect(page.locator('.nowo-ui-wizard-steps__item--active')).toContainText('Address');
}

/** The Symfony toolbar is not part of the demo platform: screenshots hide it. */
export async function hideToolbar(page: Page): Promise<void> {
  await page.addStyleTag({ content: '.sf-toolbar,.sf-minitoolbar,[id^="sfwdt"]{display:none!important}' });
  await page.waitForTimeout(300);
}
