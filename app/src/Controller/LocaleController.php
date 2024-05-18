<?php
/**
 * Locale controller.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class LocaleController extends AbstractController
{
    /**
     * @Route("/change-locale/{locale}", name="change_locale")
     */
    public function changeLocale($locale, Request $request, RouterInterface $router): RedirectResponse
    {
        // Set the locale in the session
        $request->getSession()->set('_locale', $locale);

        // Get the last visited URL
        $referer = $request->headers->get('referer');
        $url = $referer ? $referer : $router->generate('homepage'); // 'homepage' should be replaced with your actual home route name

        return new RedirectResponse($url);
    }
}
