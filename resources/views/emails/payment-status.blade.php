<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BLACK MILK</title>
</head>
<body style="margin:0;padding:0;background:#faf8f7;color:#27272a;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#faf8f7">
        <tr><td align="center" style="padding:40px 16px">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;background:#ffffff">
                <tr>
                    <td style="padding:38px 36px 22px">
                        <p style="margin:0;color:#a9636f;font-size:13px;letter-spacing:2px;font-weight:bold">BLACK MILK</p>
                        <h1 style="margin:32px 0 12px;font-family:Georgia,serif;font-size:30px;line-height:1.2;font-weight:normal;color:#18181b">
                            @if ($kind === 'paid')
                                {{ __('Paiement confirmé') }}
                            @elseif ($kind === 'reminder')
                                {{ __('Votre commande vous attend') }}
                            @else
                                {{ __('Paiement non finalisé') }}
                            @endif
                        </h1>
                        <p style="margin:0;color:#71717a;font-size:14px;line-height:1.8">
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
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 36px">
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#faf6f6">
                            <tr>
                                <td style="padding:18px 20px;color:#71717a;font-size:13px">{{ __('Commande') }}</td>
                                <td align="right" style="padding:18px 20px;font-size:13px;font-weight:bold">{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td style="padding:0 20px 18px;color:#71717a;font-size:13px">{{ __('Total') }}</td>
                                <td align="right" style="padding:0 20px 18px;font-size:15px;font-weight:bold">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @if ($kind !== 'paid')
                    <tr>
                        <td style="padding:28px 36px 0">
                            <a href="{{ route('order.status', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}"
                               style="display:inline-block;background:#a9636f;color:#ffffff;text-decoration:none;padding:15px 25px;font-size:14px;font-weight:bold">
                                {{ __('Reprendre le paiement') }}
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:32px 36px 38px;color:#a1a1aa;font-size:12px;line-height:1.7">
                        {{ __('BLACK MILK · Merci pour votre confiance.') }}
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
