<?php

namespace App\Http\Controllers;

use App\Models\InvoiceExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportDownloadController extends Controller
{
    public function __invoke(Request $request, InvoiceExport $export): StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        // Telegram links open this URL: send guests to the admin login and bring them back here.
        if (! $user) {
            return redirect()->guest(url('/admin/login'));
        }

        abort_unless($user->is_admin === true, 403);
        abort_unless($export->isReady(), 404);

        if (! Storage::disk(InvoiceExport::DISK)->exists($export->file_path)) {
            Log::error('Invoice export file is missing or not readable by the web user', [
                'export' => $export->id,
                'path' => Storage::disk(InvoiceExport::DISK)->path($export->file_path),
                'web_user' => function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? null) : null,
            ]);
            abort(404, 'The archive file is not available. Delete it and request a new one.');
        }

        return Storage::disk(InvoiceExport::DISK)->download($export->file_path, $export->downloadName(), [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
