<x-mail.layout :title="'BLACK MILK · '.$title">
    <tr><td style="padding:32px 32px 0">
        <h1 style="margin:0;color:{{ config('email.colors.ink') }};font-family:Georgia,'Times New Roman',serif;font-size:30px;font-weight:400;line-height:1.25">
            {{ $title }}
        </h1>
        <p style="margin:20px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            {{ __('Bonjour :name,', ['name' => $name ?: '']) }}
        </p>
        <p style="margin:12px 0 0;color:{{ config('email.colors.copy') }};font-size:14px;line-height:1.8">
            {{ $bodyText }}
        </p>
    </td></tr>

    <tr><td style="padding:30px 32px 0">
        <x-mail.button :href="$actionUrl">{{ $actionLabel }} →</x-mail.button>
        <p style="margin:20px 0 0;color:{{ config('email.colors.muted') }};font-size:12px;line-height:1.8">
            {{ $expiry }}
        </p>
        <p style="margin:11px 0 0;color:{{ config('email.colors.subtle') }};font-size:12px;line-height:1.8">
            {{ $disclaimer }}
        </p>
    </td></tr>
</x-mail.layout>
