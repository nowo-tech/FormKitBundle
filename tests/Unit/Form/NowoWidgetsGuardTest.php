<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Controller\FormKitControllerTrait;
use Nowo\FormKitBundle\Form\FormKitTrait;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function dirname;
use function is_array;
use function is_string;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Guards {@see FormTypeMap::NOWO_WIDGETS}: every nowo-tech widget is mapped, suggested, has its helper trio and is
 * documented, and no nowo-tech FormType is mapped without being in the central list.
 */
final class NowoWidgetsGuardTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideNowoWidgets(): iterable
    {
        foreach (FormTypeMap::NOWO_WIDGETS as $key => [$package, $helper]) {
            yield $key => [$key, $package, $helper];
        }
    }

    #[DataProvider('provideNowoWidgets')]
    public function testWidgetIsMappedSuggestedAndHasHelpers(string $key, string $package, string $helper): void
    {
        $optional = $this->optionalTypes();
        self::assertArrayHasKey($key, $optional, sprintf('"%s" is missing from FormTypeMap::OPTIONAL.', $key));
        self::assertStringStartsWith('Nowo\\', $optional[$key]);

        self::assertArrayHasKey($package, $this->composerSuggest(), sprintf('%s is missing from composer.json "suggest".', $package));

        foreach ([FormKitTrait::class, FormOptionsTrait::class] as $trait) {
            $reflection = new ReflectionClass($trait);
            self::assertTrue($reflection->hasMethod($helper), sprintf('%s::%s() is missing.', $trait, $helper));
            self::assertTrue($reflection->hasMethod($helper . 'Field'), sprintf('%s::%sField() is missing.', $trait, $helper));
        }
        self::assertTrue(
            (new ReflectionClass(FormKitControllerTrait::class))->hasMethod($helper . 'FieldType'),
            sprintf('FormKitControllerTrait::%sFieldType() is missing.', $helper),
        );

        foreach (['docs/USAGE.md', 'docs/CONFIGURATION.md'] as $doc) {
            $row = sprintf('| `%s` |', $key);
            self::assertStringContainsString($row, $this->read($doc), sprintf('%s lacks the "%s" row of the nowo-tech widgets table.', $doc, $key));
        }
    }

    public function testEveryMappedNowoTypeIsInTheCentralList(): void
    {
        foreach ($this->optionalTypes() as $key => $fqcn) {
            if (str_starts_with($fqcn, 'Nowo\\')) {
                self::assertArrayHasKey($key, FormTypeMap::NOWO_WIDGETS, sprintf('"%s" (%s) is not in FormTypeMap::NOWO_WIDGETS.', $key, $fqcn));
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function optionalTypes(): array
    {
        /** @var array<string, string> $optional */
        $optional = (new ReflectionClass(FormTypeMap::class))->getConstant('OPTIONAL');

        return $optional;
    }

    /**
     * @return array<mixed>
     */
    private function composerSuggest(): array
    {
        $composer = json_decode($this->read('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $suggest = $composer['suggest'] ?? null;

        return is_array($suggest) ? $suggest : [];
    }

    private function read(string $relative): string
    {
        $content = file_get_contents(dirname(__DIR__, 3) . '/' . $relative);
        self::assertTrue(is_string($content));

        return $content;
    }
}
