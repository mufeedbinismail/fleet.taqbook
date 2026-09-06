<?php

namespace Tests\Concern;

/**
 * Reads back what a page staged for its browser.
 *
 * The payload reaches the page as the body of a JS string literal wrapped in JSON.parse(), so
 * getting at it is that literal decoded and then the JSON inside it.
 */
trait ReadsClientData
{
    /**
     * @return array<string, mixed>
     */
    protected function clientData(string $html): array
    {
        $this->assertSame(1, preg_match("/window\.App\.data = JSON\.parse\('(.*)'\);/U", $html, $match));

        return json_decode(json_decode('"'.$match[1].'"'), associative: true);
    }
}
