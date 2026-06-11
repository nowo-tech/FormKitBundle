<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\DemoTranslationItem;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_array;

/**
 * Form type for a single translation (title, description).
 * Used as form_type in A2lix TranslationsFormsType; uses buildFormFromArray and data_class.
 * Normalizes array (e.g. from request) to DemoTranslationItem so data_class is always satisfied.
 */
class TranslationItemType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // A2lix expects translation item data to be array-like for rendering
        // (it reads title/description using array access).
        // On submit, we want DemoTranslationItem objects as the final form data.
        $builder->addModelTransformer(new class implements DataTransformerInterface {
            /**
             * @return array{title: ?string, description: ?string}
             */
            public function transform(mixed $value): array
            {
                if ($value instanceof DemoTranslationItem) {
                    return [
                        'title' => $value->getTitle(),
                        'description' => $value->getDescription(),
                    ];
                }

                if (is_array($value)) {
                    return [
                        'title' => $value['title'] ?? null,
                        'description' => $value['description'] ?? null,
                    ];
                }

                return ['title' => null, 'description' => null];
            }

            public function reverseTransform(mixed $value): DemoTranslationItem
            {
                if ($value instanceof DemoTranslationItem) {
                    return $value;
                }

                if (!is_array($value)) {
                    return new DemoTranslationItem();
                }

                $item = new DemoTranslationItem();
                $item->setTitle($value['title'] ?? null);
                $item->setDescription($value['description'] ?? null);

                return $item;
            }
        });

        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->buildFormFromArray($builder, [
            'title'       => ['type' => TextType::class, ...$rowFull],
            'description' => ['type' => TextareaType::class, ...$rowFull],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // A2lix provides raw arrays into this form for rendering.
            // We normalize to/from DemoTranslationItem via the model transformer.
            'data_class' => null,
        ]);
    }
}
