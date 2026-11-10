<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReviewAccessToken;
use App\Models\ReviewEmailOptOut;
use App\Services\ProductRatingWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewLinkController extends Controller
{
    public function show(string $token): View
    {
        $link = $this->link($token);

        if ($link->expires_at->isPast()) {
            abort(410, __('Ce lien a expiré. Vous pouvez retrouver vos évaluations dans votre espace client.'));
        }

        $order = $link->order->load('ratings');
        abort_unless($order->canBeRated() && count($order->rateableItems()) > 0, 404);

        $ids = collect($order->rateableItems())
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->all();

        $ratingProducts = Product::query()->withTrashed()->with('media')
            ->whereKey($ids)->get()->keyBy('id');

        return response()->view('reviews.show', compact('order', 'token', 'ratingProducts'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $request, string $token, ProductRatingWriter $writer): RedirectResponse
    {
        $link = $this->link($token);
        abort_if($link->expires_at->isPast(), 410);

        $data = $request->validate([
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $writer->save($link->order, $data['ratings'], tokenHash: hash('sha256', $token));

        return redirect()->route('reviews.show', ['token' => $token])
            ->with('success', __('Vos évaluations ont été enregistrées. Merci !'));
    }

    public function unsubscribeForm(Order $order): View
    {
        return response()->view('reviews.unsubscribe', [
            'order' => $order,
            'unsubscribed' => filled($order->email) && ReviewEmailOptOut::contains($order->email),
        ])->header('Cache-Control', 'private, no-store')
          ->header('Referrer-Policy', 'no-referrer');
    }

    public function unsubscribe(Request $request, Order $order): RedirectResponse
    {
        abort_unless(filled($order->email), 404);

        ReviewEmailOptOut::query()->firstOrCreate([
            'email_hash' => ReviewEmailOptOut::hashEmail($order->email),
        ]);

        return redirect()->to($request->fullUrl())
            ->with('success', __('Vous ne recevrez plus de demandes d’avis par e-mail.'));
    }

    private function link(string $token): ReviewAccessToken
    {
        return ReviewAccessToken::query()
            ->with('order')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
