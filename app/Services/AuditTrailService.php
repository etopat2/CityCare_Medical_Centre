<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditTrailService
{
    public function record(Request $request, int $statusCode, ?User $actor = null): void
    {
        $route = $request->route();
        $actor ??= $request->user();
        $subject = null;

        foreach (['user', 'role', 'patient', 'doctor', 'appointment', 'payment'] as $parameter) {
            $candidate = $route?->parameter($parameter);

            if ($candidate instanceof Model) {
                $subject = $candidate;
                break;
            }
        }

        $routeName = $route?->getName();
        $event = $routeName !== null
            ? Str::limit($routeName, 160, '')
            : Str::limit($request->method().' '.$request->path(), 160, '');

        $fieldNames = collect(array_keys($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'code', 'token', 'portal_password'])))
            ->reject(fn (string $field): bool => Str::contains(Str::lower($field), ['password', 'token', 'secret', 'code']))
            ->values()
            ->all();
        $safeValues = [];
        $roles = $request->input('roles');
        $accessRules = $request->input('access');
        $permissionRules = $request->input('permissions');

        if (is_array($roles)) {
            $safeValues['roles'] = array_slice(array_values(array_filter($roles, 'is_numeric')), 0, 100);
        }

        foreach (['access' => $accessRules, 'permissions' => $permissionRules] as $key => $rules) {
            if (is_array($rules)) {
                $safeValues[$key] = collect($rules)
                    ->filter(fn (mixed $effect): bool => is_string($effect) && in_array($effect, ['inherit', 'allow', 'deny'], true))
                    ->take(100)
                    ->all();
            }
        }

        if ($request->has('is_active') && in_array($request->input('is_active'), [true, false, 0, 1, '0', '1'], true)) {
            $safeValues['is_active'] = (bool) $request->boolean('is_active');
        }

        if (is_string($request->input('status')) && preg_match('/^[a-z_]{1,30}$/', $request->input('status')) === 1) {
            $safeValues['status'] = $request->input('status');
        }

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
            'event' => $event,
            'subject_type' => $subject === null ? null : class_basename($subject),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'subject_name' => $subject !== null && isset($subject->name) ? Str::limit((string) $subject->name, 255, '') : null,
            'method' => $request->method(),
            'route_name' => $routeName,
            'path' => Str::limit('/'.$request->path(), 500, ''),
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'metadata' => json_encode(['field_names' => $fieldNames, 'safe_values' => $safeValues], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
