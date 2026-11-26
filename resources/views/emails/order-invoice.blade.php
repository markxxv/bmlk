<x-mail.layout :title="'BLACK MILK · '.$order->order_number">
    <tr><td style="padding:32px 32px 0">
        <h1 style="margin:0;color:{{ config('email.colors.ink') }};font-family:Georgia,'Times New Roman',serif;font-size:30px;font-weight:400;line-height:1.25">
            {{ __('Votre facture') }}
        </h1>
        <p style="margin:20px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            {{ __('Bonjour :name,', ['name' => $order->first_name ?: '']) }}
        </p>
        <p style="margin:12px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            {{ __('Votre commande BLACK MILK a été expédiée. Vous trouverez votre facture au format PDF en pièce jointe à cet e-mail.') }}
        </p>
    </td></tr>
    <tr><td style="padding:26px 32px 0">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:{{ config('email.colors.canvas') }}">
            <tr>
                <td style="padding:16px 18px;color:{{ config('email.colors.muted') }};font-size:12px">{{ __('Commande') }}</td>
                <td align="right" style="padding:16px 18px;color:{{ config('email.colors.ink') }};font-size:13px;font-weight:700">{{ $order->order_number }}</td>
            </tr>
        </table>
    </td></tr>
    <tr><td style="padding:24px 32px 32px">
        <p style="margin:0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            {{ __('Vous pouvez également retrouver votre facture dans votre espace personnel BLACK MILK.') }}
        </p>
        <div style="margin-top:20px">
            <x-mail.button :href="route('account.index')">{{ __('Mes commandes') }}</x-mail.button>
        </div>
    </td></tr>
</x-mail.layout>
