<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;
use function preg_replace;
use function trim;

/**
 * KIT-72 (REQ-I18N-001): `@NowoFormKitBundle/form/choice_blocks.html.twig` passes `label_translation_parameters` and
 * `choice_translation_parameters`, and honours `translation_domain: false`, when `use`d by a host theme.
 */
final class ChoiceBlocksTranslationParametersTest extends TestCase
{
    public function testCheckboxRowTranslatesTheLabelWithItsParameters(): void
    {
        $html = $this->renderRow(CheckboxType::class, [
            'label'                        => 'Notify %title%',
            'label_translation_parameters' => ['%title%' => 'Release 1.2'],
            'row_attr'                     => ['class' => 'mb-2'],
            'help'                         => 'Sent at once',
        ]);

        self::assertStringContainsString('<label for="form_field" class="nowo-form-check__label">Notify Release 1.2</label>', $html);
        self::assertStringContainsString('<div class="mb-2 nowo-form-check">', $html);
        self::assertStringContainsString('class="nowo-form-check__input"', $html);
        self::assertStringContainsString('Sent at once', $html);
        self::assertStringNotContainsString('%title%', $html);
    }

    public function testRadioRowTranslatesTheLabelWithItsParameters(): void
    {
        $html = $this->renderRow(RadioType::class, ['label' => 'Only %who%', 'label_translation_parameters' => ['%who%' => 'me']]);

        self::assertStringContainsString('>Only me</label>', $html);
        self::assertStringContainsString('type="radio"', $html);
    }

    public function testTranslationDomainFalsePrintsTheLabelAsIs(): void
    {
        $html = $this->renderRow(CheckboxType::class, [
            'label'                        => 'Notify %title%',
            'label_translation_parameters' => ['%title%' => 'ignored'],
            'translation_domain'           => false,
        ]);

        self::assertStringContainsString('>Notify %title%</label>', $html);
    }

    public function testLabelFalseEmptyLabelAndLabelHtml(): void
    {
        self::assertStringNotContainsString('<label', $this->renderRow(CheckboxType::class, ['label' => false]));
        self::assertStringContainsString('>Field</label>', $this->renderRow(CheckboxType::class, []));
        self::assertStringContainsString('>Accept the field</label>', $this->renderRow(CheckboxType::class, ['label_format' => 'Accept the %name%']));
        self::assertStringContainsString('><b>Bold %x%</b></label>', $this->renderRow(CheckboxType::class, ['label' => '<b>Bold %x%</b>', 'label_html' => true, 'translation_domain' => false]));
        self::assertStringContainsString('>&lt;b&gt;Escaped&lt;/b&gt;</label>', $this->renderRow(CheckboxType::class, ['label' => '<b>Escaped</b>']));
    }

    public function testExpandedChoicesTranslateEachChoiceWithItsParameters(): void
    {
        $html = $this->renderRow(ChoiceType::class, [
            'label'                         => 'Pick for %who%',
            'label_translation_parameters'  => ['%who%' => 'Ana'],
            'expanded'                      => true,
            'multiple'                      => true,
            'choices'                       => ['Notify %title%' => 'notify', 'Archive %title%' => 'archive'],
            'choice_translation_parameters' => static fn (string $value): array => ['%title%' => $value === 'notify' ? 'Release 1.2' : 'old pages'],
        ]);

        self::assertStringContainsString('Pick for Ana', $html);
        self::assertStringContainsString('<label for="form_field_0" class="nowo-form-check__label">Notify Release 1.2</label>', $html);
        self::assertStringContainsString('<label for="form_field_1" class="nowo-form-check__label">Archive old pages</label>', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringNotContainsString('%title%', $html);
    }

    public function testExpandedChoicesHonourChoiceTranslationDomainFalse(): void
    {
        $html = $this->renderRow(ChoiceType::class, [
            'expanded'                      => true,
            'choices'                       => ['Notify %title%' => 'notify'],
            'choice_translation_domain'     => false,
            'choice_translation_parameters' => ['%title%' => 'ignored'],
        ]);

        self::assertStringContainsString('>Notify %title%</label>', $html);
        self::assertStringContainsString('type="radio"', $html);
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $options
     */
    private function renderRow(string $type, array $options): string
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()
            ->createNamedBuilder('form', FormType::class)
            ->add('field', $type, $options)
            ->getForm();

        $view = $form->createView();
        $html = $this->twig()->createTemplate('{{ form_row(view.field) }}')->render(['view' => $view]);

        return trim((string) preg_replace('/\s+/', ' ', $html));
    }

    private function twig(): Environment
    {
        $root = dirname(__DIR__, 3);
        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'host_theme.html.twig' => "{% extends 'form_div_layout.html.twig' %}{% use 'choice_blocks.html.twig' %}",
            ]),
            new FilesystemLoader([
                $root . '/src/Resources/views/form',
                $root . '/vendor/symfony/twig-bridge/Resources/views/Form',
            ]),
        ]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer(new TwigRendererEngine(['host_theme.html.twig'], $twig)),
        ]));

        return $twig;
    }
}
