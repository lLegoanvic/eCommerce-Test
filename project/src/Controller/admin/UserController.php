<?php

namespace App\Controller\admin;


use App\Form\AdminFormType;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class UserController extends AbstractController
{

    public EntityManagerInterface $entityManager;
    private EmailVerifier $emailVerifier;
    private UserRepository $userRepository;


    public function __construct(UserRepository $userRepository,EmailVerifier $emailVerifier,EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->emailVerifier = $emailVerifier;
        $this->entityManager = $entityManager;
    }


    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $users = $this->userRepository->findBy([], ['id' => 'asc']);

        return $this->render('/admin/users.html.twig', compact('users'));
    }

    #[Route('/update/{id}', name: 'update')]
    #[ParamConverter('user',User::class)]
    public function update(Request $request, User $user): Response
    {

        $form = $this->createForm(AdminFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $unitOfWork = $this->entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changeSet = $unitOfWork->getEntityChangeSet($user);
            if(isset($changeSet['email'])){
                $user->setIsVerified(false);
                $this->sendVerificationMail($user);
                $this->addFlash('success','valide ton mail avant de te connecter ');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->redirectToRoute('admin_users_index');
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/update.html.twig', [
            'AdminForm' => $form->createView(),
            'user'=>$user
        ]);
    }


    #[Route('/createUser', name: 'create_user')]
    public function createUser(Request $request): Response
    {
        // todo use this entityManager
        $user = new User();
        $form = $this->createForm(AdminFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du mot de passe
            $password = $this->generateRandomPassword();
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Stockage du mot de passe haché dans la base de données avec l'utilisateur

            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->sendVerificationMail($user, $password);
            $this->addFlash('success','Utilisateur ajouté avec succès.');
            return $this->render('admin/user_create.html.twig', [
                'AdminForm' => $form->createView(),
            ]);
        }

        return $this->render('admin/user_create.html.twig', [
            'AdminForm' => $form->createView(),
        ]);
    }



    // Fonction pour générer un mot de passe aléatoire

    /**
     * @throws RandomException
     */
    private function generateRandomPassword(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function sendVerificationMail(User $user,?string $password = null): void
    {

        $email = (new TemplatedEmail())
                ->from(new Address('biggoodbassist@gmail.com', 'Gros bras corp'))
        ->to($user->getEmail())
        ->subject('Please Confirm your Email')
        ->htmlTemplate('admin/Create_user_email.html.twig');

        if($password !== null ){
          $email->context(['user'=>$user,"password"=>$password]);
        }else{
            $email->context(['user'=>$user]);

        }

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,$email);
    }

}