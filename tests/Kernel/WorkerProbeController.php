<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Kernel;

use Nowo\FormKitBundle\Form\GetFilterFormFactory;
use Nowo\FormKitBundle\Form\Type\SearchQueryType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Host-like action for the kept-alive worker test: builds a Form Kit search form from the query string and renders it.
 */
final readonly class WorkerProbeController
{
    public function __construct(
        private GetFilterFormFactory $filters,
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->filters->create(SearchQueryType::class, [], ['q' => (string) $request->query->get('q', '')]);
        $form->handleRequest($request);
        $html = $this->twig->createTemplate('<main data-user="{{ who }}">{{ form(form) }}</main>')->render([
            'form' => $form->createView(),
            'who'  => (string) $request->query->get('who', ''),
        ]);

        return new Response($html);
    }
}
