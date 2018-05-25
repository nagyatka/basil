<?php

namespace Basil\Import;


use Basil\NodeOperation;
use Basil\Tree;
use Tree\Node\Node;

/**
 * Class AdjacencySqlImport
 *
 *
 *
 * @package Basil\Import
 */
class AdjacencySqlImport extends Import
{
    /**
     * Mandatory option field names
     */
    const DB        = "pdo";
    const TABLE_NAME= "table";
    const NODE_ID   = "node_id";
    const PARENT_ID = "parent_id";
    const ROOT_ID   = "root_id";

    private static $fields = [
        self::DB, self::TABLE_NAME, self::NODE_ID, self::PARENT_ID, self::ROOT_ID
    ];

    public function execute($data = null): Tree
    {
        /*
         * Collecting options
         */
        $options = [];
        foreach (self::$fields as $option_field) {
            if(!($of = $this->getOption($option_field))) {
                throw new \InvalidArgumentException("Missing ".$option_field." option in NestedSqlImport");
            }
            $options[$option_field] = $of;
        }

        /*
         * Assembling and executing the query
         */
        /** @var \PDO $db */
        $db = $options[self::DB];
        $statement = $db->prepare("
          SELECT * 
          FROM ".$options[self::TABLE_NAME]);
        $statement->execute();

        /*
         * Preprocessing query result
         */
        $result = array_map(function ($row) {
            return new Node($row);
        }, $statement->fetchAll(\PDO::FETCH_ASSOC));

        /*
         * Building tree
         */
        $root_node = array_shift($result);
        $pc_nodes = NodeOperation::groupByField($result, $options[self::PARENT_ID]);
        self::buildTree($root_node, $pc_nodes, $options[self::NODE_ID]);
        return new Tree($root_node);
    }

    private static function buildTree(Node &$root, array &$pc_nodes, $node_id_field) {
        $parent_id = NodeOperation::getNodeValue($root, $node_id_field);
        foreach ($pc_nodes[$parent_id] as $node) {
            /** @var Node node */
            $root->addChild($node);
            self::buildTree($node, $pc_nodes, $node_id_field);
        }
    }
}