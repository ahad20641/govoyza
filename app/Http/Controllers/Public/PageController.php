<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

/**
 * Static and CMS pages (About, Contact, FAQ, Terms, Privacy, Refund, Add-ons, Become an agent).
 * A published `pages` row with the same slug replaces the placeholder. Public site, govoyza.com.
 * Task 1.15 — Static/CMS pages.
 */
class PageController extends Controller
{
    /** slug => [title, intro, task that fills it]. Each slug becomes a route named pages.{slug}. */
    public const PAGES = [
        'add-ons' => ['Add-ons', 'Translation, attestation, reservation letters from real providers, travel insurance and appointment booking.', '1.15'],
        'about' => ['About GoVoyza', 'Visa applications handled online, start to finish.', '1.15'],
        'contact' => ['Contact us', 'Questions about an application or a visa? We reply within one working day.', '1.15'],
        'faq' => ['Frequently asked questions', 'Fees, timelines, documents and what happens after you apply.', '1.15'],
        'terms' => ['Terms of service', 'We do not guarantee visa approval. Our service fee covers processing, not the decision.', '1.15'],
        'privacy' => ['Privacy policy', 'How we collect, store and protect your personal data and documents.', '1.15'],
        'refund-policy' => ['Refund policy', 'When and how service fees are refunded. Embassy fees are passed through at cost.', '1.15'],
        'become-an-agent' => ['Become a B2B agent', 'Travel agencies get net prices, a wallet and white-label documents for their clients.', '1.15'],
    ];

    public function show(string $slug): View
    {
        [$title, $intro, $task] = self::PAGES[$slug];

        return view('public.page', [
            'slug' => $slug,
            'title' => $title,
            'intro' => $intro,
            'task' => $task,
            'page' => Page::query()->published()->where('slug', $slug)->first(),
        ]);
    }
}
