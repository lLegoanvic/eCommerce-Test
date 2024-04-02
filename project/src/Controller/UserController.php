<?php

namespace App\Controller;

use App\Form\PasswordForgotFormType;
use App\Form\ResetPasswordFormType;
use App\Form\UserFormType;
use App\Form\RegistrationFormType;
use App\Helper\UserHelper;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class UserController extends AbstractController
{
    protected OrderRepository $orderRepository;
    private EmailVerifier $emailVerifier;
    public EntityManagerInterface $entityManager;

    /**
     *dans le construc on peut definir des variable qu'on peut utiliser dans toute la classe via this->var
     * symfony autoload par defaut toute les classe qui lui sont propore
     *si tu veux load tes class /helper dans le construct il faudra alors le definir dans le fichier
     * services.yml ( on verra ca plus tard :p )
     */
    public function __construct(EmailVerifier $emailVerifier, EntityManagerInterface $entityManager, OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->emailVerifier = $emailVerifier;
        $this->entityManager = $entityManager;
    }

    // apicontroller
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }
        $token = random_int();
        return $this->json([
            'user' => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }


    //logincontroller
    #[Route('/login', name: 'app_login')]
    public function userLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('index');
        }
        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException();
    }


    //registrationcontroller
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            $this->sendVerificationMail($user);
            // add un flash ( petite popup verte reouge etc )
            $this->addFlash('success', 'valide ton mail avant de te connecter ');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }

    //profilcontroller
    #[Route('/profil', name: 'app_profil')]
    public function profil(AuthenticationUtils $authenticationUtils): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }
        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();
        return $this->render('profil/profil.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/profil/update/', name: 'profil_update')]
    public function updateProfil(Request $request): Response
    {

        $user = $this->getUser();
        /**
         * on envoir en deuxieme param du create form l'entité user deja prérempli avec l'user courant
         */
        $form = $this->createForm(UserFormType::class, $user);

        // dump = affiche moi une ou x var mais continu le code
//        dump($user); // user avant modif
        $form->handleRequest($request);
        // dd affiche les var mais stop l'exec
//        dd($user);// user apres modif

        if ($form->isSubmitted() && $form->isValid()) {


            // ca pu ca par contre :( ah c pas ça que je voulais check

            $unitOfWork = $this->entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changeSet = $unitOfWork->getEntityChangeSet($user);
            // change set = tableau de ce qui a changé dans mon objet
            // $changeSet['infos'] si present alors
            // $changeSet['infos'][0] = ancienne val
            // $changeSet['infos'][1] = nouvelle val

            // dans ton cas tu veux voir si email a changé
            // si email est present dans le changeSet c'es qu'il a bougé alors on veut reset le isverified et renvoyé le mail de validation
            if (isset($changeSet['email'])) {

                //    setter = faut y mettre qq chose dedans comme le le setisverified
                //  tu n'a pas besoin de setter sur les champ du form comparé au is verified qui n'y est pas
                //  par ce que l'user tu l'as deja passé en param du create form et le handleRequest se charge de mettre a jour l'obet :D
                $user->setIsVerified(false);
                // ca c'est pas au bon endroit
                // pas au bon endroit pck en fait persist lui sauve dans l'OBJET les changement de valeurs
                // flush lui insert / update dans la db
                // donc le mieux et de faire x persist ( mais le moins possible )
                // et UN SEUL flush a la fin pour persist en db
                // sinon tu va faire x update / create c'est pas tres opti :D
                // do anything else you need here, like send an email
                $this->sendVerificationMail($user);
                // add un flash ( petite popup verte reouge etc )
                $this->addFlash('success', 'valide ton mail avant de te connecter ');

                // ici tu redirect vers le login
                // MAIS ton presist / flush est apres
                // donc ton user est pas save dans la db
                // la on persist et on flush et on veut pas redirect vers login
                // il viens de changer son email et doit le revalider on va le deco
                // et le logout va le renvoyé vers la bonne page par la suite
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->redirectToRoute('app_logout');
            }


            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/update.html.twig', [
            'UserForm' => $form->createView(),
        ]);
    }

    // les fonction sans route a la fin ( norme du code )

    #[Route("/forgot-password", name: "forgot_password")]
    public function forgotPassword(
        Request                 $request,
        UserRepository          $userRepository,
        EntityManagerInterface  $entityManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface         $mailer
    ): Response
    {
        $form = $this->createForm(PasswordForgotFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $user = $userRepository->findOneByEmail($form->get('email')->getData());
            if ($user) {
                return $this->extracted($tokenGenerator, $user, $entityManager, $mailer); // Redirect to home or login page
            }
            $this->addFlash('error', 'Email not found. Please check your email address.');

        }

        return $this->render('password_reset/forgot_password.html.twig', [
            'PasswordForgotForm' => $form->createView(),
        ]);
    }

    #[Route('/forgot_password/{token}', name: 'reset_pass')]
    public function resetPass(string                      $token, Request $request,
                              UserRepository              $userRepository,
                              EntityManagerInterface      $entityManager,
                              UserPasswordHasherInterface $userPasswordHasher
    ): Response
    {
        $user = $userRepository->findOneByResetToken($token);

        if ($user) {
            $form = $this->createForm(ResetPasswordFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setResetToken('');
                $user->setPassword($userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData())
                );
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'mot de passe changé avec succès !');
                return $this->redirectToRoute('app_profil');
            }

            return $this->render('password_reset/reset_password.html.twig', ['PasswordForgotForm' => $form->createView()]);
        }
        $this->addFlash('danger', 'Jeton invalide');
        return $this->redirectToRoute('app_profil');
    }

    #[Route("/update-password", name: "update_password")]
    public function updatePassword(
        Security                $security,
        EntityManagerInterface  $entityManager,
        MailerInterface         $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response
    {
        $user = $security->getUser();

        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_profil');
        }

        return $this->extracted($tokenGenerator, $user, $entityManager, $mailer); // Rediriger vers la page de connexion
    }

    #[Route('/profil/ordersView', name: 'profil_ordersView')]
    public function ordersView()
    {
        $user = $this->getUser();
        $orders = $user->getOrders();

        return $this->render('profil/ordersView.html.twig', ['orders' => $orders, 'user' => $user]);
    }

    #[Route('/profil/order/{id}', name: 'profil_order')]
    public function order($id)
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            return $this->redirectToRoute('app_profil');
        }
        return $this->render('profil/order.html.twig', ['order' => $order]);


    }


    private function sendResetPasswordEmail($user, $url, MailerInterface $mailer): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('biggoodbassist@gmail.com', 'Gros bras corp'))
            ->to($user->getEmail())
            ->subject('Please reset your password')
            ->htmlTemplate('password_reset/reset_password_mail.html.twig')
            ->context(['url' => $url]);

        $mailer->send($email);
    }

    private function sendVerificationMail(User $user): void
    {
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('biggoodbassist@gmail.com', 'Gros bras corp'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    /**
     * @param TokenGeneratorInterface $tokenGenerator
     * @param $user
     * @param EntityManagerInterface $entityManager
     * @param MailerInterface $mailer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function extracted(TokenGeneratorInterface $tokenGenerator, $user, EntityManagerInterface $entityManager, MailerInterface $mailer): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $token = $tokenGenerator->generateToken();
        $user->setResetToken($token);
        $entityManager->persist($user);
        $entityManager->flush();
        $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->sendResetPasswordEmail($user, $url, $mailer);
        $this->addFlash('success', 'Check your email for instructions to reset your password.');
        return $this->redirectToRoute('app_profil');
    }


}