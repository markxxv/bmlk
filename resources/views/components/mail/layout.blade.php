@props(['title' => 'BLACK MILK'])
@php($colors = config('email.colors'))
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $colors['canvas'] }};color:{{ $colors['ink'] }};font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:{{ $colors['canvas'] }}">
        <tr>
            <td align="center" style="padding:32px 16px 48px">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;border-collapse:collapse;background:{{ $colors['surface'] }}">
                    <tr><td style="padding:36px 32px 0">
                        <p style="margin:0;color:{{ $colors['accent'] }};font-size:14px;font-weight:700;letter-spacing:3px">BLACK MILK</p>
                    </td></tr>
                    {{ $slot }}
                    <tr><td style="padding:35px 32px 36px;color:{{ $colors['footer'] }};font-size:12px;line-height:1.6">
                        {{ __('BLACK MILK · Merci pour votre confiance.') }}
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
