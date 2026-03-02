<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\DemoContactType;
use App\Form\DropzoneDemoType;
use App\Form\ExampleFormType;
use App\Form\NestedFormDemoType;
use App\Form\SearchFormType;
use App\Form\TranslationsDemoType;
use App\Model\ContactWithAddress;
use App\Model\DemoTranslatableItem;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\MultiStepFormBuilder;
use Nowo\FormKitBundle\Form\MultiStepWizardSessionFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

/**
 * Form Kit demo: FormType example and form built in controller example.
 */
class FormKitDemoController extends AbstractController
{
    private const MULTISTEP_DEFINITION = [
        'contact' => [
            'label' => 'Contact',
            'fields' => [
                'fullName' => TextType::class,
                'email' => EmailType::class,
            ],
        ],
        'address' => [
            'label' => 'Address',
            'fields' => [
                'street' => TextType::class,
                'number' => TextType::class,
                'floor' => TextType::class,
                'postalCode' => TextType::class,
                'city' => TextType::class,
                'province' => TextType::class,
            ],
        ],
        'confirm' => [
            'label' => 'Confirm',
            'fields' => [],
        ],
    ];

    public function __construct(
        private readonly FormOptionsMerger $formOptionsMerger,
        private readonly MultiStepFormBuilder $multiStepFormBuilder,
        private readonly MultiStepWizardSessionFactory $wizardFactory
    ) {
    }

    /**
     * Index: explanatory cards linking to each demo page (no forms on home).
     */
    #[Route(path: '/', name: 'form_demo_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('form_demo/index.html.twig');
    }

    #[Route(path: '/form-type', name: 'form_demo_form_type', methods: ['GET', 'POST'])]
    public function formTypeExample(Request $request): Response
    {
        $form = $this->createForm(DemoContactType::class);
        $form->handleRequest($request);

        return $this->render('form_demo/form_type_example.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/controller-form', name: 'form_demo_controller_form', methods: ['GET', 'POST'])]
    public function controllerFormExample(Request $request): Response
    {
        $form = $this->createControllerForm();
        $form->handleRequest($request);

        return $this->render('form_demo/controller_form_example.html.twig', [
            'form' => $form,
        ]);
    }

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
            'form' => $form,
            'submitted' => $submitted,
        ]);
    }

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
            'form' => $form,
            'submitted' => $submitted,
        ]);
    }

    #[Route(path: '/dropzone', name: 'form_demo_dropzone', methods: ['GET', 'POST'])]
    public function dropzoneDemo(Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $defaultImagePath = $projectDir . '/public/images/demo-sample.jpg';
        $defaultImageUrl = is_file($defaultImagePath) ? $request->getUriForPath('/images/demo-sample.jpg') : null;

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
            'form' => $form,
            'uploaded' => $uploaded,
            'default_image_url' => $defaultImageUrl,
        ]);
    }

    #[Route(path: '/cropper', name: 'form_demo_cropper', methods: ['GET', 'POST'])]
    public function cropperDemo(Request $request, CropperInterface $cropper): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $imagePath = $projectDir . '/public/images/demo-sample.jpg';
        $imageUrlPath = '/images/demo-sample.jpg';
        $sampleExists = is_file($imagePath);
        $imageUrl = $request->getUriForPath($imageUrlPath);
        $form = null;
        $submitted = null;
        if ($sampleExists) {
            $crop = $cropper->createCrop($imagePath);
            $crop->setCroppedMaxSize(800, 600);
            $form = $this->createFormBuilder(['crop' => $crop])
                ->add('crop', CropperType::class, [
                    'public_url' => $imageUrl,
                    'cropper_options' => ['aspectRatio' => 4 / 3],
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $submitted = true;
            }
        }
        return $this->render('form_demo/cropper.html.twig', [
            'form' => $form,
            'sample_exists' => $sampleExists,
            'image_path' => $imagePath,
            'submitted' => $submitted,
        ]);
    }

    #[Route(path: '/translations', name: 'form_demo_translations', methods: ['GET', 'POST'])]
    public function translationsDemo(Request $request): Response
    {
        $item = new DemoTranslatableItem();
        $form = $this->createForm(TranslationsDemoType::class, $item);
        $form->handleRequest($request);
        $submitted = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
        }
        return $this->render('form_demo/translations.html.twig', ['form' => $form, 'submitted' => $submitted]);
    }

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
        return $this->render('form_demo/nested.html.twig', ['form' => $form, 'submitted' => $submitted]);
    }

    #[Route(path: '/multistep', name: 'form_demo_multistep', methods: ['GET', 'POST'])]
    public function multistepFormDemo(Request $request): Response
    {
        $wizardName = 'demo_wizard';
        $wizard = $this->wizardFactory->create(self::MULTISTEP_DEFINITION, $wizardName);
        if ($request->query->get('reset')) {
            $wizard->reset();
            return $this->redirectToRoute('form_demo_multistep');
        }
        if ($wizard->isComplete()) {
            return $this->render('form_demo/multistep_summary.html.twig', ['wizard' => $wizard, 'wizard_name' => $wizardName]);
        }
        $stepKey = $wizard->getCurrentStepKey();
        $stepData = $wizard->getCollectedData()[$stepKey] ?? [];
        $form = $this->multiStepFormBuilder->createStepForm($wizardName, $stepKey, $wizard->getStepFields($stepKey), $stepData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wizard->setStepData($stepKey, $form->getData());
            $wizard->advance();
            return $this->redirectToRoute('form_demo_multistep');
        }
        return $this->render('form_demo/multistep.html.twig', ['form' => $form, 'wizard' => $wizard]);
    }

    private function createControllerForm(): FormInterface
    {
        $formName = 'controller_contact';
        $builder = $this->createFormBuilder();
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $builder->add('name', TextType::class, $this->formOptionsMerger->resolve($formName, 'name', TextType::class, $rowHalf));
        $builder->add('email', EmailType::class, $this->formOptionsMerger->resolve($formName, 'email', EmailType::class, $rowHalf));
        $builder->add('message', TextareaType::class, $this->formOptionsMerger->resolve($formName, 'message', TextareaType::class, $rowFull));

        return $builder->getForm();
    }
}
