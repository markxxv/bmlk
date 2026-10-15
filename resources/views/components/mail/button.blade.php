@props(['href'])
<a href="{{ $href }}"
   style="display:inline-block;background:{{ config('email.colors.accent') }};color:#ffffff;padding:15px 23px;border-radius:24px;text-decoration:none;font-size:13px;font-weight:700">
    {{ $slot }}
</a>
