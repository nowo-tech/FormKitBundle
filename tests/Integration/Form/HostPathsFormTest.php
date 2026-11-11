<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Integration\Form;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\Article;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\ArticleTranslation;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\ArticleType;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\EmailAddress;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\RegisterCustomerInput;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\RegisterCustomerType;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\TranslationEntryType;
use Nowo\FormKitBundle\Tests\Stubs\HostPaths\TranslationInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

/**
 * Architecture bar, both host kinds: a DDD host binds FormKit types to an input DTO (value object through a model
 * transformer, locale tabs on an array of DTOs keyed by locale); a standard host binds them to an entity (locale tabs
 * on a Doctrine Collection indexed by locale).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class HostPathsFormTest extends TestCase
{
    public function testDddHostBindsToDtoWithValueObjectAndLocaleKeyedDtos(): void
    {
        $input               = new RegisterCustomerInput();
        $input->translations = ['es' => new TranslationInput('es'), 'en' => new TranslationInput('en')];
        $form                = $this->factory()->create(RegisterCustomerType::class, $input);

        $form->submit([
            'name'         => 'Ana',
            'email'        => 'Ana@Example.com',
            'translations' => ['es' => ['locale' => 'es', 'title' => 'Hola'], 'en' => ['locale' => 'en', 'title' => 'Hello']],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame($input, $form->getData());
        self::assertSame('Ana', $input->name);
        self::assertInstanceOf(EmailAddress::class, $input->email);
        self::assertSame('ana@example.com', $input->email->value);
        self::assertSame(['es', 'en'], array_keys($input->translations));
        self::assertSame('Hola', $input->translations['es']->title);
        self::assertSame('Hello', $input->translations['en']->title);

        $view = $form->createView();
        self::assertSame(['default_locale' => 'es'], $view['translations']->vars['locale_tabs']);
        self::assertContains(LocaleTabsExtension::BLOCK_PREFIX, $view['translations']->vars['block_prefixes']);

        $reloaded = $this->factory()->create(RegisterCustomerType::class, $input)->createView();
        self::assertSame('ana@example.com', $reloaded['email']->vars['value'], 'The value object is transformed back for display.');
    }

    public function testDddHostValueObjectRejectionIsAFieldTransformationFailure(): void
    {
        $input = new RegisterCustomerInput();
        $form  = $this->factory()->create(RegisterCustomerType::class, $input);

        $form->submit(['name' => 'Ana', 'email' => 'not-an-email', 'translations' => []]);

        self::assertFalse($form->get('email')->isSynchronized());
        self::assertNull($input->email);
    }

    public function testStandardHostBindsToEntityWithLocaleIndexedCollection(): void
    {
        $article = new Article();
        $article->addTranslation(new ArticleTranslation('es', 'Antes'));
        $article->addTranslation(new ArticleTranslation('en', 'Before'));
        $spanish = $article->getTranslations()->get('es');
        $form    = $this->factory()->create(ArticleType::class, $article);

        $form->submit([
            'code'         => 'A-1',
            'translations' => ['es' => ['locale' => 'es', 'title' => 'Despues'], 'en' => ['locale' => 'en', 'title' => 'After']],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('A-1', $article->getCode());
        self::assertSame(['es', 'en'], $article->getTranslations()->getKeys());
        self::assertSame($spanish, $article->getTranslations()->get('es'), 'Existing entities are updated in place.');
        self::assertSame('Despues', $article->getTranslations()->get('es')?->getTitle());
        self::assertSame('After', $article->getTranslations()->get('en')?->getTitle());

        $view = $form->createView();
        self::assertSame(['default_locale' => 'es', 'required_locales' => ['es']], $view['translations']->vars['locale_tabs']);
        self::assertSame(['es', 'en'], array_keys($view['translations']->children));
    }

    private function factory(): FormFactoryInterface
    {
        $typeMap = new FormTypeMap();
        $merger  = new FormOptionsMerger(
            ['default' => ['translation_domain' => 'messages', 'auto_placeholder' => false, 'auto_help' => false, 'defaults' => ['attr' => [], 'row_attr' => [], 'help_attr' => []], 'field_types' => []]],
            'default',
            new ConstraintDefinitionFactory(),
            null,
            $typeMap,
        );

        return Forms::createFormFactoryBuilder()
            ->addTypes([
                new RegisterCustomerType($merger, $typeMap),
                new ArticleType($merger, $typeMap),
                new TranslationEntryType($merger, $typeMap),
            ])
            ->addTypeExtension(new LocaleTabsExtension())
            ->getFormFactory();
    }
}
