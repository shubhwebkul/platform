<?php declare(strict_types=1);

namespace Shopware\Currency\Factory;

use Doctrine\DBAL\Connection;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Currency\Extension\CurrencyExtension;
use Shopware\Currency\Struct\CurrencyBasicStruct;
use Shopware\Framework\Factory\ExtensionRegistryInterface;
use Shopware\Framework\Factory\Factory;
use Shopware\Search\QueryBuilder;
use Shopware\Search\QuerySelection;

class CurrencyBasicFactory extends Factory
{
    const ROOT_NAME = 'currency';
    const EXTENSION_NAMESPACE = 'currency';

    const FIELDS = [
       'uuid' => 'uuid',
       'is_default' => 'is_default',
       'factor' => 'factor',
       'symbol' => 'symbol',
       'symbol_position' => 'symbol_position',
       'position' => 'position',
       'created_at' => 'created_at',
       'updated_at' => 'updated_at',
       'short_name' => 'translation.short_name',
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
        CurrencyBasicStruct $currency,
        QuerySelection $selection,
        TranslationContext $context
    ): CurrencyBasicStruct {
        $currency->setUuid((string) $data[$selection->getField('uuid')]);
        $currency->setIsDefault((bool) $data[$selection->getField('is_default')]);
        $currency->setFactor((float) $data[$selection->getField('factor')]);
        $currency->setSymbol((string) $data[$selection->getField('symbol')]);
        $currency->setSymbolPosition((int) $data[$selection->getField('symbol_position')]);
        $currency->setPosition((int) $data[$selection->getField('position')]);
        $currency->setCreatedAt(isset($data[$selection->getField('created_at')]) ? new \DateTime($data[$selection->getField('created_at')]) : null);
        $currency->setUpdatedAt(isset($data[$selection->getField('updated_at')]) ? new \DateTime($data[$selection->getField('updated_at')]) : null);
        $currency->setShortName((string) $data[$selection->getField('short_name')]);
        $currency->setName((string) $data[$selection->getField('name')]);

        /** @var $extension CurrencyExtension */
        foreach ($this->getExtensions() as $extension) {
            $extension->hydrate($currency, $data, $selection, $context);
        }

        return $currency;
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
            'currency_translation',
            $translation->getRootEscaped(),
            sprintf(
                '%s.currency_uuid = %s.uuid AND %s.language_uuid = :languageUuid',
                $translation->getRootEscaped(),
                $selection->getRootEscaped(),
                $translation->getRootEscaped()
            )
        );
        $query->setParameter('languageUuid', $context->getShopUuid());
    }
}
