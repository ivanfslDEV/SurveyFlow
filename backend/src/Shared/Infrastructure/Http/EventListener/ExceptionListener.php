<?php

namespace App\Shared\Infrastructure\Http\EventListener;

use App\Shared\Domain\Exception\InvalidInputException;
use App\Shared\Domain\Exception\ResourceConflictException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: 'kernel.exception')]
class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $errors = [];
            foreach ($previous->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $event->setResponse(new JsonResponse([
                'message' => 'Validation Failed',
                'errors' => $errors,
            ], 422));
            return;
        }

        if ($exception instanceof ResourceNotFoundException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], 404));
            return;
        }

        if ($exception instanceof ResourceConflictException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], 409));
            return;
        }

        if ($exception instanceof InvalidInputException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], 422));
            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode()));
            return;
        }

        $this->logger->error($exception->getMessage(), [
            'exception' => $exception,
        ]);

        if ($this->environment === 'dev') {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ], 500));
        } else {
            $event->setResponse(new JsonResponse([
                'message' => 'Internal Server Error',
            ], 500));
        }
    }
}
