<?php

namespace App\Session;

use IteratorAggregate;
use ArrayAccess;
use Serializable;
use Countable;
use Illuminate\Session\SessionManager;

class Store implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /**
     * The Laravel session manager instance.
     */
    protected SessionManager $manager;

    /**
     * Create a new SessionArrayObject instance.
     */
    public function __construct(SessionManager $manager = null)
    {
        $this->manager = $manager;
    }

    /**
     * Get a value from the session.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->manager()->driver()->get($offset);
    }

    /**
     * Set a value in the session.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->manager()->driver()->put($offset, $value);
    }

    /**
     * Check if a key exists in the session.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->manager()->driver()->has($offset);
    }

    /**
     * Remove a value from the session.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->manager()->driver()->forget($offset);
    }

    /**
     * Return an iterator for session data.
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->manager()->driver()->all());
    }

    /**
     * Get count of session items.
     */
    public function count(): int
    {
        return count($this->manager()->driver()->all());
    }

    /**
     * Unserialize session data.
     */
    public function unserialize(string $data): void {
        $this->manager()->driver()->setData(unserialize($data));
    }

    /**
     * Serialize session data.
     */
    public function serialize(): string {
        return serialize($this->manager()->driver()->all());
    }

    /**
     * Push a value onto a session array's beginning.
     */
    public function unshift($key, $value): void
    {
        $array = $this->manager()->driver()->get($key, []);
        array_unshift($array, $value);
        $this->manager()->driver()->put($key, $array);
    }

    /**
     * Pop a value off a session array's beginning.
     */
    public function shift($key): mixed
    {
        $array = $this->manager()->driver()->get($key, []);
        $value = array_shift($array);
        $this->manager()->driver()->put($key, $array);
        return $value;
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
        return $this->manager()->driver()->get($key);
    }

    /**
     * Dynamically set values on the session.
     */
    public function __set($key, $value)
    {
        $this->manager()->driver()->put($key, $value);
    }
}