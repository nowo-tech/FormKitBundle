<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

use function dirname;

/**
 * Symfony button/submit/reset widgets call form_label_content without a `required` var.
 * With Twig strict_variables that must not throw (hosts in debug).
 */
final class StaticBlocksFormLabelContentTest extends TestCase
{
    public function testFormLabelContentRendersWithoutRequiredVariable(): void
    {
        $html = $this->renderFormLabelContent([
            'label'                        => 'Save',
            'label_format'                 => '',
            'name'                         => 'save',
            'id'                           => 'form_save',
            'translation_domain'           => false,
            'label_html'                   => false,
            'label_translation_parameters' => [],
            'form'                         => $this->formViewWithSuffix(' *'),
        ]);

        self::assertSame('Save', $html);
    }

    public function testFormLabelContentAppendsSuffixWhenRequired(): void
    {
        $html = $this->renderFormLabelContent([
            'label'                        => 'Title',
            'label_format'                 => '',
            'name'                         => 'title',
            'id'                           => 'form_title',
            'translation_domain'           => false,
            'label_html'                   => false,
            'label_translation_parameters' => [],
            'required'                     => true,
            'form'                         => $this->formViewWithSuffix(' *'),
        ]);

        self::assertSame('Title *', $html);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderFormLabelContent(array $context): string
    {
        $root   = dirname(__DIR__, 3);
        $loader = new FilesystemLoader([
            $root . '/src/Resources/views/form',
            $root . '/vendor/symfony/twig-bridge/Resources/views/Form',
        ]);

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape'       => false,
            'cache'            => false,
        ]);
        $twig->addExtension(new FormExtension());
        $twig->addFilter(new TwigFilter('trans', static fn (mixed $value): string => (string) $value));

        return $twig->load('static_blocks.html.twig')->renderBlock('form_label_content', $context);
    }

    private function formViewWithSuffix(string $suffix): FormView
    {
        $view                                = new FormView();
        $view->vars['required_label_suffix'] = $suffix;

        return $view;
    }
}
