<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
    :admin-edit-url="auth()->user()?->is_admin ? \App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page], panel: 'admin') : null"
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
            color: var(--color-bmlk-brand);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .page-content strong {
            font-weight: 600;
            color: #27272a;
        }

        .page-content .table-wrap {
            margin-top: 1.25rem;
            overflow-x: auto;
            border: 1px solid #eadfe2;
            border-radius: 1rem;
        }

        .page-content table {
            width: 100%;
            min-width: 640px;
            border-collapse: collapse;
            font-size: .875rem;
            line-height: 1.55;
        }

        .page-content th {
            padding: .75rem 1rem;
            background: #F6ECEE;
            color: #18181b;
            font-weight: 600;
            text-align: left;
        }

        .page-content td {
            padding: .875rem 1rem;
            border-top: 1px solid #f0e6e8;
            vertical-align: top;
        }

        .page-content code {
            padding: .1rem .35rem;
            border-radius: .35rem;
            background: #F6ECEE;
            color: #712B47;
            font-size: .8125rem;
        }

        .page-content .cookie-settings-button {
            display: inline-flex;
            min-height: 3rem;
            align-items: center;
            justify-content: center;
            padding: 0 1.5rem;
            border: 0;
            border-radius: 9999px;
            background: #712B47;
            color: #fff;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .2s;
        }

        .page-content .cookie-settings-button:hover {
            background: var(--color-bmlk-brand);
        }
    </style>
</x-layout>
