{{-- Static/CMS page (About, Contact, FAQ, Terms, Privacy, Refund, Add-ons, Become an agent).
     A published `pages` row replaces the placeholder. Public site. Task 1.15 — Static/CMS pages. --}}
<x-layouts.public :title="$page?->seo_title ?: $title" :description="$page?->seo_description ?: $intro">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs"><a href="{{ route('home') }}">Home</a> / {{ $page?->title ?: $title }}</p>
            <h1>{{ $page?->title ?: $title }}</h1>
            <p class="muted prose">{{ $intro }}</p>
        </div>
    </section>

    {{-- ===== Body ===== --}}
    <section class="section">
        <div class="container prose">
            @if ($page?->body)
                {{-- Admin-authored Markdown, escaped HTML (CommonMark with html_input=escape). --}}
                {!! \Illuminate\Support\Str::markdown($page->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            @else
                <x-coming-soon :task="$task">This page's content is written in the admin CMS.</x-coming-soon>
            @endif

            @if ($slug === 'become-an-agent')
                <a href="{{ route('register.agency') }}" class="btn btn-primary">Register your agency</a>
            @endif
        </div>
    </section>
</x-layouts.public>
