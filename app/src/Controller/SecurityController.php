<?php
/**
 * Security controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @return void
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="app_register")
     *
     * @param Request                     $request
     * @param UserPasswordHasherInterface $passwordHasher
     *
     * @return Response
     */
    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password before storing it in the database
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            // Save the user to the database
            $entityManager = $this->managerRegistry->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // Redirect to the appropriate page after successful registration
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/account", name="app_account")
     *
     * @param Security $security
     *
     * @return Response
     */
    #[Route(path: '/account', name: 'app_account')]
    public function changeAccountData(Security $security): Response
    {
        // Get the currently logged-in user
        /** @var PasswordAuthenticatedUserInterface $user */
        $user = $security->getUser();

        return $this->render('security/account.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @param User $user
     *
     * @return Response
     */
    #[Route('/useraccount/{id}', name: 'app_useraccount', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    public function accountData(User $user): Response
    {
        return $this->render('security/account.html.twig', [
            'user' => $user,
        ]);
    }
}
