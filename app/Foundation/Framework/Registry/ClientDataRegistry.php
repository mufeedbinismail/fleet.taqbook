<?php

namespace App\Foundation\Framework\Registry;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Framework\Exception\ClientDataAlreadyRenderedException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Js;
use LogicException;

/**
 * Collects everything this request wants to hand the browser, under one namespace per kind.
 *
 * Values are resolved as they are registered rather than at render: a namespace holds plain data by
 * the time anything reads it, so nothing has to know which kind it came from to serialise it, and a
 * new kind needs no more than a name.
 *
 * Registration closes at the first read for the same reason a sitemap's does — anything arriving
 * afterwards would be missing from what the page already sent, and the browser would only report it
 * as a failed lookup much later.
 */
class ClientDataRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $data = [];

    private bool $rendered = false;

    public function routes(string|array $names): static
    {
        return $this->put('routes', collect(Arr::wrap($names))
            ->mapWithKeys(fn ($name) => [$name => Route::uri($name)])
            ->all());
    }

    /**
     * Keys are resolved as-is: each string is owned by whichever lang file already owns it, same as
     * any other translation, so there is nothing here for this class to name.
     */
    public function translations(string|array $keys): static
    {
        return $this->put('i18n', collect(Arr::wrap($keys))
            ->mapWithKeys(fn ($key) => [$key => __($key)])
            ->all());
    }

    /**
     * Answered for whoever is signed in now, so the browser holds a yes or a no for each name and never the rule behind it.
     *
     * Names are those of `Permission` constants, so the permission strings themselves stay on the server.
     *
     * @param  string|list<string>  $names
     *
     * @throws LogicException if a name is not a `Permission` constant
     */
    public function permissions(string|array $names): static
    {
        $granted = collect(Arr::wrap($names))
            ->mapWithKeys(function ($name) {
                $constant = Permission::class.'::'.$name;

                if (! defined($constant)) {
                    throw new LogicException("`{$name}` is not a Permission constant.");
                }

                return [$name => Gate::allows(constant($constant))];
            })
            ->all();

        return $this->put('user', ['permissions' => array_merge($this->data['user']['permissions'] ?? [], $granted)]);
    }

    /**
     * Resolved by the company settings, so the browser holds the value each call answered and never the call behind it.
     *
     * An entry is a method name, or a name followed by the arguments to call it with, in the order it takes them.
     * The browser asks by name alone.
     *
     * @param  list<string|array{0: string}>  $entries
     *
     * @throws LogicException if a name is not callable on the settings
     */
    public function settings(array $entries): static
    {
        $settings = settings();
        $answers = [];

        foreach ($entries as $entry) {
            $arguments = Arr::wrap($entry);
            $name = array_shift($arguments);

            if (! is_callable([$settings, $name])) {
                throw new LogicException("`{$name}` is not callable on the settings.");
            }

            $answers[$name] = call_user_func([$settings, $name], ...$arguments);
        }

        return $this->put('settings', $answers);
    }

    /**
     * @param  array<string, mixed>  $values
     *
     * @throws ClientDataAlreadyRenderedException if the page has already handed its data over
     */
    public function put(string $namespace, array $values): static
    {
        if ($this->rendered) {
            throw ClientDataAlreadyRenderedException::putting($namespace);
        }

        $this->data[$namespace] = array_merge($this->data[$namespace] ?? [], $values);

        return $this;
    }

    /**
     * Everything collected, as the JavaScript expression a page prints, and the close of
     * registration.
     */
    public function render(): string
    {
        $this->rendered = true;

        // An empty registry still has to read as an object — an array would hand the page a shape
        // nothing can look a namespace up in.
        return Js::from($this->data ?: (object) [])->toHtml();
    }
}
