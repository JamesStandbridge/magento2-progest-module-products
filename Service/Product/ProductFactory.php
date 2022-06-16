<?php
/**
 * @author    Boeki, <james.standbridge.git@gmail.com>
 * @copyright Boeki
 */

namespace Progest\Products\Service\Product;

use Magento\Catalog\Model\ProductFactory as MagProductFactory;
use Magento\Catalog\Model\Product;
use Boeki\Core\Helper\Entity\Attribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class ProductFactory
{
    private $factory;
    private $attributeHelper;
    private $productRepository;


    const TAX_ASSOCIATION = [
        "20" => "6",
        "10" => "7",
        "5.5" => "8",
        "5,5" => "8",
        "0.1" => "9",
        "0,1" => "9",
        "shipping" => "4"
    ];

    public function __construct(
        MagProductFactory $factory,
        Attribute $attributeHelper,
        ProductRepositoryInterface $productRepository
    ) 
    {
        $this->factory = $factory;
        $this->attributeHelper = $attributeHelper;
        $this->productRepository = $productRepository;
    }

    public function get($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            return null;
        }
    }

    public function createOrUpdate(
        $sku,
        $name,
        $attributeSetId,
        $typeId,
        $price,
        $visiblibilty,
        $weight
    )
    {
        try {
            $product = $this->productRepository->get($sku);
            $product = $this->factory->create()->load($product->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            $product = $this->factory->create();
        }

        $product
            ->setVisibility($visiblibilty)
            ->setName($name)
            ->setCategoryIds([])
            ->setSku($sku)
            ->setAttributeSetId($attributeSetId)
            ->setWebsiteIds(array(0))
            ->setStoreId(0)
            ->setTypeId($typeId)
            ->setWeight($weight)
        ;

        return $product;
    }

    public function setAttributeValue($product, $progestLabel, $value)
    {
        switch($progestLabel) {
            case "increment": {
                $product->setBioIncrement($this->getOptionId($value, "bio_increment"));
                break;
            }
            case "origine": {
                $product->setBioProvenance($value);
                break;
            }
            case "bio_arbo": {
                $product->setBioArbo($value);
                break;
            }
            case "progest_arbo": {
                $product->setProgestArbo($value);
                break;
            }
            case "calibre": {
                $product->setBioCalibre($value);
                break;
            }
            case "categorie": {
                $product->setProgestCategorie($value);
                break;
            }
            case "libelles": {
                $product->setBioLabels($value);
                break;
            }
            case "marque": {
                $product->setBioMarque($this->getOptionId($value, "bio_marque"));
                break;
            }
            case "calibre": {
                $product->setBioCalibre($value);
                break;
            }
            case "unite": {
                $product->setBioUnite($this->getOptionId($value, "bio_unite"));
                break;
            }
            case "description": {
                $product->setDescription($value);
                break;
            }
            case "description_courte": {
                $product->setShortDescription($value);
                break;
            }
            case "prix_special": {
                $product->setSpecialPrice($value);
                break;
            }
            case "date_debut_prix_special": {

                break;
            }
            case "date_fin_prix_special": {

                break;
            }
            case "classe_tva": {
                $option = self::TAX_ASSOCIATION[$value];
                $product->setTaxClassId($option);
                break;
            }
            case "origine": {
                $product->setBioProvenance($value);
                break;
            }
            case "ean_sku": {
                $product->setEanSku($value);
                break;
            }
            case "status": {
                if($value == "1") {
                    $product->setStatus(Status::STATUS_ENABLED);
                } else {
                    $product->setStatus(Status::STATUS_DISABLED);
                }
                break;
            }
            default: {
                throw new \LogicException(
                    sprintf(
                        "Invalid attribute name %s encountered during product %s creation. Check Config && progest_swap_table columns", 
                        $progestLabel, $product->getSku()
                    )
                );
            }
        }

        return $this;
    }

    public function setPrice($product, $price)
    {
        $product->setPrice($price);

        return $this;
    }

    public function setQuantity($product, $quantity)
    {
        $product->setQuantityAndStockStatus(['qty' => $quantity, 'is_in_stock' => $quantity > 0 ? 1 : 0]);

        return $this;
    }

    public function getOptionId($value, $attributeCode)
    {
        $optionId = $this->attributeHelper->getOptionId($value, $attributeCode);
        if(!$optionId) {
            $optionId = $this->attributeHelper->createOption($value, $attributeCode);
        }
        return $optionId;
    }
}