<?php

namespace App\Security;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskLinkerVoter extends Voter
{

    public function __construct(
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository,
    )
    {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'acces_projet' || $attribute === 'acces_tache';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if($attribute === 'acces_projet'){
            $projet = $this->projectRepository->find($subject);
        } else {
            $tache = $this->taskRepository->find($subject);
            $projet = $tache?->getProject();
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface ||!$projet)
        {
            return false;
        }

        return $user->isAdmin() || $projet->getUsers()->contains($user);
    }
}