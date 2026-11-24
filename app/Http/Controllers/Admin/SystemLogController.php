<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Presents application log entries and authorized log exports for administrators.
 */
class SystemLogController extends Controller
{
    /**
     * Builds the system log listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'in:EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG'],
        ]);
        $entries = $this->filteredEntries($filters);
        $levelCounts = $entries->countBy('level');

        return view('admin.system-logs.index', [
            'entries' => $entries->take(250),
            'filters' => $filters,
            'levelCounts' => $levelCounts,
            'logFileAvailable' => is_file($this->logPath()),
        ]);
    }

    /** Streams the filtered recent log entries as CSV with spreadsheet formula prefixes neutralized. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'in:EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG'],
        ]);
        $entries = $this->filteredEntries($filters);

        return response()->streamDownload(function () use ($entries): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Timestamp', 'Channel', 'Level', 'Message']);

            foreach ($entries as $entry) {
                fputcsv($stream, array_map($this->safeCell(...), [$entry['timestamp'], $entry['channel'], $entry['level'], $entry['message']]));
            }

            fclose($stream);
        }, 'citycare-system-logs-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Applies the selected severity and text filters to the bounded recent log tail. */
    private function filteredEntries(array $filters): Collection
    {
        $query = trim((string) ($filters['q'] ?? ''));
        $level = $filters['level'] ?? null;

        return collect($this->readRecentEntries(5000))
            ->when($level !== null, fn (Collection $entries): Collection => $entries->where('level', $level))
            ->when($query !== '', fn (Collection $entries): Collection => $entries->filter(fn (array $entry): bool => Str::contains(Str::lower($entry['message'].' '.$entry['channel']), Str::lower($query))))
            ->values();
    }

    /**
     * Read a bounded tail of the single-file Laravel log without loading the full file.
     *
     * @return array<int, array{timestamp: string, channel: string, level: string, message: string}>
     */
    private function readRecentEntries(int $limit): array
    {
        $path = $this->logPath();

        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);
        $buffer = '';

        while ($position > 0 && substr_count($buffer, "\n") <= $limit * 5) {
            $chunkSize = min(8192, $position);
            $position -= $chunkSize;
            fseek($handle, $position);
            $buffer = fread($handle, $chunkSize).$buffer;
        }

        fclose($handle);

        $entries = [];

        foreach (preg_split('/\R/', $buffer) ?: [] as $line) {
            if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s+(?<channel>[^.]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/', $line, $matches) !== 1) {
                continue;
            }

            $message = preg_replace('/(?i)\b(password|token|secret|authorization|otp|verification[_ -]?code|api[_ -]?key|app[_ -]?password)\b(\s*[=:]\s*)(?:"[^"]*"|\'[^\']*\'|\S+)/', '$1$2[redacted]', $matches['message']) ?? $matches['message'];
            $entries[] = [
                'timestamp' => $matches['timestamp'],
                'channel' => $matches['channel'],
                'level' => $matches['level'],
                'message' => Str::limit($message, 1200),
            ];
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    /** Resolves the configured single-file log path, falling back to Laravel's default log location. */
    private function logPath(): string
    {
        return (string) config('logging.channels.single.path', storage_path('logs/laravel.log'));
    }

    /** Prefixes formula-leading characters so exported log text cannot execute as a spreadsheet formula. */
    private function safeCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[=+@\-\t\r]/u', $cell) === 1 ? "'".$cell : $cell;
    }
}
