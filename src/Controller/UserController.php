<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager){
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
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
    public function registerUser(Request $request, UserPasswordHasherInterface $passwordHasher): Response{
        $user = new User();
        $user->setStatus('CDI');
        $user->setJoinOn(new \DateTime());

        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $password = $user->getPassword();
            $user->setPassword($passwordHasher->hashPassword($user, $password));

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
}