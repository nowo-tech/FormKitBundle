<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\HelpModalExtension;
use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            profiles: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>?</span>',
                    ],
                ],
            ],
            defaultProfileName: 'default',
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
            profiles: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'tailwind',
                        'icon_html' => '<i class="help-icon">?</i>',
                    ],
                ],
            ],
            defaultProfileName: 'default',
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
            profiles: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>?</span>',
                    ],
                ],
            ],
            defaultProfileName: 'default',
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
            /** @param array<string, mixed> $attributes */
            public function renderIcon(string $name, array $attributes = []): string
            {
                return '<svg data-test-icon="' . $name . '" class="' . ($attributes['class'] ?? '') . '"></svg>';
            }
        };

        $ext = new HelpModalExtension(
            profiles: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span>fallback</span>',
                    ],
                ],
            ],
            defaultProfileName: 'default',
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
            profiles: [
                'default' => [
                    'help_modal' => [
                        'framework' => 'bootstrap5',
                        'icon_html' => '<span class="fallback">?</span>',
                    ],
                ],
            ],
            defaultProfileName: 'default',
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

    public function testConfigureOptionsAllowsNullBooleanOrArray(): void
    {
        $ext      = new HelpModalExtension([], 'default');
        $resolver = new OptionsResolver();
        $ext->configureOptions($resolver);

        self::assertNull($resolver->resolve([])['help_modal']);
        self::assertTrue($resolver->resolve(['help_modal' => true])['help_modal']);
        self::assertSame(['title' => 'x'], $resolver->resolve(['help_modal' => ['title' => 'x']])['help_modal']);
    }

    public function testBuildViewIgnoresNonArrayHelpModalValue(): void
    {
        $ext = new HelpModalExtension(
            profiles: ['default' => ['help_modal' => ['framework' => 'bootstrap5']]],
            defaultProfileName: 'default',
        );

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, ['help_modal' => 123]);

        self::assertArrayNotHasKey('data-nowo-help-modal', $view->vars['label_attr'] ?? []);
    }

    public function testBuildViewUsesCustomModalIdAndNormalizesNonArrayLabelAttr(): void
    {
        $ext = new HelpModalExtension(
            profiles: ['default' => ['help_modal' => ['framework' => 'bootstrap5', 'icon_html' => '?']]],
            defaultProfileName: 'default',
        );

        $view                     = new FormView();
        $view->vars['id']         = 'ignored';
        $view->vars['label_attr'] = 'invalid';

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => [
                'id'      => 'custom-modal-id',
                'content' => 'Body',
            ],
        ]);

        $data = json_decode((string) $view->vars['label_attr']['data-nowo-help-modal'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom-modal-id', $data['id']);
    }

    public function testBuildViewFallsBackWhenUxIconRendererThrows(): void
    {
        $renderer = new class {
            /** @param array<string, mixed> $attributes */
            public function renderIcon(string $name, array $attributes = []): string
            {
                throw new RuntimeException('icon failure');
            }
        };

        $ext = new HelpModalExtension(
            profiles: ['default' => ['help_modal' => ['icon_html' => '<span>fallback</span>']]],
            defaultProfileName: 'default',
            iconRenderer: $renderer,
        );

        $view                     = new FormView();
        $view->vars['id']         = 'field_throw';
        $view->vars['label_attr'] = [];

        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, [
            'help_modal' => ['ux_icon' => 'lucide:help'],
        ]);

        $data = json_decode((string) $view->vars['label_attr']['data-nowo-help-modal'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('<span>fallback</span>', $data['icon_html']);
    }

    public function testPayloadCarriesRequestNonceAndOptInContentHtml(): void
    {
        $request = new Request();
        $stack   = new RequestStack();
        $stack->push($request);
        $nonce = new HelpModalNonce($stack);

        $ext = new HelpModalExtension(
            profiles: ['default' => ['help_modal' => []]],
            defaultProfileName: 'default',
            nonce: $nonce,
        );

        $view             = new FormView();
        $view->vars['id'] = 'field_nonce';
        $ext->buildView($view, $this->createStub(FormInterface::class), [
            'help_modal' => [
                'content'      => 'Plain <b>text</b>',
                'content_html' => '<p>Trusted</p>',
            ],
        ]);

        $json = $view->vars['label_attr']['data-nowo-help-modal'];
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($nonce->get(), $data['nonce']);
        self::assertSame('Plain <b>text</b>', $data['content']);
        self::assertSame('<p>Trusted</p>', $data['content_html']);
    }

    public function testContentHtmlIsNullWhenNotAString(): void
    {
        $ext = new HelpModalExtension(profiles: ['default' => ['help_modal' => []]], defaultProfileName: 'default');

        $view = new FormView();
        $ext->buildView($view, $this->createStub(FormInterface::class), ['help_modal' => ['content_html' => ['x']]]);

        $json = $view->vars['label_attr']['data-nowo-help-modal'];
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertNull($data['content_html']);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $data['nonce']);
    }
}
