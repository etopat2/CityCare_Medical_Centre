<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditTrailService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Provides the audit trail middleware application component and its related behavior.
 */
class AuditTrailMiddleware
{
    /**
     * Initializes audit trail middleware behavior with its required dependencies and input state.
     */
    public function __construct(private AuditTrailService $auditTrail) {}

    /**
     * Runs the middleware or command workflow and returns its result.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $actorBefore = $request->user();

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $this->persistSafely($request, $statusCode, $actorBefore instanceof User ? $actorBefore : null);

            throw $exception;
        }

        $actorAfter = $request->user();
        $actor = $actorAfter instanceof User ? $actorAfter : ($actorBefore instanceof User ? $actorBefore : null);
        $this->persistSafely($request, $response->getStatusCode(), $actor);

        return $response;
    }

    /** Writes the audit record defensively so an audit storage failure does not break the user's completed request. */
    private function persistSafely(Request $request, int $statusCode, ?User $actor): void
    {
        try {
            $this->auditTrail->record($request, $statusCode, $actor);
        } catch (Throwable $exception) {
            Log::error('An audit event could not be saved.', [
                'route_name' => $request->route()?->getName(),
                'exception' => $exception::class,
            ]);
        }
    }
}
