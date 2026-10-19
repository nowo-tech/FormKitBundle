<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\CheckboxRowExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;

final class CheckboxRowExtensionTest extends TestCase
{
    public function testVarsFollowTheProfileFramework(): void
    {
        self::assertSame([CheckboxType::class], [...CheckboxRowExtension::getExtendedTypes()]);
        self::assertSame([true, 'mb-3'], $this->vars([], 'bootstrap'));
        self::assertSame([false, ''], $this->vars([], 'tailwind'));
        self::assertSame([false, ''], $this->vars(['css_framework' => 'tailwind'], 'bootstrap'), 'profile framework wins');
        self::assertSame([true, 'mb-3'], $this->vars(['css_framework' => 'bootstrap5'], 'tailwind'));
        self::assertSame([false, 'space-y-1'], $this->vars(['defaults' => ['row_attr' => ['class' => 'space-y-1']]], 'tailwind'), "the profile's row class");
        self::assertSame([true, 'mb-3'], $this->vars(null, 'bootstrap'), 'unknown default profile');
    }

    public function testStaticBlocksCheckboxRow(): void
    {
        $bootstrap = $this->render('bootstrap', []);
        self::assertStringContainsString('<div class="mb-3 form-check">', $bootstrap);
        self::assertStringContainsString('class="form-check-input"', $bootstrap);
        self::assertStringContainsString('class="form-check-label', $bootstrap);

        $tailwind = $this->render('tailwind', ['defaults' => ['row_attr' => ['class' => 'flex items-center gap-2']]]);
        self::assertStringContainsString('<div class="flex items-center gap-2">', $tailwind);
        self::assertStringNotContainsString('form-check', $tailwind);
        self::assertStringNotContainsString('mb-3', $tailwind);

        $plain = $this->render('none', []);
        self::assertMatchesRegularExpression('/<div>\s*<input type="checkbox"/', $plain);
        self::assertStringNotContainsString('form-check', $plain);

        // Without the extension (custom Twig setups): the Bootstrap chrome, as before 2.13.
        self::assertStringContainsString('<div class="mb-3 form-check">', $this->render(null, []));
    }

    /**
     * @param array<string, mixed>|null $profile
     *
     * @return array{0: mixed, 1: mixed}
     */
    private function vars(?array $profile, string $framework): array
    {
        $view = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new CheckboxRowExtension($profile === null ? [] : ['default' => $profile], 'default', $framework))
            ->getFormFactory()->create(CheckboxType::class)->createView();

        return [$view->vars['checkbox_bootstrap'], $view->vars['checkbox_row_class']];
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function render(?string $framework, array $profile): string
    {
        $builder = Forms::createFormFactoryBuilder();
        if ($framework !== null) {
            $builder->addTypeExtension(new CheckboxRowExtension(['default' => $profile], 'default', $framework));
        }
        $form = $builder->getFormFactory()->createNamedBuilder('f', FormType::class)->add('agree', CheckboxType::class, ['label' => 'Agree'])->getForm();

        $root = dirname(__DIR__, 4);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($root . '/src/Resources/views', 'NowoFormKitBundle');
        $twig = new Environment(new ChainLoader([new ArrayLoader(['t' => "{% form_theme form '@NowoFormKitBundle/form/static_blocks.html.twig' %}{{ form_row(form.agree) }}"]), $fs]), ['strict_variables' => true]);
        $twig->addExtension(new TranslationExtension());
        $twig->addExtension(new FormExtension());
        $engine = new TwigRendererEngine(['form_div_layout.html.twig'], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine)]));

        return $twig->render('t', ['form' => $form->createView()]);
    }
}
