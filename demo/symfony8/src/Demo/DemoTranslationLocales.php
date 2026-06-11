<?php

declare(strict_types=1);

namespace App\Demo;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Demo helper: picks a random subset of translation locales per session so the A2lix translations
 * form shows a variable number of tabs (example of dynamic enabled_locales).
 *
 * The set is stored in session and reused across GET/POST so the form stays consistent.
 */
final class DemoTranslationLocales
{
    public const POOL = ['en', 'es', 'fr', 'de'];

    private const SESSION_KEY = 'form_kit_demo.translation_form_locales';

    /**
     * @return array{default_locale: string, enabled_locales: list<string>}
     */
    public static function forSession(SessionInterface $session): array
    {
        $stored = $session->get(self::SESSION_KEY);
        if (is_array($stored)
            && isset($stored['enabled_locales'], $stored['default_locale'])
            && is_array($stored['enabled_locales'])
            && is_string($stored['default_locale'])
        ) {
            /* @var array{default_locale: string, enabled_locales: list<string>} $stored */
            return $stored;
        }

        $enabledLocales = self::randomSubset();
        $defaultLocale  = self::pickDefaultLocale($enabledLocales);

        $data = [
            'default_locale'  => $defaultLocale,
            'enabled_locales' => $enabledLocales,
        ];
        $session->set(self::SESSION_KEY, $data);

        return $data;
    }

    public static function clear(SessionInterface $session): void
    {
        $session->remove(self::SESSION_KEY);
    }

    /**
     * @return list<string>
     */
    private static function randomSubset(): array
    {
        $pool  = self::POOL;
        $n     = count($pool);
        $count = random_int(2, $n);

        $indices = range(0, $n - 1);
        shuffle($indices);
        $picked = [];
        for ($i = 0; $i < $count; ++$i) {
            $picked[] = $pool[$indices[$i]];
        }
        sort($picked);

        return array_values($picked);
    }

    /**
     * @param list<string> $enabledLocales
     */
    private static function pickDefaultLocale(array $enabledLocales): string
    {
        if (in_array('en', $enabledLocales, true)) {
            return 'en';
        }

        return $enabledLocales[0];
    }
}
