<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Form;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class FormOptionsMergerTest extends TestCase
{
    private FormOptionsMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'text' => ['attr' => ['class' => 'form-control form-control-text']],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
    }

    public function testResolveUsesConventionForLabelPlaceholderHelp(): void
    {
        $options = $this->merger->resolve('user_profile', 'email_address', \Symfony\Component\Form\Extension\Core\Type\TextType::class, []);

        self::assertSame('user_profile.email_address.label', $options['label']);
        self::assertSame('user_profile.email_address.placeholder', $options['attr']['placeholder']);
        self::assertSame('user_profile.email_address.help', $options['help']);
        self::assertSame('messages', $options['translation_domain']);
        self::assertSame('form-control form-control-text', $options['attr']['class']);
        self::assertSame(['class' => 'mb-3'], $options['row_attr']);
    }

    public function testResolveMergesFieldTypeDefaults(): void
    {
        $options = $this->merger->resolve('user_profile', 'name', 'text', []);

        self::assertSame('user_profile.name.label', $options['label']);
        self::assertSame('form-control form-control-text', $options['attr']['class']);
        self::assertSame('user_profile.name.placeholder', $options['attr']['placeholder']);
    }

    public function testResolveFieldOptionsOverrideConvention(): void
    {
        $options = $this->merger->resolve('user_profile', 'email', EmailType::class, [
            'label'       => 'Custom label',
            'placeholder' => false,
            'attr'        => ['class' => 'custom-class'],
        ]);

        self::assertSame('Custom label', $options['label']);
        self::assertArrayNotHasKey('placeholder', $options);
        self::assertSame(['class' => 'custom-class'], $options['attr']);
        self::assertArrayNotHasKey('placeholder', $options['attr']);
    }

    public function testResolveLabelFalseRemovesLabel(): void
    {
        $options = $this->merger->resolve('user_profile', 'internal_note', 'text', ['label' => false]);

        self::assertArrayNotHasKey('label', $options);
    }

    public function testResolveDoesNotPassRequiredLabelSuffixToType(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain'    => 'messages',
                    'required_label_suffix' => ' *',
                    'defaults'              => ['attr' => [], 'row_attr' => []],
                    'field_types'           => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        $options = $merger->resolve('contact', 'name', 'text', []);

        self::assertArrayNotHasKey('required_label_suffix', $options, 'required_label_suffix must not be passed to the form type; it is injected into the view from config.');
    }

    public function testResolveBuildsConstraintsFromFieldTypesEmail(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'email' => [
                            'constraints' => [
                                'NotBlank',
                                'Email',
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        $options = $merger->resolve('contact', 'userEmail', EmailType::class, []);

        self::assertArrayHasKey('constraints', $options);
        self::assertCount(2, $options['constraints']);
        self::assertInstanceOf(NotBlank::class, $options['constraints'][0]);
        self::assertInstanceOf(EmailConstraint::class, $options['constraints'][1]);
    }

    public function testResolveMergesFieldConstraintsAfterFieldTypeDefaults(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'email' => [
                            'constraints' => [
                                'NotBlank',
                                'Email',
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        $options = $merger->resolve('contact', 'userEmail', EmailType::class, [
            'constraints' => [
                ['type' => 'Length', 'options' => ['max' => 255]],
            ],
        ]);

        self::assertCount(3, $options['constraints']);
        self::assertInstanceOf(Length::class, $options['constraints'][2]);
        self::assertSame(255, $options['constraints'][2]->max);
    }
}
