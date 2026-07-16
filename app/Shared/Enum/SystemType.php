<?php

namespace App\Shared\Enum;

use App\Foundation\Concern\Enum\HasLabelConcern;
use App\Foundation\Contract\Enum\HasLabelContract;

enum SystemType: int implements HasLabelContract
{
    use HasLabelConcern;

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

    public function abbr(): ?string
    {
        return self::abbreviations()[$this->value] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::Journal->value          => __("Journal Entry"),
            self::BankPayment->value      => __("Bank Payment"),
            self::BankDeposit->value      => __("Bank Deposit"),
            self::BankTransfer->value     => __("Funds Transfer"),
            self::SalesInvoice->value     => __("Sales Invoice"),
            self::CustomerCredit->value   => __("Customer Credit Note"),
            self::CustomerPayment->value  => __("Customer Payment"),
            self::CustomerDelivery->value => __("Delivery Note"),
            self::LocTransfer->value      => __("Location Transfer"),
            self::InvAdjust->value        => __("Inventory Adjustment"),
            self::PurchOrder->value       => __("Purchase Order"),
            self::SupplierInvoice->value  => __("Supplier Invoice"),
            self::SupplierCredit->value   => __("Supplier Credit Note"),
            self::SupplierPayment->value  => __("Supplier Payment"),
            self::SupplierReceive->value  => __("Purchase Order Delivery"),
            self::WorkOrder->value        => __("Work Order"),
            self::ManuIssue->value        => __("Work Order Issue"),
            self::ManuReceive->value      => __("Work Order Production"),
            self::SalesOrder->value       => __("Sales Order"),
            self::SalesQuote->value       => __("Sales Quotation"),
            self::CostUpdate->value       => __("Cost Update"),
            self::Dimension->value        => __("Dimension"),
            self::Customer->value         => __("Customer"),
            self::Supplier->value         => __("Supplier"),
            self::Item->value             => __("Item"),
            self::FixedAsset->value       => __("Fixed Asset"),
            self::BankAccount->value      => __("Bank Account"),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function abbreviations(): array
    {
        return [
            self::Journal->value          => __("GJ"), // general journal
            self::BankPayment->value      => __("BP"),
            self::BankDeposit->value      => __("BD"),
            self::BankTransfer->value     => __("BT"),
            self::SalesInvoice->value     => __("SI"),
            self::CustomerCredit->value   => __("CN"),
            self::CustomerPayment->value  => __("CP"),
            self::CustomerDelivery->value => __("DN"),
            self::LocTransfer->value      => __("IT"), // inventory transfer
            self::InvAdjust->value        => __("IA"),
            self::PurchOrder->value       => __("PO"),
            self::SupplierInvoice->value  => __("PI"), // purchase invoice
            self::SupplierCredit->value   => __("PC"),
            self::SupplierPayment->value  => __("SP"),
            self::SupplierReceive->value  => __("GRN"),
            self::WorkOrder->value        => __("WO"),
            self::ManuIssue->value        => __("WI"),
            self::ManuReceive->value      => __("WP"),
            self::SalesOrder->value       => __("SO"),
            self::SalesQuote->value       => __("SQ"),
            self::CostUpdate->value       => __("CU"),
            self::Dimension->value        => __("Dim"),
        ];
    }
}
