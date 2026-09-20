<?php

namespace App\Foundation\Framework\Exception;

use App\Foundation\Framework\Http\Response\Envelope;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Domain "not found" → HTTP 404. Only fires when rendering an HTTP
        // response; under console/queue the exception propagates untouched.
        $this->renderable(function (ResourceNotFoundException $e, $request) {
            return $this->prepareResponse($request, new NotFoundHttpException($e->getMessage(), $e));
        });
    }

    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return Envelope::failed($exception->getMessage(), $exception->errors(), $exception->status)
            ->toResponse($request);
    }
}
