<?php

namespace App\Foundation\Navigation\Enum;

/**
 * What kind of thing a destination is. Pure taxonomy: it carries no presentation of its own, so
 * mapping a category to an icon stays a theme's decision.
 */
enum Category: string
{
    case Entry = 'menu_entry';
    case Inquiry = 'menu_inquiry';
    case Maintenance = 'menu_maintenance';
    case Report = 'menu_report';
    case Settings = 'menu_settings';
    case System = 'menu_system';
    case Transaction = 'menu_transaction';
    case Update = 'menu_update';
}
