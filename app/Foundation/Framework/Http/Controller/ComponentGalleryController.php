<?php

namespace App\Foundation\Framework\Http\Controller;

use App\Foundation\Navigation\Facade\Navigation;
use Illuminate\Contracts\View\View;

/**
 * The reference screen for the theme and the shared controls drawn on top of it.
 *
 * Served rather than kept as a file beside the build so that what it shows is the application: the
 * same layout, the same chrome, the same components, resolved by the same stylesheet. A copy of the
 * markup can agree with the app on the day it is written and disagree with it silently ever after,
 * which is the one thing a reference may not do.
 *
 * It asks to be dressed as a legacy page, which is what has the FrontAccounting stylesheet sent.
 * Its own markup is not legacy, but the surfaces it documents are: the message boxes, the tab strip
 * and the table styles are all keyed to FA's markup and drawn by a stylesheet only a page marked
 * this way receives. Without it a reference could not show half of what it is for.
 *
 * Nothing here is declared in the sitemap — it is a developer's page, not an area's — so the trail
 * is named outright, which is all a page off the tree can do.
 */
class ComponentGalleryController extends Controller
{
    public function __invoke(): View
    {
        $title = 'Component gallery';

        Navigation::crumb($title);

        return view('pages.demo.component-gallery', [
            'title' => $title,
            'is_legacy_page' => true,
        ]);
    }
}
