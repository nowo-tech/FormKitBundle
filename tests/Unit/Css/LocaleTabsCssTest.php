<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * 2.13.2: locale-tabs.css styles the heading (title size, muted help, label gap) with UiKit tokens and fallbacks.
 */
final class LocaleTabsCssTest extends TestCase
{
    public function testHeadingRulesUseUiKitTokensWithFallbacks(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Resources/public/locale-tabs.css');

        self::assertStringContainsString('--nfk-locale-tabs-muted: var(--nowo-ui-help-text, var(--nowo-ui-muted, rgba(0, 0, 0, 0.6)));', $css);
        self::assertStringContainsString('gap: var(--nfk-locale-tabs-label-gap, var(--nowo-ui-form-label-gap, 0.5rem));', $css);
        self::assertMatchesRegularExpression('/\.nowo-form-kit-locale-tabs__title \{[^}]*font-size: var\(--nfk-locale-tabs-title-size, 0\.875rem\);[^}]*color: var\(--nowo-ui-text, inherit\);/s', $css);
        self::assertMatchesRegularExpression('/\.nowo-form-kit-locale-tabs__help \{[^}]*font-size: var\(--nfk-locale-tabs-help-size, 0\.75rem\);[^}]*color: var\(--nfk-locale-tabs-muted\);/s', $css);
        self::assertMatchesRegularExpression('/\.nowo-form-kit-locale-tabs__heading \{[^}]*flex-direction: column;/s', $css);
    }
}
