<?php

namespace App\Legacy\Service;

/**
 * Legacy language service for FrontAccounting compatibility
 * Provides language object interface while using Laravel's translation system
 */
class LanguageService
{
    protected $locale;
    protected $dir;
    protected $encoding = 'UTF-8';

    public function __construct()
    {
        $this->locale = app()->getLocale();
        $this->dir = $this->isRtlLocale(app()->getLocale()) ? 'rtl' : 'ltr';
    }

    protected function isRtlLocale($locale)
    {
        return in_array($locale, config('app.rtl_locales', []));
    }

    public function setLocale($locale)
    {
        app()->setLocale($locale);
        $this->locale = $locale;
        $this->dir = $this->isRtlLocale($locale) ? 'rtl' : 'ltr';
        ini_set('default_charset', 'UTF-8');
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function isRtl()
    {
        return $this->dir === 'rtl';
    }

    public function getEncoding()
    {
        return $this->encoding;
    }
}

