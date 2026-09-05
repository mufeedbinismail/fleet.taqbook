<?php

namespace App\Foundation\Component\Control\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * The wiring every control drawn onto a form element shares.
 *
 * A control of that kind is two things at once: an element the form posts, and a configuration the
 * browser reads back off it. Both need a name the two sides agree on, and neither is what the
 * component is actually about — so they are settled here rather than decided again per component,
 * where the second answer only has to differ once to be wrong somewhere nobody is looking.
 */
class Control
{
    /**
     * A label's target, which the caller names or this derives.
     *
     * Derived from the posting name because that is the one thing on such an element guaranteed to
     * be there and to be distinct. Array-style names are the reason it cannot be used as it stands:
     * `items[3][due_on]` is a name a form posts happily and an id no document may carry.
     *
     * @param  prefix  what the derived id is grouped under, so two kinds of control on one screen
     *                 cannot derive the same id from the same name
     */
    public static function id(ComponentAttributeBag $attributes, string $prefix, ?string $name): string
    {
        if ($given = $attributes->get('id')) {
            return $given;
        }

        return $prefix.'-'.Str::slug(str_replace(['[', ']'], '-', $name ?? Str::random(8)));
    }

    /**
     * What a control alone has to say, as the attribute it is read back from.
     *
     * Silence is dropped rather than written: a null on the wire is a control being told to have no
     * opinion, which is not the same as its not having been asked, and only one of those leaves a
     * default standing. What is left is written as an object even where nothing survived, because
     * an empty list and an empty object are the same statement here and only one of them reads back
     * as one.
     *
     * @param  array<string, mixed>  $config
     */
    public static function config(array $config): string
    {
        return json_encode((object) array_filter($config, fn ($one) => $one !== null));
    }
}
