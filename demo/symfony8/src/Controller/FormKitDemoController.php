<?php

declare(strict_types=1);

namespace App\Controller;

use App\Demo\DemoTranslationLocales;
use App\Form\AutocompleteDemoType;
use App\Form\BuildTimeConditionalDemoType;
use App\Form\ChoiceFieldsDemoType;
use App\Form\CkeditorDemoType;
use App\Form\ConditionalFieldsDemoType;
use App\Form\DataTransformersDemoType;
use App\Form\DemoContactType;
use App\Form\DropzoneDemoType;
use App\Form\ExampleFormType;
use App\Form\NamedConfigDemoType;
use App\Form\NestedFormDemoType;
use App\Form\NowoSpecialFieldsDemoType;
use App\Form\SearchFormType;
use App\Form\SnakeCaseKitDemoType;
use App\Form\TranslationsDemoType;
use App\Model\AutocompleteDemoData;
use App\Model\ChoiceFieldsDemoData;
use App\Model\CkeditorDemoData;
use App\Model\ConditionalFieldsDemoData;
use App\Model\ContactWithAddress;
use App\Model\DataTransformersDemoData;
use App\Model\DemoTranslatableItem;
use App\Model\NowoSpecialFieldsDemoData;
use Nowo\FormKitBundle\Controller\FormKitControllerTrait;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\MultiStepFormBuilder;
use Nowo\FormKitBundle\Form\MultiStepWizardSessionFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

use function in_array;

/**
 * Form Kit demo: FormType example and form built in controller example.
 */
#[Route(path: '/{_locale}', requirements: ['_locale' => 'en|es|fr|de'], defaults: ['_locale' => 'en'])]
class FormKitDemoController extends AbstractController
{
    use FormKitControllerTrait;

    private const MULTISTEP_DEFINITION = [
        'contact' => [
            'label'  => 'Contact',
            'fields' => [
                'fullName' => TextType::class,
                'email'    => EmailType::class,
            ],
        ],
        'address' => [
            'label'  => 'Address',
            'fields' => [
                'street'     => TextType::class,
                'number'     => TextType::class,
                'floor'      => TextType::class,
                'postalCode' => TextType::class,
                'city'       => TextType::class,
                'province'   => TextType::class,
            ],
        ],
        'confirm' => [
            'label'  => 'Confirm',
            'fields' => [],
        ],
    ];

    public function __construct(
        private readonly FormOptionsMerger $formOptionsMerger,
        private readonly FormTypeMap $formTypeMap,
        private readonly MultiStepFormBuilder $multiStepFormBuilder,
        private readonly MultiStepWizardSessionFactory $wizardFactory
    ) {
        $this->setFormOptionsMerger($this->formOptionsMerger);
        $this->setFormTypeMap($this->formTypeMap);
        $this->setFormKitFormName('controller_contact');
    }

    /**
     * Index: explanatory cards linking to each demo page (no forms on home).
     */
    #[Route(path: '/', name: 'form_demo_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('form_demo/index.html.twig');
    }

    /**
     * Example 1: FormType (DemoContactType) — form defined in a dedicated class.
     */
    #[Route(path: '/form-type', name: 'form_demo_form_type', methods: ['GET', 'POST'])]
    public function formTypeExample(Request $request): Response
    {
        $form = $this->createForm(DemoContactType::class);
        $form->handleRequest($request);

        return $this->render('form_demo/form_type_example.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Example 2: Form created in the controller using FormOptionsMerger (no FormType class).
     */
    #[Route(path: '/controller-form', name: 'form_demo_controller_form', methods: ['GET', 'POST'])]
    public function controllerFormExample(Request $request): Response
    {
        $form = $this->createControllerForm();
        $form->handleRequest($request);

        return $this->render('form_demo/controller_form_example.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Search form — inline/horizontal layout (search bar).
     */
    #[Route(path: '/search', name: 'form_demo_search', methods: ['GET'])]
    public function searchForm(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/search.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Example form — card/stacked layout.
     */
    #[Route(path: '/example-form', name: 'form_demo_example_form', methods: ['GET', 'POST'])]
    public function exampleForm(Request $request): Response
    {
        $form = $this->createForm(ExampleFormType::class);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/example_form.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Dropzone demo — Symfony UX Dropzone (drag-and-drop file upload).
     * Shows a default image from public/images when available.
     */
    #[Route(path: '/dropzone', name: 'form_demo_dropzone', methods: ['GET', 'POST'])]
    public function dropzoneDemo(Request $request): Response
    {
        $projectDir       = $this->getParameter('kernel.project_dir');
        $defaultImagePath = $projectDir . '/public/images/demo-sample.jpg';
        $defaultImageUrl  = is_file($defaultImagePath) ? $request->getUriForPath('/images/demo-sample.jpg') : null;

        $form = $this->createForm(DropzoneDemoType::class);
        $form->handleRequest($request);
        $uploaded = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (isset($data['document']) && $data['document']) {
                $uploaded = $data['document']->getClientOriginalName();
            }
        }

        return $this->render('form_demo/dropzone.html.twig', [
            'form'              => $form,
            'uploaded'          => $uploaded,
            'default_image_url' => $defaultImageUrl,
        ]);
    }

    /**
     * Cropper demo — Symfony UX Cropper.js (crop an image).
     */
    #[Route(path: '/cropper', name: 'form_demo_cropper', methods: ['GET', 'POST'])]
    public function cropperDemo(Request $request, CropperInterface $cropper): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $candidates = [
            $projectDir . '/public/images/demo-sample.jpg',
            $projectDir . '/images/demo-sample.jpg',
        ];
        $imagePath = null;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $imagePath = $path;
                break;
            }
        }
        $imageUrlPath = '/images/demo-sample.jpg';
        $sampleExists = $imagePath !== null;
        $imageUrl     = $request->getUriForPath($imageUrlPath);

        $form      = null;
        $submitted = null;
        if ($sampleExists) {
            $crop = $cropper->createCrop($imagePath);
            $crop->setCroppedMaxSize(800, 600);
            $form = $this->createFormBuilder(['crop' => $crop])
                ->add('crop', CropperType::class, [
                    'public_url'      => $imageUrl,
                    'cropper_options' => ['aspectRatio' => 4 / 3],
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $submitted = true;
            }
        }

        return $this->render('form_demo/cropper.html.twig', [
            'form'          => $form,
            'sample_exists' => $sampleExists,
            'image_path'    => $imagePath ?? $candidates[0],
            'submitted'     => $submitted,
        ]);
    }

    /**
     * Translations demo — translatable fields (title, description) per locale, form_class per locale.
     */
    #[Route(path: '/translations', name: 'form_demo_translations', methods: ['GET', 'POST'])]
    public function translationsDemo(Request $request): Response
    {
        $session = $request->getSession();
        if ($request->query->get('reshuffle')) {
            DemoTranslationLocales::clear($session);

            return $this->redirectToRoute('form_demo_translations', ['_locale' => $request->getLocale()]);
        }

        $localeContext = DemoTranslationLocales::forSession($session);
        $item          = DemoTranslatableItem::forLocales($localeContext['enabled_locales']);
        $form          = $this->createForm(TranslationsDemoType::class, $item);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/translations.html.twig', [
            'form'                     => $form,
            'submitted'                => $submitted,
            'demo_translation_context' => $localeContext,
        ]);
    }

    /**
     * Nowo special fields — OTP, phone, password, icon selector, tags, Tiptap, CKEditor 5, slide-to-confirm.
     */
    #[Route(path: '/nowo-special-fields', name: 'form_demo_nowo_special_fields', methods: ['GET', 'POST'])]
    public function nowoSpecialFieldsDemo(Request $request): Response
    {
        $data = new NowoSpecialFieldsDemoData();
        $form = $this->createForm(NowoSpecialFieldsDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/nowo_special_fields.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Nested form demo — contact + embedded address (street, number, floor, postal code, city, province).
     */
    #[Route(path: '/nested', name: 'form_demo_nested', methods: ['GET', 'POST'])]
    public function nestedFormDemo(Request $request): Response
    {
        $data = new ContactWithAddress();
        $form = $this->createForm(NestedFormDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/nested.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Data transformers demo — money, JSON, CSV, bool, switch via FormOptionsTrait helpers.
     */
    #[Route(path: '/data-transformers', name: 'form_demo_data_transformers', methods: ['GET', 'POST'])]
    public function dataTransformersDemo(Request $request): Response
    {
        $data = new DataTransformersDemoData();
        $form = $this->createForm(DataTransformersDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/data_transformers.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Choice fields demo — select, multiselect, radios, checkboxes, select-all (SelectAllChoiceBundle), autocomplete helper.
     */
    #[Route(path: '/choice-fields', name: 'form_demo_choice_fields', methods: ['GET', 'POST'])]
    public function choiceFieldsDemo(Request $request): Response
    {
        $data = new ChoiceFieldsDemoData();
        $form = $this->createForm(ChoiceFieldsDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/choice_fields.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * Conditional fields demo — FormEvents + resolveFieldOptions(), and build-time if.
     */
    #[Route(path: '/conditional-fields', name: 'form_demo_conditional_fields', methods: ['GET', 'POST'])]
    public function conditionalFieldsDemo(Request $request): Response
    {
        $data = new ConditionalFieldsDemoData();
        $form = $this->createForm(ConditionalFieldsDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid() && $request->request->has('events_submit')) {
            $submitted = $form->getData();
        }

        $buildMode = $request->query->getString('account_mode', 'individual');
        if (!in_array($buildMode, ['individual', 'company'], true)) {
            $buildMode = 'individual';
        }
        $buildForm = $this->createForm(BuildTimeConditionalDemoType::class, null, [
            'account_mode' => $buildMode,
        ]);
        $buildForm->handleRequest($request);
        $buildSubmitted = null;
        if ($buildForm->isSubmitted() && $buildForm->isValid() && $request->request->has('build_submit')) {
            $buildSubmitted = $buildForm->getData();
        }

        return $this->render('form_demo/conditional_fields.html.twig', [
            'form'            => $form,
            'submitted'       => $submitted,
            'build_form'      => $buildForm,
            'build_mode'      => $buildMode,
            'build_submitted' => $buildSubmitted,
        ]);
    }

    /**
     * Conditional fields with Symfony UX Live Component (same FormEvents form type).
     */
    #[Route(path: '/conditional-fields-live', name: 'form_demo_conditional_fields_live', methods: ['GET'])]
    public function conditionalFieldsLiveDemo(): Response
    {
        return $this->render('form_demo/conditional_fields_live.html.twig');
    }

    /**
     * Kit API patterns — FormKitAbstractType (snake_case) + named config profile.
     */
    #[Route(path: '/kit-api-patterns', name: 'form_demo_kit_api_patterns', methods: ['GET', 'POST'])]
    public function kitApiPatternsDemo(Request $request): Response
    {
        $snakeForm = $this->createForm(SnakeCaseKitDemoType::class);
        $snakeForm->handleRequest($request);
        $snakeSubmitted = null;
        if ($snakeForm->isSubmitted() && $snakeForm->isValid() && $request->request->has('snake_submit')) {
            $snakeSubmitted = $snakeForm->getData();
        }

        $namedForm = $this->createForm(NamedConfigDemoType::class);
        $namedForm->handleRequest($request);
        $namedSubmitted = null;
        if ($namedForm->isSubmitted() && $namedForm->isValid() && $request->request->has('named_submit')) {
            $namedSubmitted = $namedForm->getData();
        }

        return $this->render('form_demo/kit_api_patterns.html.twig', [
            'snake_form'      => $snakeForm,
            'snake_submitted' => $snakeSubmitted,
            'named_form'      => $namedForm,
            'named_submitted' => $namedSubmitted,
        ]);
    }

    /**
     * CKEditor demo — FOSCKEditorBundle (CKEditor 4) with addCKEditorField().
     */
    #[Route(path: '/ckeditor', name: 'form_demo_ckeditor', methods: ['GET', 'POST'])]
    public function ckeditorDemo(Request $request): Response
    {
        $data = new CkeditorDemoData();
        $form = $this->createForm(CkeditorDemoType::class, $data);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }

        return $this->render('form_demo/ckeditor.html.twig', [
            'form'      => $form,
            'submitted' => $submitted,
        ]);
    }

    /**
     * UX Autocomplete demo — {@see ChoiceType} with {@code autocomplete => true} (Tom Select) and {@see FormOptionsTrait::addAutocompleteField()}.
     */
    #[Route(path: '/ux-autocomplete', name: 'form_demo_ux_autocomplete', methods: ['GET', 'POST'])]
    public function uxAutocompleteDemo(Request $request): Response
    {
        $data = new AutocompleteDemoData();
        $form = $this->createForm(AutocompleteDemoType::class, $data);
        $form->handleRequest($request);
        $submitted           = null;
        $submittedNormalized = null;
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AutocompleteDemoData $submitted */
            $submitted           = $form->getData();
            $submittedNormalized = [
                'topic'  => $submitted->topic,
                'skills' => $submitted->skills,
            ];
        }

        return $this->render('form_demo/ux_autocomplete.html.twig', [
            'form'                 => $form,
            'submitted'            => $submitted,
            'submitted_normalized' => $submittedNormalized,
        ]);
    }

    /**
     * Multi-step form demo — wizard built from array (contact → address → confirm).
     */
    #[Route(path: '/multistep', name: 'form_demo_multistep', methods: ['GET', 'POST'])]
    public function multistepFormDemo(Request $request): Response
    {
        $wizardName = 'demo_wizard';
        $wizard     = $this->wizardFactory->create(self::MULTISTEP_DEFINITION, $wizardName);

        if ($request->query->get('reset')) {
            $wizard->reset();

            return $this->redirectToRoute('form_demo_multistep', ['_locale' => $request->getLocale()]);
        }

        if ($wizard->isComplete()) {
            return $this->render('form_demo/multistep_summary.html.twig', [
                'wizard'      => $wizard,
                'wizard_name' => $wizardName,
            ]);
        }

        $stepKey  = $wizard->getCurrentStepKey();
        $stepData = $wizard->getCollectedData()[$stepKey] ?? [];
        $form     = $this->multiStepFormBuilder->createStepForm(
            $wizardName,
            $stepKey,
            $wizard->getStepFields($stepKey),
            $stepData,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wizard->setStepData($stepKey, $form->getData());
            $wizard->advance();

            return $this->redirectToRoute('form_demo_multistep', ['_locale' => $request->getLocale()]);
        }

        return $this->render('form_demo/multistep.html.twig', [
            'form'   => $form,
            'wizard' => $wizard,
        ]);
    }

    /**
     * Builds a form in the controller with Form Kit convention (label/placeholder/help from config).
     */
    private function createControllerForm(): FormInterface
    {
        $builder = $this->createFormBuilder([
            'notifySwitch' => 1,
            'topic'        => 'support',
        ]);
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->setFormKitFormName('controller_contact');
        $this->addTextType($builder, 'name', $rowHalf);
        $this->addEmailType($builder, 'email', $rowHalf);
        $this->addSelectType($builder, 'topic', array_merge($rowHalf, [
            'choices' => [
                'Support' => 'support',
                'Sales'   => 'sales',
                'Other'   => 'other',
            ],
        ]));
        $this->addChoiceRadiosType($builder, 'priority', array_merge($rowHalf, [
            'choices' => [
                'Low'    => 'low',
                'Normal' => 'normal',
                'High'   => 'high',
            ],
        ]));
        $this->addTextareaType($builder, 'message', $rowFull);
        $this->addSwitchType($builder, 'notifySwitch', array_merge($rowHalf, [
            'switch_value' => 1,
        ]));
        $this->addMoneyType($builder, 'budget_cents', array_merge($rowHalf, [
            'required' => false,
        ]));

        return $builder->getForm();
    }
}
