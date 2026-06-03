<?php

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Collection\TaxableItemCollection;
use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use App\Marketplace\Cart\SupplierInvoiceCart;
use App\Marketplace\Cart\SupplierInvoiceCartLine;
use App\Shared\Enum\SupplierTransactionSource;
use App\Shared\Enum\SystemType;
use App\Shared\Enum\TransactionEffect;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

function read_marketplace_supplier_trans(int $transType, int $transNo): SupplierInvoiceCart
{
    $trans = DB::table('supp_trans as st')
        ->leftJoin('marketplaces as m', 'm.supplier_id', '=', 'st.supplier_id')
        ->where('st.type', $transType)
        ->where('st.trans_no', $transNo)
        ->select(
            'st.supplier_id',
            'st.tran_date',
            'st.reference',
            'st.supp_reference',
            'st.tax_included',
            'm.id as marketplace_id'
        )
        ->first();

    $supplierId  = (int) $trans->supplier_id;
    $taxIncluded = (bool) $trans->tax_included;

    $cart = new SupplierInvoiceCart(SystemType::from($transType));
    $cart->transNo       = $transNo;
    $cart->supplierId    = $supplierId;
    $cart->marketplaceId = $trans->marketplace_id ? (string) $trans->marketplace_id : null;
    $cart->date          = $trans->tran_date;
    $cart->reference     = $trans->reference;
    $cart->supplierRef   = $trans->supp_reference;

    $taxService = app(TaxService::class);
    $taxRepo    = app(TaxRepository::class);

    $items = DB::table('supp_invoice_items as inv')
        ->leftJoin('stock_master as stock', 'stock.stock_id', '=', 'inv.stock_id')
        ->where('inv.supp_trans_type', $transType)
        ->where('inv.supp_trans_no', $transNo)
        ->select(
            'inv.stock_id',
            'inv.description',
            'inv.quantity',
            'inv.unit_price',
            'inv.unit_tax',
            DB::raw("COALESCE(stock.units, '') AS units")
        )
        ->orderBy('inv.id')
        ->get();

    foreach ($items as $row) {
        $qty       = BigDecimal::of((string) $row->quantity);
        $unitPrice = MoneyFactory::of((string) $row->unit_price);
        $unitTax   = MoneyFactory::of((string) $row->unit_tax);

        $cart->addLine(new SupplierInvoiceCartLine(
            stockId:        $row->stock_id,
            description:    $row->description,
            unit:           $row->units,
            qty:            $qty,
            amount:         $unitPrice,
            itemTaxSetting: $taxRepo->getItemTaxSetting($row->stock_id),
            taxBreakdown:   $taxService->taxBreakdownFromStored($unitPrice, $unitTax, $qty, $taxIncluded),
        ));
    }

    return $cart;
}

function get_sql_for_marketplace_supplier_invoice_list(string $marketplaceId, string $fromDate, string $toDate): string
{
    $sql = "SELECT
                st.`type`,
                st.trans_no,
                st.reference,
                m.code AS marketplace_ref,
                m.name AS marketplace_name,
                st.tran_date,
                (st.ov_amount + st.ov_gst) AS total,
                (v.row_id is not null) as is_voided 
            FROM supp_trans st
            JOIN marketplaces m ON m.supplier_id = st.supplier_id
            LEFT JOIN voided v ON v.`type` = st.`type` AND v.id = st.trans_no
            WHERE
                v.row_id IS NULL
                AND st.source = ".SupplierTransactionSource::MarketplaceClearing->value."
                AND st.`type` = ".SystemType::SupplierInvoice->value;

    if (!empty($marketplaceId)) {
        $sql .= " AND m.id = ".db_escape((int)$marketplaceId);
    }
    if (!empty($fromDate)) {
        $sql .= " AND st.tran_date >= ".db_escape(date2sql($fromDate));
    }
    if (!empty($toDate)) {
        $sql .= " AND st.tran_date <= ".db_escape(date2sql($toDate));
    }

    return $sql;
}

function write_marketplace_supplier_invoice(SupplierInvoiceCart $cart): int
{
    global $Refs;

    $transType = $cart->transType->value;

    begin_transaction();
    $exRate = 1;
    $totalDiscount = 0;
    $totalTaxAdded = $cart->marketplaceTaxSetting->taxIncluded ? 0 : MoneyFactory::value($cart->totalTax());
    $cart->transNo = $transNo = write_supp_trans(
        $transType,
        $cart->transNo,
        $cart->supplierId,
        $cart->date,
        $cart->date,   // due_date = invoice date (settled in advance)
        $cart->reference,
        $cart->supplierRef,
        MoneyFactory::value($cart->totalRaw()),
        $totalTaxAdded,
        $totalDiscount,
        'Could not write supplier invoice',
        $exRate,
        $cart->marketplaceTaxSetting->taxIncluded,
        SupplierTransactionSource::MarketplaceClearing,
        TransactionEffect::Increase
    );

    foreach ($cart->line_items as $line) {
        add_supp_invoice_item(
            $transType,
            $transNo,
            $line->stockId,
            $line->description,
            '',
            MoneyFactory::value($line->amount),
            MoneyFactory::value($line->taxBreakdown->tax->dividedBy($line->qty, MoneyFactory::defaultRoundingMode())),
            (string) $line->qty,
            null,
            null,
            ''
        );
    }

    $taxService  = app(TaxService::class);
    $taxableItems = new TaxableItemCollection(collect($cart->line_items)->map->toTaxableItem()->all());
    $taxes = $taxService->calculateTaxes($taxableItems, $cart->marketplaceTaxSetting);

    foreach ($taxes as $draftTaxGroupLine) {
        if (!$draftTaxGroupLine->net->isZero()) {
            add_trans_tax_details(
                $transType,
                $transNo, 
				$draftTaxGroupLine->taxTypeId,
                (string) $draftTaxGroupLine->rate,
                $cart->marketplaceTaxSetting->taxIncluded,
                MoneyFactory::value($draftTaxGroupLine->tax),
				MoneyFactory::value($draftTaxGroupLine->net),
                $exRate,
                $cart->date,
                $cart->supplierRef,
                TR_INPUT
            );

            if ($draftTaxGroupLine->purchasingGlCode && $draftTaxGroupLine->purchasingProvisionGlCode) {
                add_gl_trans_supplier(
                    $transType,
                    $transNo,
                    $cart->date,
					$draftTaxGroupLine->purchasingGlCode,
                    0,
                    0,
                    MoneyFactory::value($draftTaxGroupLine->tax),
					$cart->supplierId,
					"A general ledger transaction for the tax amount could not be added",
                    $exRate,
                    "Tax Input"
                );

                add_gl_trans(
                    $transType,
                    $transNo,
                    $cart->date,
                    $draftTaxGroupLine->purchasingProvisionGlCode,
                    0,
                    0,
                    'Release: Input Tax Provision',
                    MoneyFactory::value($draftTaxGroupLine->tax->negated()),
                    null,
                    PT_MARKETPLACE,
                    $cart->marketplaceId,
                    "The general ledger transaction for total provision could not be added",
                    $exRate
                );
            }
        }
    }

    $totalNet = $cart->totalNet();
    if (!$totalNet->isZero()) {
        add_gl_trans_supplier(
            $transType,
            $transNo,
            $cart->date,
            $cart->payableAccount,
            0,
            0,
            MoneyFactory::value($totalNet->negated()),
            $cart->supplierId,
            "The general ledger transaction for total payable could not be added",
            $exRate,
            'Accounts Payable'
        );

        add_gl_trans(
            $transType,
            $transNo,
            $cart->date,
            $cart->provisionAccount,
            0,
            0,
            'Release: Marketplace Provision',
            MoneyFactory::value($totalNet),
            null,
            PT_MARKETPLACE,
            $cart->marketplaceId,
            "The general ledger transaction for total provision could not be added",
            $exRate
        );
    }

    add_comments($transType, $transNo, $cart->date, $cart->comments);
    $Refs->save($transType, $transNo, $cart->reference);

    commit_transaction();

    return $transNo;
}
