@props(['label', 'marked' => true])
@php
$text = $label->text();
$key = $marked ? $label->accessKey() : null;

// Which occurrence to mark is a guess. A label keeps its access key through translation but not
// the position it was written at, so the mark is placed on the first character that answers to the
// key, preferring the case it was given. Only the emphasis can land on the wrong one: the key is
// whatever the label says it is, so the character shown underlined is always one that works.
$at = $key === null ? false : mb_strpos($text, $key);

if ($at === false && $key !== null) {
    $at = mb_stripos($text, $key);
}
@endphp
@if ($at === false){{ $text }}@else{{ mb_substr($text, 0, $at) }}<u>{{ mb_substr($text, $at, 1) }}</u>{{ mb_substr($text, $at + 1) }}@endif
