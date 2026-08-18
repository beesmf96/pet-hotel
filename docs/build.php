<?php

/**
 * Renders every docs/<name>.md into docs/<name>.html and regenerates index.html.
 *
 *   php docs/build.php
 *
 * Markdown is the source of truth — never hand-edit a generated .html file.
 * Pages are styled by docs/assets/doc.css, which is hand-maintained.
 *
 * Optional frontmatter (simple `key: value` lines, no YAML dependency):
 *
 *   ---
 *   title: Project Initiation Document
 *   description: Scope, objectives, risks, and acceptance criteria.
 *   badges: Governance, Planning
 *   order: 10
 *   ---
 *
 * Title falls back to the first `# heading`, description to the first
 * paragraph, and order to 100 (ties break alphabetically).
 */

require __DIR__.'/../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

const DOCS_DIR = __DIR__;

/**
 * Hand-written HTML docs that predate this script. They are listed on the
 * index but never regenerated — convert one to markdown to have it rendered.
 */
const STATIC_DOCS = [
    'user_guide.html' => [
        'title' => 'User Guide',
        'description' => 'Step-by-step guides for browsing hotels, making bookings, and managing the hotel owner portal.',
        'badges' => ['Pet Owners', 'Hotel Owners'],
        'order' => 20,
    ],
    'booking-flow.html' => [
        'title' => 'Booking Flow',
        'description' => 'End-to-end walkthrough of the customer booking journey from search to confirmation.',
        'badges' => ['Pet Owners'],
        'order' => 30,
    ],
    'pet-hotel-boarding-mvp-v1.html' => [
        'title' => 'MVP Requirements',
        'description' => 'The original functional and non-functional requirements the MVP was scoped against.',
        'badges' => ['Requirements'],
        'order' => 15,
    ],
];

/** Badge label to CSS modifier. Anything unlisted renders slate. */
const BADGE_COLOURS = [
    'Pet Owners' => 'blue',
    'Hotel Owners' => 'teal',
    'Admin' => 'amber',
];

/**
 * Splits leading `---` frontmatter off a markdown document.
 *
 * @return array{0: array<string, string>, 1: string} [metadata, body]
 */
function split_frontmatter(string $raw): array
{
    if (! preg_match('/^---\R(.*?)\R---\R?(.*)$/s', $raw, $matches)) {
        return [[], $raw];
    }

    $meta = [];

    foreach (preg_split('/\R/', $matches[1]) as $line) {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $meta[trim($key)] = trim($value);
        }
    }

    return [$meta, ltrim($matches[2])];
}

/** Pulls the first `# heading` out of a markdown body. */
function first_heading(string $body): ?string
{
    return preg_match('/^#\s+(.+)$/m', $body, $matches) ? trim($matches[1]) : null;
}

/** Pulls the first prose paragraph, skipping headings, tables, and quotes. */
function first_paragraph(string $body): ?string
{
    foreach (preg_split('/\R/', $body) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '|')
            || str_starts_with($line, '>') || str_starts_with($line, '-')) {
            continue;
        }

        $line = preg_replace('/[*`_\[\]]/', '', $line);

        return mb_strlen($line) > 160 ? mb_substr($line, 0, 157).'…' : $line;
    }

    return null;
}

/** Renders a badge list to HTML. */
function badges_html(array $badges): string
{
    $html = '';

    foreach ($badges as $badge) {
        $colour = BADGE_COLOURS[$badge] ?? 'slate';
        $html .= sprintf(
            "\n          <span class=\"badge badge-%s\">%s</span>",
            $colour,
            htmlspecialchars($badge, ENT_QUOTES)
        );
    }

    return $html;
}

/** Wraps tables so wide ones scroll instead of breaking the layout. */
function wrap_tables(string $html): string
{
    return preg_replace('/(<table>.*?<\/table>)/s', '<div class="table-wrap">$1</div>', $html);
}

function article_page(string $title, string $body): string
{
    $title = htmlspecialchars($title, ENT_QUOTES);

    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Pet Hotel — {$title}</title>
      <link rel="stylesheet" href="assets/doc.css" />
    </head>
    <body class="doc">
    <a class="backlink" href="index.html">&larr; All docs</a>
    {$body}
    </body>
    </html>

    HTML;
}

function index_page(array $entries): string
{
    $cards = '';

    foreach ($entries as $entry) {
        $cards .= sprintf(
            "\n      <a class=\"doc-card\" href=\"%s\">\n        <div class=\"doc-title\">\n          %s%s\n        </div>\n        <div class=\"doc-desc\">%s</div>\n      </a>\n",
            htmlspecialchars($entry['href'], ENT_QUOTES),
            htmlspecialchars($entry['title'], ENT_QUOTES),
            badges_html($entry['badges']),
            htmlspecialchars($entry['description'], ENT_QUOTES)
        );
    }

    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Pet Hotel — Documentation</title>
      <link rel="stylesheet" href="assets/doc.css" />
    </head>
    <body class="index">
      <div class="container">
        <div class="header">
          <div class="logo">🐾</div>
          <h1>Pet Hotel Docs</h1>
          <p class="subtitle">Booking platform for pet boarding</p>
        </div>

        <div class="docs">
    {$cards}
        </div>

        <div class="footer">Pet Hotel &middot; <a href="https://github.com/beesmf96/pet-hotel" style="color:inherit">beesmf96/pet-hotel</a></div>
      </div>
    </body>
    </html>

    HTML;
}

$environment = new Environment([
    'html_input' => 'allow',
    'heading_permalink' => [
        'html_class' => 'heading-permalink',
        'id_prefix' => '',
        'symbol' => '#',
        'aria_hidden' => true,
    ],
]);

$environment->addExtension(new CommonMarkCoreExtension);
$environment->addExtension(new TableExtension);
$environment->addExtension(new AutolinkExtension);
$environment->addExtension(new TaskListExtension);
$environment->addExtension(new HeadingPermalinkExtension);

$converter = new MarkdownConverter($environment);

$entries = [];

foreach (STATIC_DOCS as $href => $doc) {
    $entries[] = $doc + ['href' => $href];
}

foreach (glob(DOCS_DIR.'/*.md') as $path) {
    $slug = basename($path, '.md');
    [$meta, $body] = split_frontmatter(file_get_contents($path));

    $title = $meta['title'] ?? first_heading($body) ?? ucfirst(str_replace('-', ' ', $slug));

    file_put_contents(
        DOCS_DIR."/{$slug}.html",
        article_page($title, wrap_tables((string) $converter->convert($body)))
    );

    $entries[] = [
        'href' => "{$slug}.html",
        'title' => $title,
        'description' => $meta['description'] ?? first_paragraph($body) ?? '',
        'badges' => isset($meta['badges'])
            ? array_map('trim', explode(',', $meta['badges']))
            : [],
        'order' => (int) ($meta['order'] ?? 100),
    ];

    echo "rendered {$slug}.html\n";
}

usort($entries, fn (array $a, array $b) => [$a['order'], $a['title']] <=> [$b['order'], $b['title']]);

file_put_contents(DOCS_DIR.'/index.html', index_page($entries));

echo 'rendered index.html ('.count($entries)." docs)\n";
