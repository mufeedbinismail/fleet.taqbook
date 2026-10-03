<?php

namespace App\Trust\Entity;

use App\Trust\Contract\SignatureAlgorithmContract;
use App\Trust\Enum\SignatureAlgorithm;
use App\Trust\Support\Encoding;
use InvalidArgumentException;

abstract class Key
{
    private ?SignatureAlgorithmContract $implementation = null;

    /** Final, so reading one from a string builds the key that was asked for and nothing else. */
    final public function __construct(
        private readonly SignatureAlgorithm $algorithm,
        private readonly string $bytes,
    ) {
        $length = static::length($this->implementation());

        if (strlen($this->bytes) !== $length) {
            throw new InvalidArgumentException(sprintf(
                '%s [%s] must be %d bytes, %d given.',
                class_basename(static::class),
                $algorithm->value,
                $length,
                strlen($this->bytes),
            ));
        }
    }

    public function algorithm(): SignatureAlgorithm
    {
        return $this->algorithm;
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function toEncodedString(): string
    {
        return $this->algorithm->value.':'.Encoding::encode($this->bytes);
    }

    public static function fromEncodedString(string $encoded): static
    {
        [$name, $bytes] = array_pad(explode(':', $encoded, 2), 2, null);
        $algorithm = $bytes === null ? null : SignatureAlgorithm::tryFrom($name);

        if ($algorithm === null) {
            throw new InvalidArgumentException(class_basename(static::class).' must be written as [<algorithm>:<base64url>] with a known algorithm.');
        }

        return new static($algorithm, Encoding::decode($bytes));
    }

    protected function implementation(): SignatureAlgorithmContract
    {
        return $this->implementation ??= app($this->algorithm->implementationClass());
    }

    abstract protected static function length(SignatureAlgorithmContract $algorithm): int;
}
