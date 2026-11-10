<x-mail.layout title="BLACK MILK · Votre avis">
    <tr><td style="padding:32px 32px 0">
        <h1 style="margin:0;color:{{ config('email.colors.ink') }};font-family:Georgia,'Times New Roman',serif;font-size:30px;font-weight:400;line-height:1.25">
            Votre avis nous intéresse
        </h1>
        <p style="margin:20px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            Bonjour {{ $order->first_name }},
        </p>
        <p style="margin:12px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            Vous avez reçu votre commande BLACK MILK il y a quelques jours.
            Votre expérience avec ces produits nous aiderait beaucoup :
            vous pouvez les noter en quelques instants, sans créer de compte ni saisir de mot de passe.
        </p>
    </td></tr>

    <tr><td style="padding:26px 32px 0">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:{{ config('email.colors.canvas') }}">
            <tr>
                <td style="padding:16px 18px;color:{{ config('email.colors.muted') }};font-size:12px">Commande</td>
                <td align="right" style="padding:16px 18px;color:{{ config('email.colors.ink') }};font-size:13px;font-weight:700">{{ $order->order_number }}</td>
            </tr>
        </table>
    </td></tr>

    <tr><td style="padding:28px 32px 0">
        <x-mail.button :href="$reviewUrl">Évaluer mes produits</x-mail.button>
        <p style="margin:18px 0 0;color:{{ config('email.colors.subtle') }};font-size:12px;line-height:1.8">
            Ce lien est personnel, valable pendant 30 jours, et donne uniquement accès aux évaluations de cette commande.
        </p>
    </td></tr>

    <tr><td style="padding:20px 32px 0">
        <p style="margin:0;color:{{ config('email.colors.subtle') }};font-size:12px;line-height:1.8">
            Vous ne souhaitez plus recevoir ce type de message ?
            <a href="{{ $unsubscribeUrl }}" style="color:{{ config('email.colors.accent') }};text-decoration:underline">Ne plus recevoir de demandes d’avis</a>.
        </p>
    </td></tr>
</x-mail.layout>
