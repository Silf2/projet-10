<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Security;

class ErrorController extends AbstractController
{

    #[Route('/error', name: 'app_error')]
    public function showError(\Throwable $exception): Response
    {
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
            return $this->render('error/error.html.twig', [
                'errorCode' => $statusCode,
                'errorMessage' => $message,
            ]);
        } else {
            $statusCode = 500;
            $message = $exception->getMessage();
            return $this->render('error/error.html.twig', [
                'errorCode' => $statusCode,
                'errorMessage' => $message,
            ]);
        }
    }
}
