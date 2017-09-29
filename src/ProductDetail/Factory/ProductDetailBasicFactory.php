<?php declare(strict_types=1);

namespace Shopware\ProductDetail\Factory;

use Doctrine\DBAL\Connection;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Factory\ExtensionRegistryInterface;
use Shopware\Framework\Factory\Factory;
use Shopware\ProductDetail\Extension\ProductDetailExtension;
use Shopware\ProductDetail\Struct\ProductDetailBasicStruct;
use Shopware\ProductDetailPrice\Factory\ProductDetailPriceBasicFactory;
use Shopware\Search\QueryBuilder;
use Shopware\Search\QuerySelection;
use Shopware\Unit\Factory\UnitBasicFactory;
use Shopware\Unit\Struct\UnitBasicStruct;

class ProductDetailBasicFactory extends Factory
{
    const ROOT_NAME = 'product_detail';
    const EXTENSION_NAMESPACE = 'productDetail';

    const FIELDS = [
       'uuid' => 'uuid',
       'product_uuid' => 'product_uuid',
       'supplier_number' => 'supplier_number',
       'is_main' => 'is_main',
       'sales' => 'sales',
       'active' => 'active',
       'stock' => 'stock',
       'min_stock' => 'min_stock',
       'weight' => 'weight',
       'position' => 'position',
       'width' => 'width',
       'height' => 'height',
       'length' => 'length',
       'ean' => 'ean',
       'unit_uuid' => 'unit_uuid',
       'purchase_steps' => 'purchase_steps',
       'max_purchase' => 'max_purchase',
       'min_purchase' => 'min_purchase',
       'purchase_unit' => 'purchase_unit',
       'reference_unit' => 'reference_unit',
       'release_date' => 'release_date',
       'shipping_free' => 'shipping_free',
       'purchase_price' => 'purchase_price',
       'created_at' => 'created_at',
       'updated_at' => 'updated_at',
       'additional_text' => 'translation.additional_text',
       'pack_unit' => 'translation.pack_unit',
    ];

    /**
     * @var UnitBasicFactory
     */
    protected $unitFactory;

    /**
     * @var ProductDetailPriceBasicFactory
     */
    protected $productDetailPriceFactory;

    public function __construct(
        Connection $connection,
        ExtensionRegistryInterface $registry,
        UnitBasicFactory $unitFactory,
        ProductDetailPriceBasicFactory $productDetailPriceFactory
    ) {
        parent::__construct($connection, $registry);
        $this->unitFactory = $unitFactory;
        $this->productDetailPriceFactory = $productDetailPriceFactory;
    }

    public function hydrate(
        array $data,
        ProductDetailBasicStruct $productDetail,
        QuerySelection $selection,
        TranslationContext $context
    ): ProductDetailBasicStruct {
        $productDetail->setUuid((string) $data[$selection->getField('uuid')]);
        $productDetail->setProductUuid((string) $data[$selection->getField('product_uuid')]);
        $productDetail->setSupplierNumber(isset($data[$selection->getField('supplier_number')]) ? (string) $data[$selection->getField('supplier_number')] : null);
        $productDetail->setIsMain((bool) $data[$selection->getField('is_main')]);
        $productDetail->setSales((int) $data[$selection->getField('sales')]);
        $productDetail->setActive((bool) $data[$selection->getField('active')]);
        $productDetail->setStock((int) $data[$selection->getField('stock')]);
        $productDetail->setMinStock(isset($data[$selection->getField('min_stock')]) ? (int) $data[$selection->getField('min_stock')] : null);
        $productDetail->setWeight(isset($data[$selection->getField('weight')]) ? (float) $data[$selection->getField('weight')] : null);
        $productDetail->setPosition((int) $data[$selection->getField('position')]);
        $productDetail->setWidth(isset($data[$selection->getField('width')]) ? (float) $data[$selection->getField('width')] : null);
        $productDetail->setHeight(isset($data[$selection->getField('height')]) ? (float) $data[$selection->getField('height')] : null);
        $productDetail->setLength(isset($data[$selection->getField('length')]) ? (float) $data[$selection->getField('length')] : null);
        $productDetail->setEan(isset($data[$selection->getField('ean')]) ? (string) $data[$selection->getField('ean')] : null);
        $productDetail->setUnitUuid(isset($data[$selection->getField('unit_uuid')]) ? (string) $data[$selection->getField('unit_uuid')] : null);
        $productDetail->setPurchaseSteps(isset($data[$selection->getField('purchase_steps')]) ? (int) $data[$selection->getField('purchase_steps')] : null);
        $productDetail->setMaxPurchase(isset($data[$selection->getField('max_purchase')]) ? (int) $data[$selection->getField('max_purchase')] : null);
        $productDetail->setMinPurchase((int) $data[$selection->getField('min_purchase')]);
        $productDetail->setPurchaseUnit(isset($data[$selection->getField('purchase_unit')]) ? (float) $data[$selection->getField('purchase_unit')] : null);
        $productDetail->setReferenceUnit(isset($data[$selection->getField('reference_unit')]) ? (float) $data[$selection->getField('reference_unit')] : null);
        $productDetail->setReleaseDate(isset($data[$selection->getField('release_date')]) ? new \DateTime($data[$selection->getField('release_date')]) : null);
        $productDetail->setShippingFree((bool) $data[$selection->getField('shipping_free')]);
        $productDetail->setPurchasePrice((float) $data[$selection->getField('purchase_price')]);
        $productDetail->setCreatedAt(isset($data[$selection->getField('created_at')]) ? new \DateTime($data[$selection->getField('created_at')]) : null);
        $productDetail->setUpdatedAt(isset($data[$selection->getField('updated_at')]) ? new \DateTime($data[$selection->getField('updated_at')]) : null);
        $productDetail->setAdditionalText(isset($data[$selection->getField('additional_text')]) ? (string) $data[$selection->getField('additional_text')] : null);
        $productDetail->setPackUnit(isset($data[$selection->getField('pack_unit')]) ? (string) $data[$selection->getField('pack_unit')] : null);
        $unit = $selection->filter('unit');
        if ($unit && !empty($data[$unit->getField('uuid')])) {
            $productDetail->setUnit(
                $this->unitFactory->hydrate($data, new UnitBasicStruct(), $unit, $context)
            );
        }

        /** @var $extension ProductDetailExtension */
        foreach ($this->getExtensions() as $extension) {
            $extension->hydrate($productDetail, $data, $selection, $context);
        }

        return $productDetail;
    }

    public function getFields(): array
    {
        $fields = array_merge(self::FIELDS, parent::getFields());

        $fields['unit'] = $this->unitFactory->getFields();

        return $fields;
    }

    public function joinDependencies(QuerySelection $selection, QueryBuilder $query, TranslationContext $context): void
    {
        $this->joinUnit($selection, $query, $context);
        $this->joinPrices($selection, $query, $context);
        $this->joinTranslation($selection, $query, $context);

        $this->joinExtensionDependencies($selection, $query, $context);
    }

    public function getAllFields(): array
    {
        $fields = array_merge(self::FIELDS, $this->getExtensionFields());
        $fields['unit'] = $this->unitFactory->getAllFields();
        $fields['prices'] = $this->productDetailPriceFactory->getAllFields();

        return $fields;
    }

    protected function getRootName(): string
    {
        return self::ROOT_NAME;
    }

    protected function getExtensionNamespace(): string
    {
        return self::EXTENSION_NAMESPACE;
    }

    private function joinUnit(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($unit = $selection->filter('unit'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'unit',
            $unit->getRootEscaped(),
            sprintf('%s.uuid = %s.unit_uuid', $unit->getRootEscaped(), $selection->getRootEscaped())
        );
        $this->unitFactory->joinDependencies($unit, $query, $context);
    }

    private function joinPrices(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($prices = $selection->filter('prices'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_detail_price',
            $prices->getRootEscaped(),
            sprintf('%s.uuid = %s.product_detail_uuid', $selection->getRootEscaped(), $prices->getRootEscaped())
        );

        $this->productDetailPriceFactory->joinDependencies($prices, $query, $context);

        $query->groupBy(sprintf('%s.uuid', $selection->getRootEscaped()));
    }

    private function joinTranslation(
        QuerySelection $selection,
        QueryBuilder $query,
        TranslationContext $context
    ): void {
        if (!($translation = $selection->filter('translation'))) {
            return;
        }
        $query->leftJoin(
            $selection->getRootEscaped(),
            'product_detail_translation',
            $translation->getRootEscaped(),
            sprintf(
                '%s.product_detail_uuid = %s.uuid AND %s.language_uuid = :languageUuid',
                $translation->getRootEscaped(),
                $selection->getRootEscaped(),
                $translation->getRootEscaped()
            )
        );
        $query->setParameter('languageUuid', $context->getShopUuid());
    }
}
