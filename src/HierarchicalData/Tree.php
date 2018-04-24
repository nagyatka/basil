<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 24.
 * Time: 11:28
 */

namespace HierarchicalData;


interface Tree
{
    public static function create(\PDO $pdo, array $options = null): Tree;
    public static function load(\PDO $pdo, int $root_id = null, array $options = null): Tree;
}