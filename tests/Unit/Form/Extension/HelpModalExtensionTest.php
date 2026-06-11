<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\HelpModalExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use const JSON_THROW_ON_ERROR;

final class HelpModalExtensionTest extends TestCase
{
    public function testExtendsFormTypeLikeOtherBundleExtensions(): void
    {
        self::assertSame([FormType::class], iterator_to_array(HelpModalExtension::getExtendedTypes()));
    }

    public function testBuildViewDoesNothingWhenHelpModalIsFalse(): void
    {
        $ext = new HelpModalExtension(
            configs: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>?</span>',
                    ],
                ],
            ],
            defaultConfigName: 'default',
        );

        $view                     = new FormView();
        $view->vars['label_attr'] = ['class' => 'x'];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => false,
        ]);

        self::assertSame(['class' => 'x'], $view->vars['label_attr']);
    }

    public function testBuildViewInjectsDataAttributeIntoLabelAttrUsingMergedDefaults(): void
    {
        $ext = new HelpModalExtension(
            configs: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'tailwind',
                        'icon_html' => '<i class="help-icon">?</i>',
                    ],
                ],
            ],
            defaultConfigName: 'default',
        );

        $view                     = new FormView();
        $view->vars['id']         = 'field_id_1';
        $view->vars['label_attr'] = ['class' => 'my-label'];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => [
                'title'   => 'Help title',
                'content' => '<p>Help content</p>',
            ],
        ]);

        self::assertArrayHasKey('data-nowo-help-modal', $view->vars['label_attr']);
        self::assertSame('my-label', $view->vars['label_attr']['class']);

        $json = $view->vars['label_attr']['data-nowo-help-modal'];
        self::assertIsString($json);

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('nowo-help-modal-field_id_1', $data['id']);
        self::assertSame('tailwind', $data['framework']);
        self::assertSame('<i class="help-icon">?</i>', $data['icon_html']);
        self::assertSame('Help title', $data['title']);
        self::assertSame('<p>Help content</p>', $data['content']);
    }

    public function testBuildViewAcceptsHelpModalTrueAsEnabledWithDefaults(): void
    {
        $ext = new HelpModalExtension(
            configs: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>?</span>',
                    ],
                ],
            ],
            defaultConfigName: 'default',
        );

        $view                     = new FormView();
        $view->vars['id']         = 'field_id_2';
        $view->vars['label_attr'] = [];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => true,
        ]);

        $json = $view->vars['label_attr']['data-nowo-help-modal'] ?? null;
        self::assertIsString($json);

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('nowo-help-modal-field_id_2', $data['id']);
        self::assertSame('bootstrap5', $data['framework']);
        self::assertSame('<span>?</span>', $data['icon_html']);
        self::assertNull($data['title']);
        self::assertSame('', $data['content']);
    }

    public function testBuildViewUsesUxIconRendererWhenUxIconIsSetAndRendererProvided(): void
    {
        $renderer = new class {
            public function renderIcon(string $name, array $attributes = []): string
            {
                return '<svg data-test-icon="' . $name . '" class="' . (string) ($attributes['class'] ?? '') . '"></svg>';
            }
        };

        $ext = new HelpModalExtension(
            configs: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>fallback</span>',
                    ],
                ],
            ],
            defaultConfigName: 'default',
            iconRenderer: $renderer,
        );

        $view                     = new FormView();
        $view->vars['id']         = 'field_id_ux';
        $view->vars['label_attr'] = [];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => [
                'ux_icon'            => 'lucide:circle-help',
                'ux_icon_attributes' => ['class' => 'nowo-help-modal-icon'],
            ],
        ]);

        $json = $view->vars['label_attr']['data-nowo-help-modal'] ?? null;
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('data-test-icon="lucide:circle-help"', $data['icon_html']);
        self::assertStringContainsString('nowo-help-modal-icon', $data['icon_html']);
    }

    public function testBuildViewFallsBackToIconHtmlWhenUxIconSetButRendererMissing(): void
    {
        $ext = new HelpModalExtension(
            configs: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span class="fallback">?</span>',
                    ],
                ],
            ],
            defaultConfigName: 'default',
            iconRenderer: null,
        );

        $view                     = new FormView();
        $view->vars['id']         = 'field_id_fb';
        $view->vars['label_attr'] = [];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => [
                'ux_icon' => 'lucide:circle-help',
            ],
        ]);

        $json = $view->vars['label_attr']['data-nowo-help-modal'] ?? null;
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('fallback', $data['icon_html']);
    }
}
