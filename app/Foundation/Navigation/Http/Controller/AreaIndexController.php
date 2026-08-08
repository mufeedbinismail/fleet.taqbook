<?php

namespace App\Foundation\Navigation\Http\Controller;

use App\Foundation\Http\Controller\Controller;
use App\Foundation\Navigation\Facade\Navigation;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * One area's index: everywhere the area leads, laid out across the page.
 *
 * Nothing is drawn that the resolved tree did not hand over, and nothing on it is tested a second
 * time — so whatever this page shows, it shows exactly what that tree said there was.
 */
class AreaIndexController extends Controller
{
    public function __invoke(string $key): View
    {
        $area = Navigation::tree()->find($key);

        if ($area === null || ! $area->isArea()) {
            throw new NotFoundHttpException("No area [{$key}].");
        }

        // No declaration carries this address, so the page has to name where it is. Nothing could
        // work it out from the URL, which says only which area is being drawn.
        Navigation::here($key);

        return view('navigation.area-index', [
            'area' => $area,
            'title' => $area->label()->text(),
        ]);
    }
}
