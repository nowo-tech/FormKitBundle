<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\AbstractFormKitType;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class AbstractFormKitTypeTest extends TestCase
{
    public function testConcreteTypeCanUseTraitMethodsFromAbstractBase(): void
    {
        $merger = new FormOptionsMerger([
            'default' => [
                'translation_domain' => 'forms',
                'defaults'           => ['attr' => [], 'row_attr' => []],
                'field_types'        => [],
            ],
        ], 'default');
        $builder = $this->createMock(FormBuilderInterface::class);

        $type = new class extends AbstractFormKitType {
            public function wire(FormOptionsMerger $merger): void
            {
                $this->setFormOptionsMerger($merger);
            }

            public function addName(FormBuilderInterface $builder): void
            {
                $this->addWithDefaults($builder, 'name', TextType::class);
            }
        };

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static function (array $options): bool {
                    if (($options['translation_domain'] ?? null) !== 'forms') {
                        return false;
                    }
                    if (($options['row_attr'] ?? null) !== []) {
                        return false;
                    }
                    if (!str_ends_with((string) ($options['label'] ?? ''), '.name.label')) {
                        return false;
                    }
                    if (!str_ends_with((string) ($options['help'] ?? ''), '.name.help')) {
                        return false;
                    }

                    return str_ends_with((string) ($options['attr']['placeholder'] ?? ''), '.name.placeholder');
                }),
            )
            ->willReturnSelf();

        $type->wire($merger);
        $type->addName($builder);
    }
}
