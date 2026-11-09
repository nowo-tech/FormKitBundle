<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\ImageCropAttributes;
use Nowo\UiKitBundle\Form\ImageCrop;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function constant;
use function json_decode;

use const JSON_THROW_ON_ERROR;
use const NAN;

/**
 * FormKit's own printer of UiKit 2.43's image crop contract must match `ImageCrop::attributes()` (minus the labels), and
 * reject exactly what UiKit rejects, with the same message.
 */
final class ImageCropAttributesTest extends TestCase
{
    public function testUiKitIsAvailableInDevelopment(): void
    {
        self::assertTrue(ImageCropAttributes::uiKitAvailable());
        foreach (['ATTRIBUTE', 'SHAPES', 'FORMATS', 'MAX_RANGE', 'DEFAULT_MAX'] as $name) {
            self::assertSame(constant(ImageCrop::class . '::' . $name), constant(ImageCropAttributes::class . '::' . $name), $name);
        }
    }

    public function testDefaultsWithoutUiKit(): void
    {
        self::assertSame([
            'data-nowo-ui-image-crop'         => '',
            'data-nowo-ui-image-crop-shape'   => 'square',
            'data-nowo-ui-image-crop-aspect'  => '1',
            'data-nowo-ui-image-crop-max'     => '1024',
            'data-nowo-ui-image-crop-format'  => 'auto',
            'data-nowo-ui-image-crop-quality' => '0.92',
        ], ImageCropAttributes::attributes([], null, false));
    }

    public function testEveryOptionWithoutUiKit(): void
    {
        self::assertSame([
            'data-nowo-ui-image-crop'             => '',
            'data-nowo-ui-image-crop-shape'       => 'circle',
            'data-nowo-ui-image-crop-aspect'      => '1',
            'data-nowo-ui-image-crop-max'         => '512',
            'data-nowo-ui-image-crop-format'      => 'webp',
            'data-nowo-ui-image-crop-min'         => '128',
            'data-nowo-ui-image-crop-max-bytes'   => '190000',
            'data-nowo-ui-image-crop-quality'     => '0.8',
            'data-nowo-ui-image-crop-bake-circle' => 'true',
            'data-nowo-ui-image-crop-preview'     => 'avatar-preview',
            'data-nowo-ui-image-crop-labels'      => '{"title":"Recorta tu foto","zoomStatus":"Zoom %percent%%","tooLarge":"Máx. %max%"}',
        ], ImageCropAttributes::attributes([
            'shape'       => 'circle',
            'aspect'      => '16/9',
            'max'         => 512,
            'min'         => 128,
            'format'      => 'webp',
            'quality'     => 0.8,
            'max_bytes'   => 190_000,
            'bake_circle' => true,
            'preview'     => 'avatar-preview',
            'labels'      => ['title' => 'Recorta tu foto', 'zoom_status' => 'Zoom %percent%%', 'too_large' => 'Máx. %max%'],
        ], null, false));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function validOptions(): iterable
    {
        yield 'defaults' => [[]];
        yield 'avatar' => [['shape' => 'circle', 'format' => 'auto', 'max' => 512, 'max_bytes' => 190_000]];
        yield 'logo' => [['aspect' => 'free', 'format' => 'auto', 'max' => 1600, 'max_bytes' => 996_000]];
        yield 'png keeps no quality' => [['format' => 'png', 'quality' => 0.5]];
        yield 'jpeg quality int' => [['format' => 'jpeg', 'quality' => 1]];
        yield 'ratio colon' => [['aspect' => ' 16 : 9 ']];
        yield 'aspect float' => [['aspect' => 1.5]];
        yield 'aspect numeric string' => [['aspect' => '2.25']];
        yield 'aspect int' => [['aspect' => 3]];
        yield 'circle ignores aspect' => [['shape' => 'circle', 'aspect' => '16/9']];
        yield 'bake png' => [['shape' => 'circle', 'format' => 'png', 'bake_circle' => true]];
        yield 'min and preview' => [['min' => 200, 'preview' => 'logo:preview.1']];
        yield 'max bounds' => [['max' => 16, 'min' => 8192]];
        yield 'max_bytes null' => [['max_bytes' => null]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('validOptions')]
    public function testOwnPrinterMatchesUiKitWithoutLabels(array $options): void
    {
        $uiKit = ImageCrop::attributes($options);
        unset($uiKit['data-nowo-ui-image-crop-labels']);

        self::assertSame($uiKit, ImageCropAttributes::attributes($options, null, false));
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('validOptions')]
    public function testDelegatesToUiKitWhenAvailable(array $options): void
    {
        self::assertSame(ImageCrop::attributes($options), ImageCropAttributes::attributes($options));
        self::assertSame(ImageCrop::attributes($options), ImageCropAttributes::attributes($options, null, true));
    }

    public function testUiKitTranslatesTheLabels(): void
    {
        $translator = new class implements TranslatorInterface {
            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $domain . ':' . $id;
            }

            public function getLocale(): string
            {
                return 'es';
            }
        };

        $attributes = ImageCropAttributes::attributes(['labels' => ['apply' => 'Usar']], $translator);
        /** @var array<string, string> $labels */
        $labels = json_decode($attributes['data-nowo-ui-image-crop-labels'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('NowoUiKitBundle:image_crop.title', $labels['title']);
        self::assertSame('Usar', $labels['apply']);

        // Without UiKit, the translator is not used and no labels are printed.
        self::assertArrayNotHasKey('data-nowo-ui-image-crop-labels', ImageCropAttributes::attributes([], $translator, false));
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'unknown key' => [['ratio' => 1]];
        yield 'shape' => [['shape' => 'oval']];
        yield 'format' => [['format' => 'gif']];
        yield 'max_bytes zero' => [['max_bytes' => 0]];
        yield 'max_bytes string' => [['max_bytes' => '190000']];
        yield 'max too small' => [['max' => 15]];
        yield 'max too large' => [['max' => 8193]];
        yield 'max float' => [['max' => 512.0]];
        yield 'min zero' => [['min' => 0]];
        yield 'min too large' => [['min' => 8193]];
        yield 'quality above 1' => [['quality' => 1.5]];
        yield 'quality negative' => [['quality' => -0.1]];
        yield 'quality string' => [['quality' => '0.8']];
        yield 'quality nan' => [['quality' => NAN]];
        yield 'bake not bool' => [['bake_circle' => 'yes']];
        yield 'bake square' => [['bake_circle' => true]];
        yield 'bake jpeg' => [['shape' => 'circle', 'format' => 'jpeg', 'bake_circle' => true]];
        yield 'preview selector' => [['preview' => '#avatar']];
        yield 'preview int' => [['preview' => 12]];
        yield 'aspect zero' => [['aspect' => 0]];
        yield 'aspect bad ratio' => [['aspect' => '16/0']];
        yield 'aspect word' => [['aspect' => 'wide']];
        yield 'aspect bool' => [['aspect' => true]];
        yield 'aspect zero string' => [['aspect' => '0']];
        yield 'labels unknown key' => [['labels' => ['heading' => 'x']]];
        yield 'labels not strings' => [['labels' => ['title' => 1]]];
        yield 'labels not array' => [['labels' => 'Crop']];
    }

    /**
     * @param array<mixed> $options
     */
    #[DataProvider('invalidOptions')]
    public function testOwnValidationRejectsWhatUiKitRejects(array $options): void
    {
        $uiKitMessage = null;
        try {
            ImageCrop::normalize($options);
        } catch (Throwable $exception) {
            $uiKitMessage = $exception->getMessage();
        }
        self::assertNotNull($uiKitMessage);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($uiKitMessage);
        ImageCropAttributes::attributes($options, null, false);
    }
}
