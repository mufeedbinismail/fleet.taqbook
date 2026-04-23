<?php

namespace App\Shared\Enum;

enum SystemType: int
{
    case Journal          = 0;   // ST_JOURNAL

    case BankPayment      = 1;   // ST_BANKPAYMENT
    case BankDeposit      = 2;   // ST_BANKDEPOSIT
    case BankTransfer     = 4;   // ST_BANKTRANSFER

    case SalesInvoice     = 10;  // ST_SALESINVOICE
    case CustomerCredit   = 11;  // ST_CUSTCREDIT
    case CustomerPayment  = 12;  // ST_CUSTPAYMENT
    case CustomerDelivery = 13;  // ST_CUSTDELIVERY

    case LocTransfer      = 16;  // ST_LOCTRANSFER
    case InvAdjust        = 17;  // ST_INVADJUST

    case PurchOrder       = 18;  // ST_PURCHORDER
    case SupplierInvoice  = 20;  // ST_SUPPINVOICE
    case SupplierCredit   = 21;  // ST_SUPPCREDIT
    case SupplierPayment  = 22;  // ST_SUPPAYMENT
    case SupplierReceive  = 25;  // ST_SUPPRECEIVE

    case WorkOrder        = 26;  // ST_WORKORDER
    case ManuIssue        = 28;  // ST_MANUISSUE
    case ManuReceive      = 29;  // ST_MANURECEIVE

    case SalesOrder       = 30;  // ST_SALESORDER
    case SalesQuote       = 32;  // ST_SALESQUOTE

    case CostUpdate       = 35;  // ST_COSTUPDATE

    case Dimension        = 40;  // ST_DIMENSION
    case Customer         = 41;  // ST_CUSTOMER
    case Supplier         = 42;  // ST_SUPPLIER
    case Item             = 43;  // ST_ITEM
    case FixedAsset       = 44;  // ST_FIXEDASSET
    case BankAccount      = 45;  // ST_BANKACCOUNT

    case Statement        = 91;  // ST_STATEMENT
    case Cheque           = 92;  // ST_CHEQUE
}
