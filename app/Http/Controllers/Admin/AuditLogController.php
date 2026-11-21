<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'event' => ['nullable', 'string', 'max:160'],
            'actor' => ['nullable', 'integer'],
            'outcome' => ['nullable', 'in:success,failed'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = $this->filteredQuery($filters)->paginate(40)->withQueryString();
        $todayCount = DB::table('audit_logs')->whereDate('created_at', today())->count();
        $failedCount = DB::table('audit_logs')->where('status_code', '>=', 400)->where('created_at', '>=', now()->subDays(7))->count();
        $actorCount = DB::table('audit_logs')->where('created_at', '>=', now()->subDays(7))->distinct('actor_user_id')->count('actor_user_id');
        $actors = DB::table('audit_logs')->whereNotNull('actor_user_id')->select('actor_user_id', 'actor_name')->distinct()->orderBy('actor_name')->limit(250)->get();

        return view('admin.audit.index', compact('logs', 'filters', 'todayCount', 'failedCount', 'actorCount', 'actors'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'event' => ['nullable', 'string', 'max:160'],
            'actor' => ['nullable', 'integer'],
            'outcome' => ['nullable', 'in:success,failed'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $rows = $this->filteredQuery($filters)->limit(10000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Timestamp', 'Actor', 'Event', 'Subject', 'Record ID', 'Method', 'Path', 'Status', 'IP address', 'Changed fields']);

            foreach ($rows as $row) {
                $metadata = json_decode((string) $row->metadata, true) ?: [];
                fputcsv($stream, array_map($this->safeCell(...), [
                    $row->created_at,
                    $row->actor_name,
                    $row->event,
                    $row->subject_type,
                    $row->subject_id,
                    $row->method,
                    $row->path,
                    $row->status_code,
                    $row->ip_address,
                    implode(', ', $metadata['field_names'] ?? []),
                ]));
            }

            fclose($stream);
        }, 'citycare-audit-trail-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(array $filters): Builder
    {
        return DB::table('audit_logs')
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.$term.'%';
                $query->where(fn (Builder $query) => $query->where('actor_name', 'like', $like)
                    ->orWhere('event', 'like', $like)
                    ->orWhere('subject_type', 'like', $like)
                    ->orWhere('subject_id', 'like', $like)
                    ->orWhere('path', 'like', $like));
            })
            ->when($filters['event'] ?? null, fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($filters['actor'] ?? null, fn (Builder $query, int $actorId) => $query->where('actor_user_id', $actorId))
            ->when(($filters['outcome'] ?? null) === 'success', fn (Builder $query) => $query->whereBetween('status_code', [200, 399]))
            ->when(($filters['outcome'] ?? null) === 'failed', fn (Builder $query) => $query->where('status_code', '>=', 400))
            ->when($filters['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function safeCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[=+@\-\t\r]/u', $cell) === 1 ? "'".$cell : $cell;
    }
}
