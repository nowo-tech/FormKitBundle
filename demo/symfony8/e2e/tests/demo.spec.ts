import { test, expect } from '@playwright/test';
import { fillContactStep } from './helpers';

/** REQ-DEMO-013 — Form Kit demo happy path (convention labels, submit, multi-step wizard, locale tabs). */
test.describe('Form Kit demo', () => {
  test('example form renders convention labels and placeholders and submits', async ({ page }) => {
    const response = await page.goto('/en/');
    expect(response?.ok()).toBeTruthy();
    await page.getByRole('link', { name: 'Open Example' }).click();
    await expect(page).toHaveURL(/\/en\/example-form$/);
    const name = page.getByLabel('Your name');
    await expect(name).toHaveAttribute('placeholder', 'John Doe');
    await name.fill('Ada Lovelace');
    await page.getByLabel('Email').fill('ada@example.com');
    await page.getByLabel('Subject').fill('Form Kit');
    await page.getByLabel('Message').fill('Labels, placeholders and help come from translation keys.');
    await page.getByRole('button', { name: 'Send', exact: true }).click();
    await expect(page.locator('.alert-success')).toContainText('Ada Lovelace');
  });

  test('multi-step wizard walks contact → address → confirm', async ({ page }) => {
    await fillContactStep(page);
    await page.getByLabel('Street').fill('Calle Mayor');
    await page.getByLabel('Number').fill('1');
    await page.getByLabel('Floor').fill('2A');
    await page.getByLabel('Postal code').fill('28013');
    await page.getByLabel('City').fill('Madrid');
    await page.getByLabel('Province').fill('Madrid');
    await page.getByRole('button', { name: 'Next' }).click();
    await expect(page.locator('.nav-pills .nav-link.active')).toContainText('Confirm');
    await page.getByRole('button', { name: 'Finish' }).click();
    await expect(page.getByRole('heading', { name: /Summary/ })).toBeVisible();
    await expect(page.getByText('Ada Lovelace').first()).toBeVisible();
  });

  test('locale tabs flag the invalid languages', async ({ page }) => {
    await page.goto('/en/locale-tabs?errors=1');
    await expect(page.getByRole('tab').first()).toBeVisible();
    await expect(page.locator('.invalid-feedback, .form-error-message, .nowo-ui-field__error').first()).toBeVisible();
  });
});
