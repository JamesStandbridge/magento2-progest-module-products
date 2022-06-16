<?php
/**
 * @author    Boeki, <james.standbridge.git@gmail.com>
 * @copyright Boeki
 */

namespace Progest\Products\Service;

use Progest\Products\Service\SwapTable\SwapTableManager;
use Progest\Products\Service\Product\ProductFactory;

class JobHandler
{
    const BIO_ATTRIBUTE_SET_ID = 9;

    private $tableManager;
    private $productFactory;
    private $logger;

    public function __construct(
        SwapTableManager $tableManager,
        ProductFactory $productFactory
    )
    {
        $this->tableManager = $tableManager;
        $this->productFactory = $productFactory;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function handleNewProducts()
    {
        $rawProducts = $this->tableManager->getNewProducts();
        $products_processed = 0;

        $this->logger->info(sprintf("Number of new products to be processed : %s",count($rawProducts)));
        
        foreach($rawProducts as $rawProduct) {
            // if(!$rawProduct["prix"] || strlen(trim($rawProduct["prix"])) === 0) {
            //     $this->logger->info(sprintf("Can't create product sku = %s because his price is null",  $rawProduct["code_article"]));
            //     continue;
            // }           
            if(!$rawProduct["nom"] || strlen(trim($rawProduct["nom"])) === 0) {
                $this->logger->info(sprintf("Can't create product sku = %s because his name is null",  $rawProduct["code_article"]));
                continue;
            }
            try {
                $product = $this->productFactory->createOrUpdate(
                    $rawProduct["code_article"],
                    $rawProduct["nom"],
                    self::BIO_ATTRIBUTE_SET_ID,
                    'simple',
                    $rawProduct["prix"],
                    4,
                    $rawProduct["poids"]
                );
            } catch(\Exception $e) {
                $this->logger->info($e->getMessage());
                continue;
            }

            $this->productFactory
                ->setAttributeValue($product, "description", $rawProduct["description"])
                ->setAttributeValue($product, "description_courte", $rawProduct["description_courte"])
                ->setAttributeValue($product, "marque", $rawProduct["marque"])
                ->setAttributeValue($product, "unite", $rawProduct["unite"])
                ->setAttributeValue($product, "increment", $rawProduct["increment"])
                ->setAttributeValue($product, "libelles", $rawProduct["libelles"])
                ->setAttributeValue($product, "prix_special", $rawProduct["prix_special"])
                ->setAttributeValue($product, "date_debut_prix_special", $rawProduct["date_debut_prix_special"])
                ->setAttributeValue($product, "date_fin_prix_special", $rawProduct["date_fin_prix_special"])
                ->setAttributeValue($product, "classe_tva", $rawProduct["classe_tva"])
                ->setAttributeValue($product, "origine", $rawProduct["origine"])
                ->setAttributeValue($product, "calibre", $rawProduct["calibre"])
                ->setAttributeValue($product, "categorie", $rawProduct["calibre"])
                ->setAttributeValue($product, "ean_sku", $rawProduct["sku"])
                ->setAttributeValue($product, "status", $rawProduct["status"])
                ->setAttributeValue($product, "bio_arbo", $rawProduct["bio_arbo"])
                ->setAttributeValue($product, "progest_arbo", $rawProduct["progest_arbo"])
                ->setPrice($product, $rawProduct["prix"])
                ->setQuantity($product, $rawProduct["quantite"])
            ;

            try {
                $product->save();

                $this->logger->info(sprintf("New product added to the catalog sku = %s", $product->getSku()));
                $products_processed++;
                $this->tableManager->updateProcessDates($rawProduct["code_article"]);
            } catch(\Exception $e) {
                if(get_class($e) === "Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException") {
                    $product->setUrlKey($product->getUrlKey() .  "-". $rawProduct["code_article"]);
                    $product->save();
                    $this->logger->info(sprintf("URL key concat with sku because of duplication on product %s.", 
                    $product->getSku()));
                    $this->logger->info(sprintf("New product added to the catalog sku = %s", $product->getSku()));
                    $this->tableManager->updateProcessDates($rawProduct["code_article"]);
                } else {
                    $this->logger->info(sprintf("Error while creating the product %s. %s", 
                    $product->getSku(), $e->getMessage()));
                }
            }
        }

        return $products_processed;
    }

    public function handleColdUpdateProducts()
    {
        $rawProducts = $this->tableManager->getColdUpdateProducts();
        $products_processed = 0;
    
        $this->logger->info(sprintf("Number of products with cold data to update : %s",count($rawProducts)));

        foreach($rawProducts as $rawProduct) {
            try {
                $product = $this->productFactory->createOrUpdate(
                    $rawProduct["code_article"],
                    $rawProduct["nom"],
                    self::BIO_ATTRIBUTE_SET_ID,
                    'simple',
                    $rawProduct["prix"],
                    4,
                    $rawProduct["poids"]
                );
            } catch(\Exception $e) {
                $this->logger->info($e->getMessage());
                continue;
            }

            $this->productFactory
                ->setAttributeValue($product, "description", $rawProduct["description"])
                ->setAttributeValue($product, "description_courte", $rawProduct["description_courte"])
                ->setAttributeValue($product, "marque", $rawProduct["marque"])
                ->setAttributeValue($product, "unite", $rawProduct["unite"])
                ->setAttributeValue($product, "increment", $rawProduct["increment"])
                ->setAttributeValue($product, "libelles", $rawProduct["libelles"])
                ->setAttributeValue($product, "prix_special", $rawProduct["prix_special"])
                ->setAttributeValue($product, "date_debut_prix_special", $rawProduct["date_debut_prix_special"])
                ->setAttributeValue($product, "date_fin_prix_special", $rawProduct["date_fin_prix_special"])
                ->setAttributeValue($product, "classe_tva", $rawProduct["classe_tva"])
                ->setAttributeValue($product, "ean_sku", $rawProduct["sku"])
                ->setAttributeValue($product, "status", $rawProduct["status"])
                ->setAttributeValue($product, "bio_arbo", $rawProduct["bio_arbo"])
                ->setAttributeValue($product, "progest_arbo", $rawProduct["progest_arbo"])
            ;



            try {
                $product->save();
                $this->logger->info(sprintf("Product cold data update sku = %s successful", $product->getSku()));
                $products_processed++;
                $this->tableManager->updateColdProcessDate($rawProduct["code_article"]);
            } catch(\Exception $e) {
                $this->logger->info(sprintf("Error when updating the cold data of the product sku = %s. %s", 
                $product->getSku(), $e->getMessage()));
            }

        }

        return $products_processed;
    }

    public function handleHotUpdateProducts() 
    {
        $rawProducts = $this->tableManager->getHotUpdateProducts();
        $products_processed = 0;

        $this->logger->info(sprintf("Number of products with hot data to update : %s",count($rawProducts)));

        foreach($rawProducts as $rawProduct) {
            $product = $this->productFactory->get($rawProduct["code_article"]);

            if(!$product) {
                $this->logger->info(sprintf("Impossible to update the data of the product sku = %s because it does not exist yet", $rawProduct["code_article"]));
                continue;
            }

            $this->productFactory
                ->setPrice($product, $rawProduct["prix"])
                ->setQuantity($product, $rawProduct["quantite"])
                ->setAttributeValue($product, "origine", $rawProduct["origine"])
                ->setAttributeValue($product, "calibre", $rawProduct["calibre"])
                ->setAttributeValue($product, "categorie", $rawProduct["calibre"])
            ;

            try {
                $product->save();
                $this->logger->info(sprintf("Product hot data update sku = %s successful", $product->getSku()));
                $products_processed++;
                $this->tableManager->updateHotProcessDate($rawProduct["code_article"]);
            } catch(\Exception $e) {
                $this->logger->info(sprintf("Error when updating the hot data of the product sku = %s. %s", 
                $product->getSku(), $e->getMessage()));
            }
        }

        return $products_processed;
    }
}