<?php

declare(strict_types=1);

namespace A2lix\TranslationFormBundle\Form\Type {
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    // Minimal stub so unit tests can exercise the wrapper even when
    // a2lix/translation-form-bundle is not installed in the test environment.
    if (!class_exists(__NAMESPACE__ . '\\TranslationsFormsType')) {
        class TranslationsFormsType extends AbstractType
        {
            public function configureOptions(OptionsResolver $resolver): void
            {
                // No-op: wrapper is responsible for defaults.
            }
        }
    }
}

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type {

    use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    final class TranslationsFormsTypeTest extends TestCase
    {
        public function testConfigureOptionsNormalizesDataClassIntoFormOptions(): void
        {
            $ref  = new \ReflectionClass(TranslationsFormsType::class);
            $type = $ref->newInstanceWithoutConstructor();
            $resolver = new OptionsResolver();

            $type->configureOptions($resolver);

            $resolved = $resolver->resolve([
                'form_type' => 'App\\Form\\TranslationItemType',
                'data_class' => 'App\\Model\\DemoTranslationItem',
                'form_options' => [],
            ]);

            self::assertSame('nowo_translations_forms', $type->getBlockPrefix());
            self::assertArrayHasKey('form_options', $resolved);
            self::assertSame(
                'App\\Model\\DemoTranslationItem',
                $resolved['form_options']['data_class'],
            );
        }
    }
}

