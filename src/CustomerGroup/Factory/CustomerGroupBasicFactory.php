<?php declare(strict_types=1);

namespace Shopware\CustomerGroup\Factory;

use Doctrine\DBAL\Connection;
use Shopware\Context\Struct\TranslationContext;
use Shopware\CustomerGroup\Extension\CustomerGroupExtension;
use Shopware\CustomerGroup\Struct\CustomerGroupBasicStruct;
use Shopware\Framework\Factory\ExtensionRegistryInterface;
use Shopware\Framework\Factory\Factory;
use Shopware\Search\QueryBuilder;
use Shopware\Search\QuerySelection;

class CustomerGroupBasicFactory extends Factory
{
    const ROOT_NAME = 'customer_group';
    const EXTENSION_NAMESPACE = 'customerGroup';

    const FIELDS = [
       'uuid' => 'uuid',
       'display_gross' => 'display_gross',
       'input_gross' => 'input_gross',
       'has_global_discount' => 'has_global_discount',
       'percentage_global_discount' => 'percentage_global_discount',
       'minimum_order_amount' => 'minimum_order_amount',
       'minimum_order_amount_surcharge' => 'minimum_order_amount_surcharge',
       'created_at' => 'created_at',
       'updated_at' => 'updated_at',
       'name' => 'translation.name',
    ];

    public function __construct(
        Connection $connection,
        ExtensionRegistryInterface $registry
    ) {
        parent::__construct($connection, $registry);
    }

    public function hydrate(
        array $data,
        CustomerGroupBasicStruct $customerGroup,
        QuerySelection $selection,
        TranslationContext $context
    ): CustomerGroupBasicStruct {
        $customerGroup->setUuid((string) $data[$selection->getField('uuid')]);
        $customerGroup->setDisplayGross((bool) $data[$selection->getField('display_gross')]);
        $customerGroup->setInputGross((bool) $data[$selection->getField('input_gross')]);
        $customerGroup->setHasGlobalDiscount((bool) $data[$selection->getField('has_global_discount')]);
        $customerGroup->setPercentageGlobalDiscount(isset($data[$selection->getField('percentage_global_discount')]) ? (float) $data[$selection->getField('percentage_global_discount')] : null);
        $customerGroup->setMinimumOrderAmount(isset($data[$selection->getField('minimum_order_amount')]) ? (float) $data[$selection->getField('minimum_order_amount')] : null);
        $customerGroup->setMinimumOrderAmountSurcharge(isset($data[$selection->getField('minimum_order_amount_surcharge')]) ? (float) $data[$selection->getField('minimum_order_amount_surcharge')] : null);
        $customerGroup->setCreatedAt(isset($data[$selection->getField('created_at')]) ? new \DateTime($data[$selection->getField('created_at')]) : null);
        $customerGroup->setUpdatedAt(isset($data[$selection->getField('updated_at')]) ? new \DateTime($data[$selection->getField('updated_at')]) : null);
        $customerGroup->setName((string) $data[$selection->getField('name')]);

        /** @var $extension CustomerGroupExtension */
        foreach ($this->getExtensions() as $extension) {
            $extension->hydrate($customerGroup, $data, $selection, $context);
        }

        return $customerGroup;
    }

    public function getFields(): array
    {
        $fields = array_merge(self::FIELDS, parent::getFields());

        return $fields;
    }

    public function joinDependencies(QuerySelection $selection, QueryBuilder $query, TranslationContext $context): void
    {
        $this->joinTranslation($selection, $query, $context);

        $this->joinExtensionDependencies($selection, $query, $context);
    }

    public function getAllFields(): array
    {
        $fields = array_merge(self::FIELDS, $this->getExtensionFields());

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
            'customer_group_translation',
            $translation->getRootEscaped(),
            sprintf(
                '%s.customer_group_uuid = %s.uuid AND %s.language_uuid = :languageUuid',
                $translation->getRootEscaped(),
                $selection->getRootEscaped(),
                $translation->getRootEscaped()
            )
        );
        $query->setParameter('languageUuid', $context->getShopUuid());
    }
}
