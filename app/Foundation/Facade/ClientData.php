<?php

namespace App\Foundation\Facade;

use App\Foundation\Exception\ClientDataAlreadyRenderedException;
use App\Foundation\Registry\ClientDataRegistry;

/**
 * A global because of where it is asked from: a view has no constructor to receive the registry
 * through, and neither does a legacy script. Anything the container builds should take the registry
 * as a dependency instead of reaching here.
 *
 * Deliberately not an Illuminate facade. A declared static never reaches __callStatic, so the base
 * class could deliver none of what it is normally chosen for; rebind the container to substitute
 * one of these.
 */
final class ClientData
{
    /**
     * @throws ClientDataAlreadyRenderedException if the page has already handed its data over
     */
    public static function routes(string|array $names): void
    {
        self::registry()->routes($names);
    }

    /**
     * @throws ClientDataAlreadyRenderedException if the page has already handed its data over
     */
    public static function translations(string|array $keys): void
    {
        self::registry()->translations($keys);
    }

    /**
     * @param  array<string, mixed>  $values
     *
     * @throws ClientDataAlreadyRenderedException if the page has already handed its data over
     */
    public static function put(string $namespace, array $values): void
    {
        self::registry()->put($namespace, $values);
    }

    /**
     * Closes registration: nothing may be added once this has answered.
     */
    public static function render(): string
    {
        return self::registry()->render();
    }

    /**
     * The registry this request is collecting into. Resolved per call rather than held, so one
     * global can span a boot-time seed and a request-scoped instance without a reference to either
     * outliving its own scope.
     */
    public static function registry(): ClientDataRegistry
    {
        return app(ClientDataRegistry::class);
    }
}
