<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BLACK MILK · {{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:0;background:#fcf8f4;color:#3a2e30;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#fcf8f4">
        <tr>
            <td align="center" style="padding:32px 16px 48px">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;border-collapse:collapse;background:#ffffff">
                    <tr><td style="padding:36px 32px 0">
                        <p style="margin:0;color:#a9636f;font-size:14px;font-weight:700;letter-spacing:3px">BLACK MILK</p>
                    </td></tr>
                    <tr><td style="padding:32px 32px 0">
                        <h1 style="margin:0;color:#3a2e30;font-family:Georgia,'Times New Roman',serif;font-size:30px;font-weight:400;line-height:1.25">
                            @if ($kind === 'paid')
                                {{ __('Paiement confirmé') }}
                            @elseif ($kind === 'reminder')
                                {{ __('Votre commande vous attend') }}
                            @else
                                {{ __('Paiement non finalisé') }}
                            @endif
                        </h1>
                        <p style="margin:20px 0 0;color:#52525b;font-size:14px;line-height:1.8">
                            {{ __('Bonjour :name,', ['name' => $order->first_name ?: '']) }}
                        </p>
                        <p style="margin:12px 0 0;color:#52525b;font-size:14px;line-height:1.8">
                            @if ($kind === 'paid')
                                {{ __('Nous avons bien reçu votre paiement. Votre commande est confirmée et nous allons la préparer.') }}
                            @elseif ($kind === 'reminder')
                                {{ __('Votre commande est toujours enregistrée, mais son paiement n’a pas été confirmé. Vous pouvez reprendre le paiement sans refaire votre panier.') }}
                            @else
                                {{ __('Le paiement de votre commande n’a pas abouti. Vos articles sont conservés : vous pouvez réessayer sans refaire votre panier.') }}
                            @endif
                        </p>
                    </td></tr>

                    <tr><td style="padding:28px 32px 0">
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#fcf8f4">
                            <tr>
                                <td style="padding:16px 18px;color:#71717a;font-size:12px">{{ __('Commande') }}</td>
                                <td align="right" style="padding:16px 18px;color:#3a2e30;font-size:13px;font-weight:700">{{ $order->order_number }}</td>
                            </tr>
                        </table>
                    </td></tr>

                    <tr><td style="padding:30px 32px 0">
                        <h2 style="margin:0 0 12px;color:#3a2e30;font-family:Georgia,'Times New Roman',serif;font-size:21px;font-weight:400">
                            {{ __('Articles commandés') }}
                        </h2>

                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                            @foreach ($order->items ?? [] as $item)
                                @php
                                    $image = data_get($item, 'image');
                                    $imageUrl = is_string($image) && preg_match('~^https?://~i', $image)
                                        ? $image
                                        : (is_string($image) && str_starts_with($image, '/')
                                            ? url($image)
                                            : null);
                                    $quantity = (int) data_get($item, 'quantity', 1);
                                    $unitPrice = (float) data_get($item, 'unit_price', 0);
                                    $lineTotal = (float) data_get($item, 'line_total', $unitPrice * $quantity);
                                @endphp
                                <tr>
                                    <td valign="top" style="padding:15px 0;border-bottom:1px solid #f1e9e8">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                                            <tr>
                                                @if ($imageUrl)
                                                    <td valign="top" width="62" style="padding:0 12px 0 0;width:62px">
                                                        <img src="{{ $imageUrl }}" alt="" width="62" height="62"
                                                            style="display:block;width:62px;height:62px;object-fit:cover;border-radius:8px;border:0">
                                                    </td>
                                                @endif
                                                <td valign="top" style="padding:0;color:#3a2e30;font-size:13px;line-height:1.6">
                                                    <strong style="font-weight:600">{{ data_get($item, 'name', __('Produit')) }}</strong>
                                                    @foreach (data_get($item, 'options', []) as $option)
                                                        <span style="display:block;color:#858087;font-size:11px;line-height:1.7">
                                                            {{ data_get($option, 'label') }}{{ data_get($option, 'label') && data_get($option, 'display') ? ' · ' : '' }}{{ data_get($option, 'display') }}
                                                        </span>
                                                    @endforeach
                                                    <span style="display:block;margin-top:4px;color:#71717a;font-size:12px">
                                                        {{ $quantity }} × {{ number_format($unitPrice, 2, ',', ' ') }} €
                                                    </span>
                                                </td>
                                                <td valign="top" align="right" width="92" style="padding:0 0 0 10px;width:92px;white-space:nowrap;color:#3a2e30;font-size:13px;font-weight:700">
                                                    {{ number_format($lineTotal, 2, ',', ' ') }} €
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td></tr>

                    <tr><td style="padding:20px 32px 0">
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                            <tr>
                                <td style="padding:7px 0;color:#71717a;font-size:13px">{{ __('Sous-total') }}</td>
                                <td align="right" style="padding:7px 0;color:#3a2e30;font-size:13px">{{ number_format((float) $order->subtotal, 2, ',', ' ') }} €</td>
                            </tr>
                            @if ((float) $order->discount_amount > 0)
                                <tr>
                                    <td style="padding:7px 0;color:#a9636f;font-size:13px">{{ __('Réduction') }}{{ $order->promo_code ? ' · '.$order->promo_code : '' }}</td>
                                    <td align="right" style="padding:7px 0;color:#a9636f;font-size:13px">− {{ number_format((float) $order->discount_amount, 2, ',', ' ') }} €</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:7px 0;color:#71717a;font-size:13px">{{ __('Livraison') }}</td>
                                <td align="right" style="padding:7px 0;color:#3a2e30;font-size:13px">
                                    {{ (float) $order->delivery_cost === 0.0 ? __('Offerte') : number_format((float) $order->delivery_cost, 2, ',', ' ').' €' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 0 0;border-top:1px solid #e9dedf;color:#3a2e30;font-size:16px;font-weight:700">{{ __('Total') }}</td>
                                <td align="right" style="padding:16px 0 0;border-top:1px solid #e9dedf;color:#3a2e30;font-size:17px;font-weight:700">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</td>
                            </tr>
                        </table>
                    </td></tr>

                    <tr><td style="padding:30px 32px 0">
                        <a href="{{ route('account.checkout', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}"
                            style="display:inline-block;background:#a9636f;color:#ffffff;padding:15px 23px;border-radius:24px;text-decoration:none;font-size:13px;font-weight:700">
                            {{ __('Voir ma commande') }} →
                        </a>
                        @if ($kind !== 'paid')
                            <p style="margin:18px 0 0">
                                <a href="{{ route('order.status', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}"
                                    style="color:#a9636f;font-size:13px;font-weight:700;text-decoration:underline">
                                    {{ __('Reprendre le paiement') }} →
                                </a>
                            </p>
                        @endif
                        <p style="margin:17px 0 0;color:#858087;font-size:11px;line-height:1.8">
                            {{ __('Ce lien privé vous permet de consulter cette commande sans créer de compte. Conservez cet e-mail.') }}
                        </p>
                    </td></tr>
                    <tr><td style="padding:35px 32px 36px;color:#a1a1aa;font-size:12px;line-height:1.6">
                        {{ __('BLACK MILK · Merci pour votre confiance.') }}
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
