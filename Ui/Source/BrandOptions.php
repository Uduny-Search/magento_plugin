<?php
/**
 * Brand / Manufacturer Option Source
 *
 * Provides searchable multiselect options from the product `manufacturer`
 * (and fallback `brand`) attribute for the Export List admin form.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Ui\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Data\OptionSourceInterface;

class BrandOptions implements OptionSourceInterface
{
    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * Constructor
     *
     * @param EavConfig $eavConfig
     */
    public function __construct(
        EavConfig $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Return brand/manufacturer attribute options sorted alphabetically
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = [];
        $seenValues = [];

        foreach (['manufacturer', 'brand'] as $attributeCode) {
            try {
                $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
                if (!$attribute || !$attribute->getId() || !$attribute->usesSource()) {
                    continue;
                }

                $sourceOptions = $attribute->getSource()->getAllOptions(false);
                foreach ($sourceOptions as $option) {
                    $value = isset($option['value']) ? trim((string) $option['value']) : '';
                    $label = isset($option['label']) ? trim((string) $option['label']) : '';

                    if ($value === '' || $label === '' || isset($seenValues[$value])) {
                        continue;
                    }

                    $seenValues[$value] = true;
                    $options[] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        usort(
            $options,
            static function (array $a, array $b): int {
                return strcasecmp($a['label'], $b['label']);
            }
        );

        $this->options = $options;

        return $this->options;
    }
}
