<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class WarehouseController extends Controller
{
    public function loginForm(): View|RedirectResponse
    {
        if (Auth::check() && (Auth::user()->is_admin || Auth::user()->is_warehouse)) {
            return redirect()->route('warehouse.orders.index');
        }

        return view('warehouse.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($credentials['email']))])
            ->where(fn ($query) => $query->where('is_admin', true)->orWhere('is_warehouse', true))
            ->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => __('Identifiants incorrects.')])->onlyInput('email');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('warehouse.orders.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('warehouse.login');
    }

    public function index(Request $request): View
    {
        $state = $request->query('state', 'all');
        if (! in_array($state, ['all', 'pending', 'processing'], true)) {
            $state = 'all';
        }

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 80);

        $base = Order::query()
            ->where('payment_status', 'paid')
            ->whereIn('status', ['pending', 'processing']);

        $counts = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'processing' => (clone $base)->where('status', 'processing')->count(),
        ];

        $orders = $base
            ->when($state !== 'all', fn ($query) => $query->where('status', $state))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(order_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(city) LIKE ?', [$term]);
                });
            })
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('warehouse.index', compact('orders', 'counts', 'state', 'search'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->payment_status === 'paid', 404);

        return view('warehouse.show', [
            'order' => $order,
            'labelReady' => $this->labelExists($order),
        ]);
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'completed', 'archived'])],
        ]);

        abort_unless($order->payment_status === 'paid', 404);

        $from = match ($data['status']) {
            'pending' => ['processing'],
            'processing' => ['pending'],
            'shipped' => ['pending', 'processing'],
            'completed' => ['shipped'],
            'archived' => ['shipped', 'completed'],
        };

        $query = Order::query()
            ->whereKey($order->id)
            ->where('payment_status', 'paid')
            ->whereIn('status', $from);

        // An order must never be marked shipped without an actual label.
        if ($data['status'] === 'shipped') {
            $query->whereNotNull('tracking_number');
        }

        if ($query->update(['status' => $data['status']]) !== 1) {
            return back()->withErrors(['status' => __('Actualisez la commande avant de modifier son statut.')]);
        }

        return back()->with('success', __('Statut mis à jour.'));
    }

    public function createLabel(Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === 'paid', 404);
        abort_unless(in_array($order->status, ['pending', 'processing'], true), 409);

        try {
            app(SendcloudController::class)->createLabel($order);
        } catch (Throwable $exception) {
            Log::warning('Warehouse Sendcloud label creation failed.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['label' => __('Étiquette indisponible : :message', ['message' => $exception->getMessage()])]);
        }

        if (! $this->labelExists($order->fresh())) {
            return back()->withErrors(['label' => __('Étiquette créée, mais le PDF est indisponible. Vérifiez Sendcloud.')]);
        }

        return redirect()->route('warehouse.orders.show', $order->order_number)
            ->with('success', __('Étiquette créée. Ouvrez le PDF pour imprimer.'));
    }

    public function label(Order $order): BinaryFileResponse
    {
        abort_unless($order->payment_status === 'paid', 404);
        abort_unless($this->labelExists($order), 404);

        return response()->file(
            Storage::disk('local')->path('sendcloud-labels/'.$order->id.'.pdf'),
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Disposition' => 'inline; filename="'.$order->order_number.'.pdf"',
            ],
        );
    }

    private function labelExists(Order $order): bool
    {
        return filled($order->tracking_number)
            && Storage::disk('local')->exists('sendcloud-labels/'.$order->id.'.pdf');
    }
}
