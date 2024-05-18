<?php
/**
 * Locale controller.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LocaleController extends AbstractController
{
    /**
     * @Route("/change-locale/{locale}", name="change_locale")
     */
    public function changeLocale(string $locale, Request $request, SessionInterface $session): RedirectResponse
    {
        // Store the locale in the session
        $session->set('_locale', $locale);

        // Redirect back to the referring page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }
}