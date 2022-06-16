<?php
/**
 * @author    Boeki, <james.standbridge.git@gmail.com>
 * @copyright Boeki
 */

namespace Progest\Products\Service\SwapTable;

use Magento\Framework\App\ResourceConnection;

use DateTime;

class SwapTableManager
{
    private $resourceConnection;
    
    public function __construct(ResourceConnection $resourceConnection) 
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getProducts()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $query = sprintf("Select * FROM %s", $table);
        $result = $connection->fetchAll($query);

        return $result;
    }

    public function getNewProducts()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $query = sprintf(
            "SELECT * FROM %s 
            WHERE update_cold is not null AND update_hot is not null && process_cold is null", 
            $table
        );
        $result = $connection->fetchAll($query);

        return $result;
    }

    public function getColdUpdateProducts()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $query = sprintf(
            "SELECT * FROM %s 
            WHERE `process_cold` is not null AND (`update_cold` > `process_cold`)", 
            $table
        );
        $result = $connection->fetchAll($query);

        return $result;
    }

    public function getHotUpdateProducts()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $query = sprintf(
            "SELECT * FROM %s 
            WHERE `process_cold` is not null and `process_hot` is not null AND (`update_hot` > `process_hot`)", 
            $table
        );
        $result = $connection->fetchAll($query);

        return $result;
    }

    public function updateHotProcessDate($code_article)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $date = (new DateTime())->format("Y-m-d H:i:s");

        $query = sprintf(
            "UPDATE `%s` SET `process_hot`= '%s' WHERE `code_article` = %s",
            $table, $date, $code_article
        );
        $result = $connection->query($query);

        return $result;
    }

    public function updateColdProcessDate($code_article)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $date = (new DateTime())->format("Y-m-d H:i:s");

        $query = sprintf(
            "UPDATE `%s` SET `process_cold`= '%s' WHERE `code_article` = %s",
            $table, $date, $code_article
        );
        $result = $connection->query($query);

        return $result;
    }

    public function updateProcessDates($code_article)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('progest_swap_product');

        $date = (new DateTime())->format("Y-m-d H:i:s");

        $query = sprintf(
            "UPDATE `%s` SET `process_cold`= '%s', `process_hot`= '%s' WHERE `code_article` = %s",
            $table, $date, $date, $code_article
        );
        $result = $connection->query($query);

        return $result;
    }
}