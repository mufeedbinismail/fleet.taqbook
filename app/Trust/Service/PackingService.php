<?php

namespace App\Trust\Service;

use App\Trust\Entity\PrivateKey;
use App\Trust\Entity\PublicKey;
use App\Trust\Statement\Statement;
use App\Trust\ValueObject\PackedStatement;
use InvalidArgumentException;
use JsonException;
use SodiumException;

class PackingService
{
    /**
     * @template T of Statement
     *
     * @param  T  $statement
     * @return PackedStatement<T>
     */
    public function pack(Statement $statement, PrivateKey $signer): PackedStatement
    {
        return new PackedStatement($statement, $signer->sign($statement->toEncodedString()));
    }

    /**
     * @return PackedStatement<Statement>
     *
     * @throws InvalidArgumentException|JsonException|SodiumException when it cannot be read, or $signer did not sign it
     */
    public function unpack(string $packed, PublicKey $signer): PackedStatement
    {
        $read = PackedStatement::fromEncodedString($packed);

        // Checked over the statement re-encoded the one way it is signed, so what was read is exactly what was signed.
        if (! $signer->verify($read->signature, $read->statement->toEncodedString())) {
            throw new InvalidArgumentException("Invalid signature on [{$read->statement::kind()->value}].");
        }

        return $read;
    }
}
