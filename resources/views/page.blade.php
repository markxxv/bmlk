<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
>
    <main class="px-3 py-12 sm:px-5 sm:py-16 lg:px-8 lg:py-20">
        <article class="mx-auto max-w-4xl rounded-3xl bg-white px-6 py-10 sm:px-10 sm:py-14 lg:px-14">
            <h1 class="font-serif text-4xl font-medium tracking-tight text-zinc-900 sm:text-5xl">
                {{ $title }}
            </h1>

            @if (filled($body))
                <div class="page-content mt-8 text-[15px] leading-7 text-zinc-600">
                    {!! $body !!}
                </div>
            @endif
        </article>
    </main>

    <style>
        .page-content h2 {
            margin-top: 2rem;
            margin-bottom: .75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #18181b;
        }

        .page-content h3 {
            margin-top: 1.5rem;
            margin-bottom: .5rem;
            font-size: 1rem;
            font-weight: 600;
            color: #18181b;
        }

        .page-content p,
        .page-content ul,
        .page-content ol {
            margin-top: 1rem;
        }

        .page-content ul {
            list-style: disc;
            padding-left: 1.25rem;
        }

        .page-content ol {
            list-style: decimal;
            padding-left: 1.25rem;
        }

        .page-content a {
            color: #A9636F;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .page-content strong {
            font-weight: 600;
            color: #27272a;
        }
    </style>
</x-layout>
