<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Entity\Project;
use App\Entity\Task;
use App\Form\ProjectType;
use App\Repository\StatusRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{

    public function __construct(
        private ProjectRepository $projectRepository, 
        private UserRepository $userRepository, 
        private StatusRepository $statusRepository, 
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/projects', name: 'app_allProjects')]
    public function allProjects(): Response
    {
        if($this->getUser()->isAdmin()) {
            $projects = $this->projectRepository->findBy([
                'archived' => false,
            ]);
        } else {
            $projects = $this->getUser()->getProjects()->filter(function (Project $project) { return !$project->isArchived(); });
        }
    
        return $this->render('project/home.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/project/{id<\d+>}', name : 'app_project')]
    #[IsGranted('acces_projet', 'id')]
    public function project(int $id): Response {
        $project = $this->projectRepository->find($id);
        $statuses = $this->statusRepository->findAll();
        dump($project->getTasksByStatus(Task::STATUS_LABEL_TODO));

        if(!$project || $project->isArchived()) {
            return $this->redirectToRoute('app_allProjects');
        };

        return $this->render('project/project.html.twig',[
            'project' => $project,
            'statuses' => $statuses,
        ]);
    }

    #[Route('/project/add', name : 'app_project_add')]
    #[IsGranted('ROLE_ADMIN')]
    public function addProject(Request $request): Response {
        $project = new Project();
        $project->setArchived(false);
        $users = $this->userRepository->findAll();


        $form = $this->createForm(ProjectType::class, $project, [
            'users' => $users,
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
        
            $this->entityManager->persist($project);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_project', ['id' => $project->getId() ]);
        }

        return $this->render('project/add-project.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/project/{id}/edit', name : 'app_project_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editProject(Request $request, int $id) : Response {
        $project = $this->projectRepository->find($id);
        $users = $this->userRepository->findAll();
        $oldUsers = $project->getUsers()->map(fn($user) => $user->getId() )->toArray();

        $form = $this->createForm(ProjectType::class, $project, [
            'users' => $users,
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            $newUsers = $project->getUsers()->map(fn($user) => $user->getId() )->toArray();
            $userDeleted = array_values(array_diff($oldUsers, $newUsers));

            if($userDeleted){
                $this->taskRepository->deleteUser($project->getId(), $userDeleted);
            }

            return $this->redirectToRoute('app_project', ['id' => $project->getId() ]);
        }

        return $this->render('project/edit-project.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/project/{id}/archive', name : 'app_project_archive')]
    #[IsGranted('ROLE_ADMIN')]
    public function archiveProject(int $id) : Response {
        $project = $this->projectRepository->find($id);
        $project->setArchived(true);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_allProjects');
    }
}
