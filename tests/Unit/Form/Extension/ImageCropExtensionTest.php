<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\ImageCropExtension;
use Nowo\FormKitBundle\Form\ImageCropAttributes;
use Nowo\UiKitBundle\Form\ImageCrop;
use Nowo\UiKitBundle\Form\ImageCropTypeExtension;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;
use function htmlspecialchars;
use function iterator_to_array;

use const ENT_QUOTES;

/**
 * `crop` option on FileType (UiKit 2.43 image crop contract).
 */
final class ImageCropExtensionTest extends TestCase
{
    public function testExtendsFileTypeOnly(): void
    {
        self::assertSame([FileType::class], iterator_to_array(ImageCropExtension::getExtendedTypes()));
    }

    public function testDefaultOutputIsByteIdentical(): void
    {
        foreach ([[], ['multiple' => true], ['attr' => ['accept' => 'image/*', 'class' => 'x']], ['crop' => false], ['crop' => false, 'multiple' => true]] as $options) {
            $withExtension = $this->render($this->factory(new ImageCropExtension()), $options);
            unset($options['crop']);
            self::assertSame($this->render(Forms::createFormFactoryBuilder()->getFormFactory(), $options), $withExtension);
        }
    }

    public function testCropTrueRendersUiKitDefaults(): void
    {
        $html = $this->render($this->factory(new ImageCropExtension()), ['crop' => true]);

        foreach (ImageCrop::attributes([]) as $name => $value) {
            self::assertStringContainsString($name . '="' . htmlspecialchars($value, ENT_QUOTES) . '"', $html);
        }
        self::assertStringContainsString('type="file"', $html);
    }

    public function testAvatarWithUiKitPrintsTranslatedLabels(): void
    {
        $translator = new class implements TranslatorInterface {
            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return 'T(' . $id . ')';
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };
        $crop = ['shape' => 'circle', 'format' => 'auto', 'max' => 512, 'max_bytes' => 190_000, 'preview' => 'avatar-preview'];
        $view = $this->factory(new ImageCropExtension($translator))->createNamedBuilder('form', FormType::class)
            ->add('avatar', FileType::class, ['required' => false, 'crop' => $crop])
            ->getForm()->createView();

        self::assertSame(ImageCrop::attributes($crop, $translator), $view['avatar']->vars['attr']);
        self::assertStringContainsString('T(image_crop.apply)', $view['avatar']->vars['attr']['data-nowo-ui-image-crop-labels']);
    }

    public function testLogoWithoutUiKitPrintsTheContractItself(): void
    {
        $html = $this->render($this->factory(new ImageCropExtension(null, false)), [
            'crop' => ['aspect' => '16/9', 'format' => 'auto', 'max' => 1600, 'max_bytes' => 996_000],
        ]);

        self::assertStringContainsString('data-nowo-ui-image-crop=""', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-shape="square"', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-aspect="16/9"', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-max="1600"', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-format="auto"', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-max-bytes="996000"', $html);
        self::assertStringContainsString('data-nowo-ui-image-crop-quality="0.92"', $html);
        // English fallback in the runtime: no labels attribute.
        self::assertStringNotContainsString('data-nowo-ui-image-crop-labels', $html);
    }

    public function testFieldAttrWinsOnConflicts(): void
    {
        $view = $this->factory(new ImageCropExtension(null, false))->createNamedBuilder('form', FormType::class)
            ->add('avatar', FileType::class, [
                'crop' => ['shape' => 'circle', 'max' => 512],
                'attr' => ['accept' => 'image/png,image/webp', 'data-nowo-ui-image-crop-max' => '256', 'class' => 'avatar'],
            ])
            ->getForm()->createView();

        $attr = $view['avatar']->vars['attr'];
        self::assertSame('256', $attr['data-nowo-ui-image-crop-max']);
        self::assertSame('image/png,image/webp', $attr['accept']);
        self::assertSame('avatar', $attr['class']);
        self::assertSame('circle', $attr['data-nowo-ui-image-crop-shape']);
    }

    public function testMultipleIsRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"crop" crops one image: it cannot be used with "multiple".');

        $this->factory(new ImageCropExtension())->create(FileType::class, null, ['multiple' => true, 'crop' => true]);
    }

    public function testInvalidOptionsAreRefusedWhenTheFormIsBuilt(): void
    {
        foreach ([false, null] as $uiKit) {
            try {
                $this->factory(new ImageCropExtension(null, $uiKit))->create(FileType::class, null, ['crop' => ['max' => 9000]]);
                self::fail('Expected InvalidOptionsException');
            } catch (InvalidOptionsException $exception) {
                self::assertStringEndsWith('"crop": The image crop max is an integer from 16 to 8192.', $exception->getMessage());
            }
        }
    }

    public function testValidationRunsThroughThePathThatPrints(): void
    {
        foreach ([[null, ImageCrop::class], [false, ImageCropAttributes::class]] as [$uiKit, $thrower]) {
            try {
                $this->factory(new ImageCropExtension(null, $uiKit))->create(FileType::class, null, ['crop' => ['shape' => 'oval']]);
                self::fail('Expected InvalidOptionsException');
            } catch (InvalidOptionsException $exception) {
                self::assertStringEndsWith('"crop": The image crop shape is "square" or "circle".', $exception->getMessage());
                $cause = $exception;
                while ($cause->getPrevious() instanceof Throwable) {
                    $cause = $cause->getPrevious();
                }
                self::assertSame((new ReflectionClass($thrower))->getFileName(), $cause->getFile());
            }
        }
    }

    public function testCropTogetherWithUiKitOptionIsRefused(): void
    {
        $factory = $this->factory(new ImageCropExtension(), new ImageCropTypeExtension());
        foreach ([true, ['shape' => 'circle']] as $uiKitCrop) {
            try {
                $factory->create(FileType::class, null, ['crop' => ['max_bytes' => 190_000], 'nowo_ui_image_crop' => $uiKitCrop]);
                self::fail('Expected InvalidOptionsException');
            } catch (InvalidOptionsException $exception) {
                self::assertStringEndsWith('"crop" and "nowo_ui_image_crop" cannot both be set on one field: use one of them.', $exception->getMessage());
            }
        }

        // One of them alone (the other false or left out) is fine.
        $view = $factory->createNamedBuilder('form', FormType::class)
            ->add('a', FileType::class, ['crop' => ['shape' => 'circle'], 'nowo_ui_image_crop' => false])
            ->add('b', FileType::class, ['crop' => false, 'nowo_ui_image_crop' => ['shape' => 'circle']])
            ->add('c', FileType::class, ['crop' => ['shape' => 'circle']])
            ->getForm()->createView();
        foreach (['a', 'b', 'c'] as $name) {
            self::assertSame('circle', $view[$name]->vars['attr']['data-nowo-ui-image-crop-shape'], $name);
        }
    }

    public function testNonBoolNonArrayIsRefused(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory(new ImageCropExtension())->create(FileType::class, null, ['crop' => 'circle']);
    }

    public function testOtherTypesDoNotGetTheOption(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $this->factory(new ImageCropExtension())->create(TextType::class, null, ['crop' => true]);
    }

    /**
     * @param FormTypeExtensionInterface<mixed> ...$extensions
     */
    private function factory(FormTypeExtensionInterface ...$extensions): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeExtensions($extensions)->getFormFactory();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function render(FormFactoryInterface $factory, array $options): string
    {
        $view = $factory->createNamedBuilder('form', FormType::class)
            ->add('avatar', FileType::class, $options)
            ->getForm()->createView();

        $twig = new Environment(new FilesystemLoader([dirname(__DIR__, 4) . '/vendor/symfony/twig-bridge/Resources/views/Form']), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));
        $engine = new TwigRendererEngine(['form_div_layout.html.twig'], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine)]));

        return $twig->createTemplate('{{ form_row(view.avatar) }}')->render(['view' => $view]);
    }
}
