<?php

namespace App\Legacy\Http\Controller;

use App\Foundation\Framework\Http\Controller\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends BaseController
{
    public static $path = '';

    public function __invoke(Request $request)
    {
        $requestPath = $request->path();
        if ($requestPath === '/' || $requestPath === '') {
            $requestPath = '/index.php';
        }
        $filePath = public_path($requestPath);

        if (is_dir($filePath)) {
            $filePath = rtrim($filePath, '/').'/index.php';
        }

        if (! file_exists($filePath)) {
            throw new NotFoundHttpException;
        }

        $response = null;
        $content = [];
        $headers = headers_list();

        $obLevel = ob_get_level();
        ob_start();
        try {
            self::$path = $filePath;
            $this->requireLegacyFile();
        } catch (\App\Legacy\Exception\FlowControlException $e) {
            if ($e instanceof \App\Legacy\Exception\FileDownloadException) {
                $response = response()->download($e->getFilePath(), $e->getFileName());
            } elseif ($e instanceof \App\Legacy\Exception\FileStreamException) {
                $response = response()->file($e->getFilePath());
            } elseif ($e instanceof \App\Legacy\Exception\FlowRedirectionException) {
                $response = response()->redirectTo($e->getTargetUrl(), $e->getHttpCode());
            }
        } catch (\Throwable $e) {
            if (! (isset($GLOBALS['Ajax']) && $GLOBALS['Ajax'] instanceof \Ajax)) {
                throw $e;
            }

            if (function_exists('exception_handler')) {
                exception_handler($e);
            }
        }

        if (isset($GLOBALS['Ajax']) && $GLOBALS['Ajax'] instanceof \Ajax) {
            $GLOBALS['Ajax']->run();
        }

        if (function_exists('cancel_transaction')) {
            cancel_transaction();
        }

        while (ob_get_level() > $obLevel + 1) {
            ob_end_flush();
        }
        $content = ob_get_clean();

        if (! isset($response)) {
            $response = response($content);
        }

        $headers = array_diff(headers_list(), $headers);
        foreach ($headers as $header) {
            [$key, $value] = explode(':', $header, 2) + [null, null];
            if ($key && $value) {
                $response->headers->set(trim($key), trim($value));
                header_remove(trim($key));
            }
        }

        return $response;
    }

    /**
     * requires the legacy file in an isolated context
     */
    protected function requireLegacyFile(): void
    {
        // Make all the global variables available as
        // regular variables within the scope of the included file.
        foreach (require PATH_TO_ROOT.'/includes/globals.inc' as $key => $value) {
            // Auto-loaded files define some globals themselves,
            // If so, avoid overwriting them because they will not
            // be included again.
            if (isset($GLOBALS[$key])) {
                global ${$key};
            } else {
                ${$key} = $value;
                $GLOBALS[$key] = &${$key};
            }
        }
        unset($key, $value);

        require self::$path;
    }
}
