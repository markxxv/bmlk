<?php

namespace App\Http\Controllers;

use App\Models\InvoiceExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Storage::disk(InvoiceExport::DISK)->download($export->file_path, $export->downloadName(), [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
