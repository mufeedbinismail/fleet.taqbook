<?php

namespace App\Legacy\Session;

use IteratorAggregate;
use ArrayAccess;
use Countable;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Arr;

class Store implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * The Laravel session manager instance.
     */
    protected ?SessionManager $manager;

    /**
     * The FrontAccounting session data.
     */
    protected array $items = [];

    /**
     * Create a new SessionArrayObject instance.
     */
    public function __construct(SessionManager $manager = null)
    {
        $this->manager = $manager;
    }

    public function get($key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    public function put($key, $value = null): void
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException("Session key cannot be null");
        }

        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    public function has($key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function forget($keys): void
    {
        Arr::forget($this->items, $keys);
    }

    /**
     * Get a value from the session.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set a value in the session.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->put($offset, $value);
    }

    /**
     * Check if a key exists in the session.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Remove a value from the session.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->forget($offset);
    }

    /**
     * Return an iterator for session data.
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Get count of session items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Unserialize session data.
     */
    public function load(array $data): void {
        $this->items = $data;
    }

    /**
     * Serialize session data.
     */
    public function all(): array {
        return $this->items;
    }

    /**
     * Push a value onto a session array's beginning.
     */
    public function prepend($key, $value): void
    {
        $array = $this->get($key, []);
        array_unshift($array, $value);
        $this->put($key, $array);
    }

    /**
     * Pop a value off a session array's beginning.
     */
    public function pop($key): mixed
    {
        $array = $this->get($key, []);
        $value = array_shift($array);
        $this->put($key, $array);

        return $value;
    }

    /**
     * Clear the FA session and invalidate the underlying Laravel session.
     */
    public function invalidate(): void
    {
        $this->items = [];
        $this->manager()->invalidate();
    }

    /**
     * Get the session manager instance.
     */
    protected function manager(): SessionManager
    {
        return $this->manager ??= app(SessionManager::class);
    }

    /**
     * Dynamically call methods on the manager.
     */
    public function __call($name, $arguments)
    {
        return $this->manager()->{$name}(...$arguments);
    }

    /**
     * Dynamically get values on the session.
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically set values on the session.
     */
    public function __set($key, $value)
    {
        $this->put($key, $value);
    }
}
