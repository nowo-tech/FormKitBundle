<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function count;
use function dirname;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_split;
use function sprintf;
use function strip_tags;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Regression: the German demo catalogue shipped about 200 English copies and the French one about 30.
 * A message of the demo may equal the English text only when it is language-neutral: at most two words
 * once markup and inline code are removed (product names, identifiers, "Contact"), or listed below.
 */
final class DemoCatalogueNotEnglishCopyTest extends TestCase
{
    /** English values that are identical in the language on purpose (product names, sample data). */
    private const array ALLOWED_KEYS = [
        'demo_nav.brand',
        'data_transformers_demo.tags.placeholder',
        'demo_pages.dropzone.heading',
        'demo_pages.cropper.heading',
        'demo_contact.website.placeholder',
    ];

    /** @return iterable<string, array{string}> */
    public static function localeProvider(): iterable
    {
        foreach (['de', 'es', 'fr'] as $locale) {
            yield $locale => [$locale];
        }
    }

    #[Test]
    #[DataProvider('localeProvider')]
    public function demoMessagesAreNotEnglishCopies(string $locale): void
    {
        $english = self::flatten(self::load('en'));
        $copies  = [];
        foreach (self::flatten(self::load($locale)) as $key => $value) {
            if (($english[$key] ?? null) !== $value || in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }
            if (!self::isLanguageNeutral($value)) {
                $copies[] = sprintf('%s: "%s"', $key, $value);
            }
        }

        self::assertSame([], $copies, sprintf("Untranslated (English) demo messages in %s:\n%s", $locale, implode("\n", $copies)));
    }

    private static function isLanguageNeutral(string $value): bool
    {
        $text  = (string) preg_replace('~<code>.*?</code>~s', '', $value);
        $words = preg_split('/\s+/', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_filter($words, static fn (string $word): bool => preg_match('/\pL{2,}/u', $word) === 1);

        return count($words) <= 2;
    }

    /** @return array<mixed> */
    private static function load(string $locale): array
    {
        $data = Yaml::parseFile(dirname(__DIR__, 3) . '/demo/symfony8/translations/messages.' . $locale . '.yaml');
        self::assertIsArray($data);

        return $data;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, string>
     */
    private static function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $flat += self::flatten($value, $prefix . $key . '.');
            } elseif (is_string($value)) {
                $flat[$prefix . $key] = $value;
            }
        }

        return $flat;
    }
}
