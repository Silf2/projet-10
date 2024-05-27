<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\RoundBlockSizeMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

class UserController extends AbstractController{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager){
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if($this->IsGranted('ROLE_USER')){
            return $this->redirectToRoute('app_allProjects');
        }
        return $this->render('authentification/welcome.html.twig');
    }

    #[Route('/users', name: 'app_users')]
    public function allUsers(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('user/user.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit')]
    public function editUser(Request $request, int $id): Response {
        $user = $this->userRepository->find($id);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->entityManager->flush();

            return $this->redirectToRoute('app_users');
        }

        return $this->render('user/edit-user.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/user/{id}/delete', name: 'app_user_delete')]
    public function deleteUser(int $id): Response{
        $user = $this->userRepository->find($id);

        if(!$user){
            return $this->redirectToRoute('app_users');
        }

        foreach ($user->getTasks() as $task) {
            $user->removeTask($task);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_users');
    }

    #[Route('/register', name: 'app_register')]
    public function registerUser(Request $request, UserPasswordHasherInterface $passwordHasher, GoogleAuthenticatorInterface $googleAuth): Response
    {       
        if($this->IsGranted('ROLE_USER')){
            return $this->redirectToRoute('app_allProjects');
        }
        
        $user = new User();
        $user->setStatus('CDI');
        $user->setJoinOn(new \DateTime());
        $user->setRoles(['ROLE_USER']);

        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $password = $user->getPassword();
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setGoogleAuthenticatorSecret($googleAuth->generateSecret());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_allProjects');
        }

        return $this->render('authentification/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if($this->IsGranted('ROLE_USER')){
            return $this->redirectToRoute('app_allProjects');
        }
        
        $error = $authenticationUtils->getLastAuthenticationError();
        $email = $authenticationUtils->getLastUsername();

        return $this->render('authentification/login.html.twig', [
            'email' => $email,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        //Utile uniquement pour avoir une route de déconnexion, Symfony gère le reste.
    }

    #[Route('/2fa/qrcode', name: '2fa_qrcode')]
    public function displayGoogleAuthenticatorQrCode(GoogleAuthenticatorInterface $googleAuthenticator): Response
    {

        return new Response(Builder::create()
        ->writer(new PngWriter())
        ->writerOptions([])
        ->data($googleAuthenticator->getQRContent($this->getUser()))
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
        ->size(200)
        ->margin(0)
        ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
        ->build()->getString(), 200, ['Content-Type' => 'image/png']);
    }

    #[Route('/2fa', name: '2fa_login')]
    public function displayGoogleAuthenticator(): Response
    {
        return $this->render('authentification/2fa_form.html.twig', [
            'qrCode' => $this->generateUrl('2fa_qrcode'),
        ]);
    }
}