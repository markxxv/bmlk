@foreach ($urls as $locale => $url)
    <url>
        <loc>{{ $url }}</loc>
        @foreach ($urls as $altLocale => $altUrl)
            <xhtml:link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}" />
        @endforeach
        @if (isset($urls[$default]))
            <xhtml:link rel="alternate" hreflang="x-default" href="{{ $urls[$default] }}" />
        @endif
        @if (! empty($lastmod))
            <lastmod>{{ $lastmod }}</lastmod>
        @endif
        @if (! empty($image))
            <image:image>
                <image:loc>{{ $image }}</image:loc>
                <image:title>{{ $imageTitle ?? '' }}</image:title>
            </image:image>
        @endif
    </url>
@endforeach
