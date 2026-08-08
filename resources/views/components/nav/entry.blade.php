@props(['node', 'accelerated' => true])
@use('App\Foundation\Navigation\Enum\Category')
@php
// A place draws the icon it declared, and otherwise borrows the one its kind of place wears here.
// What a kind looks like is a decision this theme makes and can remake, which is why the mapping
// sits in the markup rather than on the thing being matched.
$icon = $node->icon() ?? match ($node->category()) {
    Category::Entry => 'icon-data',
    Category::Inquiry => 'icon-view',
    Category::Maintenance, Category::Settings => 'icon-setup-master',
    Category::Report => 'icon-reports',
    Category::System => 'icon-tools',
    Category::Transaction => 'icon-feature',
    Category::Update => 'icon-globe',
    default => 'icon-feature',
};

// An anchor without one is still the right element for somewhere that exists but cannot currently
// be addressed — it reads as a place rather than as a link to nowhere.
$url = $node->url();

// Access keys are only unique among the entries of one area, so only one area's worth of them is
// ever put on a page. Where the key is withheld the mark goes too, leaving no underline promising
// a key that another entry would answer to first.
$accessKey = $accelerated ? $node->label()->accessKey() : null;
@endphp
{{--
    A row of the menu like any other, said here rather than by whoever draws it, so an entry cannot
    be placed in the tree and come out looking like something else. What is left to `nav-entry` is
    only what a leaf has and the rows above it do not.
--}}
<a
    @if ($url !== null) href="{{ $url }}" @endif
    @if ($accessKey !== null) accesskey="{{ $accessKey }}" @endif
    {{ $attributes->class(['nav-entry', 'nav-row']) }}
>
    <span class="icon nav-entry-icon {{ $icon }}"></span>
    <span class="nav-entry-label"><x-nav.label :label="$node->label()" :marked="$accelerated" /></span>
</a>
