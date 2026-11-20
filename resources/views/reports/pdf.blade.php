<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $title }}</title><style>body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:11px}h1{font-size:20px;margin:0 0 6px}.meta{color:#475569;margin-bottom:18px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #cbd5e1;padding:7px;text-align:left}th{background:#f1f5f9} .summary{margin-bottom:18px}.summary span{display:inline-block;margin-right:16px}</style></head><body>
<h1>CityCare Medical Centre · {{ $title }}</h1><div class="meta">Generated {{ $generatedAt->format('j M Y H:i') }} by {{ $generatedBy->name }}</div>
<div class="summary">@foreach($summary as $label => $value)<span><strong>{{ $label }}:</strong> {{ is_numeric($value) && str_contains($label, '(UGX)') ? 'UGX '.number_format((float) $value) : $value }}</span>@endforeach</div>
<table><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}">No matching records.</td></tr>@endforelse</tbody></table>
</body></html>
