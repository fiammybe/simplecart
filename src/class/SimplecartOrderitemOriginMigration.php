<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * Gives the order items from before 2.8.0 the product and campaign they came from
 *
 * An item only had the name of its product, so it is linked to the product with exactly that name.
 * A name that no product has, or that more than one product has, is left alone: guessing would put
 * orders in the wrong campaign. The campaign is the current one of the product, which for an old item
 * is an approximation. Safe to run more than once: only items without a product are looked at.
 */
final class SimplecartOrderitemOriginMigration
{
    public function __construct(
        private object $items,
        private object $products
    ) {
    }

    /**
     * @return array{linked: int, skipped: int}
     */
    public function run(): array
    {
        $productsByName = $this->productsByName();
        $linked = 0;
        $skipped = 0;

        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('product_id', 0));

        foreach ($this->items->getObjects($criteria) as $item) {
            $matches = $productsByName[(string)$item->getVar('product_name', 'n')] ?? [];

            if (count($matches) !== 1) {
                $skipped++;

                continue;
            }

            $product = $matches[0];
            $item->setVar('product_id', (int)$product->getVar('product_id', 'n'));
            $item->setVar('campaign_id', (int)$product->getVar('campaign_id', 'n'));

            if (! $this->items->insert($item, true)) {
                throw new RuntimeException("Could not link order item {$item->getVar('orderitem_id', 'n')} to its product");
            }

            $linked++;
        }

        return ['linked' => $linked, 'skipped' => $skipped];
    }

    /**
     * @return array<string, array<int, object>>
     */
    private function productsByName(): array
    {
        $byName = [];

        foreach ($this->products->getObjects() as $product) {
            $byName[(string)$product->getVar('name', 'n')][] = $product;
        }

        return $byName;
    }
}
