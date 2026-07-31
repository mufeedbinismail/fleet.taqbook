<?php

namespace App\Navigation\Constant;

/**
 * The key of every area, named so it cannot be mistyped.
 *
 * What areas exist is settled by what the sources declare; these are handles on those keys and
 * carry no authority of their own. A key naming an area nobody declared finds nothing in the tree,
 * however it was spelled.
 */
final class Area
{
    public const ASSET         = 'asset';
    public const FINANCE       = 'finance';
    public const INVENTORY     = 'inventory';
    public const MANUFACTURING = 'inventory.manufacturing';
    public const MARKETPLACE   = 'trade.marketplace';
    public const PURCHASE      = 'trade.purchase';
    public const SALE          = 'trade.sale';
    public const SYSTEM        = 'foundation.system';
}
