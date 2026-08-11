<?php

namespace App\Foundation\Framework\Http\Controller;

use App\Foundation\Navigation\Facade\Navigation;
use Illuminate\Contracts\View\View;

/**
 * The reference screen for the theme and the shared controls drawn on top of it.
 *
 * Served rather than kept as a static file so that what it shows is the application itself, which a
 * copy stops being the day after it is written.
 */
class ComponentGalleryController extends Controller
{
    public function __invoke(): View
    {
        $title = 'Component gallery';

        Navigation::crumb($title);

        return view('pages.demo.component-gallery', [
            'title' => $title,
        ]);
    }
}
