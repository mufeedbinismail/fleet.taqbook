<?php

namespace App\Foundation\Shared\Setting;

use Illuminate\Config\Repository as BaseRepository;
use App\Foundation\Framework\Support\Arr;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Store extends BaseRepository implements Countable, IteratorAggregate
{
    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = Arr::NOT_SET)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return $this->value($key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, Arr::NOT_SET];
            }

            $config[$key] = $this->value($key, $default);
        }

        return $config;
    }

    /**
     * Get the value of a setting.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function value($key, $default = Arr::NOT_SET)
    {
        return Arr::kvGet($this->items, $key, $default);
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}