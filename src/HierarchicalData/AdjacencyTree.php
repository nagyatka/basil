<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 24.
 * Time: 15:59
 */

namespace HierarchicalData;


class AdjacencyTree implements Tree
{

    public static function create(\PDO $pdo, mixed $root_data, array $options = null): Tree
    {
        // TODO: Implement create() method.
    }

    public static function load(\PDO $pdo, int $root_id = null, array $options = null): Tree
    {
        // TODO: Implement load() method.
    }
}