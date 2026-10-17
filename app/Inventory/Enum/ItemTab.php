<?php

namespace App\Inventory\Enum;

/**
 * The sections of the item page, in the order the page shows them. The value is what the address
 * carries after `tab=`, so renaming a case would break every link somebody has shared.
 */
enum ItemTab: string
{
    case General = 'general';
    case Sales = 'sales';
    case Purchasing = 'purchasing';
    case Costs = 'costs';
    case Reorder = 'reorder';
    case Transactions = 'transactions';
    case Status = 'status';
    case Attachments = 'attachments';
}
