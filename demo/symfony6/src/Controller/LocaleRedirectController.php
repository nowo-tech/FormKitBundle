<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Redirects / to the default locale home so all demo URLs are /{locale}/...
 */
final class LocaleRedirectController extends AbstractController
{
    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): RedirectResponse
    {
        $default = (string) $this->getParameter('kernel.default_locale');

        return $this->redirectToRoute('form_demo_index', ['_locale' => $default !== '' ? $default : 'en'], 302);
    }
}
