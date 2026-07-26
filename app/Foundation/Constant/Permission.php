<?php

namespace App\Foundation\Constant;

/**
 * Every permission key there is, named so it cannot be mistyped.
 *
 * The catalog itself lives in the permissions table. These are handles on it and nothing more: a
 * constant here grants nothing, and a key with no row behind it is refused however it was spelled.
 *
 * Named for what the grant lets somebody do rather than for where its key sits, because a name is
 * read far more often than a key is. Two grants cannot share a name — the compiler sees to that —
 * so a name says only as much as it needs to to stand apart.
 */
final class Permission
{
    /*
        Resolved in code rather than from the table. OPEN keeps a page reachable without a grant —
        or without a login at all; DENIED switches one off for a client build by editing the single
        line that names it; AUTHENTICATED is OPEN with the login still required.

        None carries a dot, so none can collide with a catalog key below.
    */
    public const OPEN = 'open';

    public const DENIED = 'denied';

    public const AUTHENTICATED = 'authenticated';

    public const ASSET_TRANSACTION_ANALYTICS           = 'asset.transaction.analytics';
    public const CREATE_ASSET_TRANSFER                 = 'asset.transfer.create';
    public const CREATE_DEPRECIATION                   = 'asset.depreciation.create';
    public const CREATE_DISPOSAL                       = 'asset.disposal.create';
    public const MANAGE_ASSET_CATEGORY                 = 'asset.category.manage';
    public const MANAGE_ASSET_CLASS                    = 'asset.class.manage';
    public const MANAGE_ASSET_ITEM                     = 'asset.item.manage';
    public const VIEW_ASSET_TRANSACTION                = 'asset.transaction.view';

    public const BANKING_TRANSACTION_REPORT            = 'finance.banking.transaction.report';
    public const CREATE_BANKING_JOURNAL_ENTRY          = 'finance.banking.journal-entry.create';
    public const CREATE_BANKING_PAYMENT                = 'finance.banking.payment.create';
    public const CREATE_BANKING_TRANSFER               = 'finance.banking.transfer.create';
    public const CREATE_DEPOSIT                        = 'finance.banking.deposit.create';
    public const CREATE_RECONCILIATION                 = 'finance.banking.reconciliation.create';
    public const MANAGE_BANKING_ACCOUNT                = 'finance.banking.account.manage';
    public const VIEW_BANKING_TRANSACTION              = 'finance.banking.transaction.view';

    public const CONFIGURE_LEDGER                      = 'finance.ledger.setup.configure';
    public const CREATE_ACCRUAL                        = 'finance.ledger.accrual.create';
    public const CREATE_LEDGER_JOURNAL_ENTRY           = 'finance.ledger.journal-entry.create';
    public const LEDGER_POSTING_ANALYTICS              = 'finance.ledger.posting.analytics';
    public const LEDGER_POSTING_REPORT                 = 'finance.ledger.posting.report';
    public const MANAGE_ACCOUNT_CLASS                  = 'finance.ledger.account-class.manage';
    public const MANAGE_ACCOUNT_GROUP                  = 'finance.ledger.account-group.manage';
    public const MANAGE_ACCOUNT_TAG                    = 'finance.ledger.account-tag.manage';
    public const MANAGE_LEDGER_ACCOUNT                 = 'finance.ledger.account.manage';
    public const MANAGE_QUICK_ENTRY                    = 'finance.ledger.quick-entry.manage';
    public const VIEW_LEDGER_POSTING                   = 'finance.ledger.posting.view';

    public const CLOSE_PERIOD                          = 'finance.shared.period.close';
    public const CREATE_DIMENSION                      = 'finance.shared.dimension.create';
    public const DIMENSION_REPORT                      = 'finance.shared.dimension.report';
    public const MANAGE_BUDGET                         = 'finance.shared.budget.manage';
    public const MANAGE_CURRENCY                       = 'finance.shared.currency.manage';
    public const MANAGE_DIMENSION_TAG                  = 'finance.shared.dimension-tag.manage';
    public const MANAGE_EXCHANGE_RATE                  = 'finance.shared.exchange-rate.manage';
    public const MANAGE_FISCAL_YEAR                    = 'finance.shared.fiscal-year.manage';
    public const MANAGE_NON_CLOSED_YEAR                = 'finance.shared.non-closed-year.manage';
    public const MANAGE_STANDARD_COST                  = 'finance.shared.standard-cost.manage';
    public const REOPEN_PERIOD                         = 'finance.shared.period.reopen';
    public const VIEW_DIMENSION                        = 'finance.shared.dimension.view';

    public const MANAGE_ITEM_TAX_TYPE                  = 'finance.tax.item-type.manage';
    public const MANAGE_TAX_GROUP                      = 'finance.tax.group.manage';
    public const MANAGE_TAX_RATE                       = 'finance.tax.rate.manage';
    public const TAX_TRANSACTION_REPORT                = 'finance.tax.transaction.report';

    public const EDIT_OTHERS_RECORD                    = 'foundation.record.edit-others';
    public const MANAGE_ATTACHMENT                     = 'foundation.attachment.manage';
    public const VIEW_RECORD                           = 'foundation.record.view';
    public const VOID_RECORD                           = 'foundation.record.void';

    public const CHANGE_PASSWORD                       = 'foundation.access.password.change';
    public const MANAGE_ROLE                           = 'foundation.access.role.manage';
    public const MANAGE_USER                           = 'foundation.access.user.manage';

    public const CONFIGURE_COMPANY                     = 'foundation.system.company.configure';
    public const CONFIGURE_DISPLAY                     = 'foundation.system.display.configure';
    public const MANAGE_BACKUP                         = 'foundation.system.backup.manage';
    public const MANAGE_FORM_TEMPLATE                  = 'foundation.system.form-template.manage';
    public const MANAGE_PRINTER                        = 'foundation.system.printer.manage';
    public const MANAGE_PRINT_PROFILE                  = 'foundation.system.print-profile.manage';

    public const CREATE_ADJUSTMENT                     = 'inventory.adjustment.create';
    public const CREATE_INVENTORY_TRANSFER             = 'inventory.transfer.create';
    public const INVENTORY_REORDER_REPORT              = 'inventory.reorder.report';
    public const INVENTORY_TRANSACTION_ANALYTICS       = 'inventory.transaction.analytics';
    public const MANAGE_FOREIGN_CODE                   = 'inventory.foreign-code.manage';
    public const MANAGE_INVENTORY_CATEGORY             = 'inventory.category.manage';
    public const MANAGE_INVENTORY_ITEM                 = 'inventory.item.manage';
    public const MANAGE_INVENTORY_LOCATION             = 'inventory.location.manage';
    public const MANAGE_INVENTORY_UNIT                 = 'inventory.unit.manage';
    public const MANAGE_KIT                            = 'inventory.kit.manage';
    public const MANAGE_MOVEMENT_TYPE                  = 'inventory.movement-type.manage';
    public const VALUATION_REPORT                      = 'inventory.valuation.report';
    public const VIEW_INVENTORY_STATUS                 = 'inventory.status.view';
    public const VIEW_INVENTORY_TRANSACTION            = 'inventory.transaction.view';

    public const BOM_REPORT                            = 'inventory.manufacturing.bom.report';
    public const BULK_PRINT_MANUFACTURING_DOCUMENT     = 'inventory.manufacturing.document.bulk-print';
    public const CREATE_MANUFACTURING_ISSUE            = 'inventory.manufacturing.issue.create';
    public const CREATE_MANUFACTURING_RECEIVAL         = 'inventory.manufacturing.receival.create';
    public const CREATE_MANUFACTURING_RELEASE          = 'inventory.manufacturing.release.create';
    public const CREATE_WORK_ORDER                     = 'inventory.manufacturing.work-order.create';
    public const MANAGE_BOM                            = 'inventory.manufacturing.bom.manage';
    public const MANAGE_WORK_CENTRE                    = 'inventory.manufacturing.work-centre.manage';
    public const MANUFACTURING_COST_REPORT             = 'inventory.manufacturing.cost.report';
    public const VIEW_MANUFACTURING_OPERATION          = 'inventory.manufacturing.operation.view';
    public const WORK_ORDER_ANALYTICS                  = 'inventory.manufacturing.work-order.analytics';

    public const ALLOCATE_MARKETPLACE_PAYMENT          = 'trade.marketplace.payment.allocate';
    public const CREATE_MARKETPLACE_CREDIT_NOTE        = 'trade.marketplace.credit-note.create';
    public const CREATE_MARKETPLACE_DELIVERY           = 'trade.marketplace.delivery.create';
    public const CREATE_MARKETPLACE_FREEHAND_CREDIT    = 'trade.marketplace.freehand-credit.create';
    public const CREATE_MARKETPLACE_INVOICE            = 'trade.marketplace.invoice.create';
    public const CREATE_MARKETPLACE_ORDER              = 'trade.marketplace.order.create';
    public const CREATE_MARKETPLACE_PAYMENT            = 'trade.marketplace.payment.create';
    public const CREATE_MARKETPLACE_SUPPLIER_CREDIT    = 'trade.marketplace.supplier-credit.create';
    public const CREATE_MARKETPLACE_SUPPLIER_INVOICE   = 'trade.marketplace.supplier-invoice.create';
    public const MANAGE_MARKETPLACE_CHANNEL            = 'trade.marketplace.channel.manage';
    public const VIEW_MARKETPLACE_SALE_TRANSACTION     = 'trade.marketplace.sale-transaction.view';
    public const VIEW_MARKETPLACE_SUPPLIER_TRANSACTION = 'trade.marketplace.supplier-transaction.view';

    public const ALLOCATE_PURCHASE_PAYMENT             = 'trade.purchase.payment.allocate';
    public const BULK_PRINT_PURCHASE_DOCUMENT          = 'trade.purchase.document.bulk-print';
    public const CREATE_PURCHASE_CREDIT_NOTE           = 'trade.purchase.credit-note.create';
    public const CREATE_PURCHASE_INVOICE               = 'trade.purchase.invoice.create';
    public const CREATE_PURCHASE_ORDER                 = 'trade.purchase.order.create';
    public const CREATE_PURCHASE_PAYMENT               = 'trade.purchase.payment.create';
    public const CREATE_PURCHASE_RECEIVAL              = 'trade.purchase.receival.create';
    public const DELETE_RECEIVAL_ITEM                  = 'trade.purchase.receival-item.delete';
    public const MANAGE_PURCHASE_PRICE                 = 'trade.purchase.price.manage';
    public const MANAGE_SUPPLIER                       = 'trade.purchase.supplier.manage';
    public const PURCHASE_PAYMENT_REPORT               = 'trade.purchase.payment.report';
    public const PURCHASE_TRANSACTION_ANALYTICS        = 'trade.purchase.transaction.analytics';
    public const VIEW_PURCHASE_TRANSACTION             = 'trade.purchase.transaction.view';

    public const ALLOCATE_SALE_PAYMENT                 = 'trade.sale.payment.allocate';
    public const BULK_PRINT_SALE_TRANSACTION           = 'trade.sale.transaction.bulk-print';
    public const CREATE_QUOTATION                      = 'trade.sale.quotation.create';
    public const CREATE_SALE_CREDIT_NOTE               = 'trade.sale.credit-note.create';
    public const CREATE_SALE_DELIVERY                  = 'trade.sale.delivery.create';
    public const CREATE_SALE_FREEHAND_CREDIT           = 'trade.sale.freehand-credit.create';
    public const CREATE_SALE_INVOICE                   = 'trade.sale.invoice.create';
    public const CREATE_SALE_ORDER                     = 'trade.sale.order.create';
    public const CREATE_SALE_PAYMENT                   = 'trade.sale.payment.create';
    public const CUSTOMER_PAYMENT_REPORT               = 'trade.sale.customer-payment.report';
    public const CUSTOMER_REPORT                       = 'trade.sale.customer.report';
    public const CUSTOMER_STATUS_REPORT                = 'trade.sale.customer-status.report';
    public const MANAGE_CONTACT_CATEGORY               = 'trade.sale.contact-category.manage';
    public const MANAGE_CREDIT_STATUS                  = 'trade.sale.credit-status.manage';
    public const MANAGE_CUSTOMER                       = 'trade.sale.customer.manage';
    public const MANAGE_POS                            = 'trade.sale.pos.manage';
    public const MANAGE_RECURRENT_INVOICE              = 'trade.sale.recurrent-invoice.manage';
    public const MANAGE_SALESMAN                       = 'trade.sale.salesman.manage';
    public const MANAGE_SALE_AREA                      = 'trade.sale.area.manage';
    public const MANAGE_SALE_GROUP                     = 'trade.sale.group.manage';
    public const MANAGE_SALE_PRICE                     = 'trade.sale.price.manage';
    public const MANAGE_SALE_TEMPLATE                  = 'trade.sale.template.manage';
    public const MANAGE_SALE_TYPE                      = 'trade.sale.type.manage';
    public const MANAGE_SHIPPING                       = 'trade.sale.shipping.manage';
    public const SALESMAN_REPORT                       = 'trade.sale.salesman.report';
    public const SALE_PRICE_REPORT                     = 'trade.sale.price.report';
    public const SALE_TRANSACTION_ANALYTICS            = 'trade.sale.transaction.analytics';
    public const VIEW_SALE_TRANSACTION                 = 'trade.sale.transaction.view';

    public const MANAGE_PAYMENT_TERM                   = 'trade.shared.payment-term.manage';
}
