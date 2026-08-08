<?php

namespace App\Foundation\Framework\Exception;

use RuntimeException;

/**
 * Something offered data to the client after the page had already handed its data over.
 *
 * Fatal rather than quietly dropped: the alternative is a value that is present on the server and
 * absent in the browser, which surfaces far away from the code that caused it — as a lookup failing
 * at the moment a user clicks something. Registering sooner is the fix, and everything can, since
 * nothing renders after the point the data goes out.
 */
class ClientDataAlreadyRenderedException extends RuntimeException
{
    public static function putting(string $namespace): self
    {
        return new self(
            "Client data was already rendered when '{$namespace}' was added to it. Register it "
            .'before the page renders its data.',
        );
    }
}
