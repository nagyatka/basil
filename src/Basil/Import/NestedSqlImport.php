<?php

namespace Basil\Import;

use Basil\Tree;
use Tree\Node\Node;

/**
 * Class NestedSqlImport
 *
 *  The required option fields:
 *  - "pdo": It contains a valid PDO object
 *  - "table": The name of the NestedSet table
 *  - "id": The name of the primary key field
 *  - "parent_id": The name of parent node id field
 *  - "lft": The name of left field
 *  - "rgt": The name of right field
 *
 * @package HierarchicalData\Import
 */
class NestedSqlImport extends Import
{
    const DB        = "pdo";
    const TABLE_NAME= "table";
    const NODE_ID   = "node_id";
    const LEFT      = "lft";
    const RIGHT     = "rgt";
    const ROOT_ID   = "root_id";

    private static $option_fields = [
        self::TABLE_NAME,
        self::NODE_ID,
        self::LEFT,
        self::RIGHT
    ];

    /**
     * @param array $data
     * @return Tree
     */
    public function execute($data = null): Tree
    {
        /*
         * Collecting db params
         */
        /** @var $db \PDO */
        if(!($db = $this->getOption(self::DB))) {
            throw new \InvalidArgumentException("Missing pdo option in NestedSqlImport");
        }

        if(!($root_id = $this->getOption(self::ROOT_ID))) {
            throw new \InvalidArgumentException("Missing root_id option in NestedSqlImport");
        }

        $options = [];
        foreach (self::$option_fields as $option_field) {
            if(!($of = $this->getOption($option_field))) {
                throw new \InvalidArgumentException("Missing ".$option_field." option in NestedSqlImport");
            }
            $options[$option_field] = $of;
        }
        $table_name  = $options[NestedSqlImport::TABLE_NAME];
        $lft = $options[NestedSqlImport::LEFT];
        $rgt = $options[NestedSqlImport::RIGHT];
        $node_id = $options[NestedSqlImport::NODE_ID];

        /*
         * Assembling and executing the query
         */
        $statement = $db->prepare("
          SELECT * 
          FROM ".$table_name." as node,
               ".$table_name." as parent   
          WHERE node.".$lft." BETWEEN parent.".$lft." AND parent.".$rgt." AND parent.".$node_id." = :node_id
          ORDER BY node.".$lft);
        $statement->execute(['node_id' => $root_id]);

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
        self::buildTree($root_node, $result, $lft, $rgt);
        return new Tree($root_node);
    }

    /**
     * Builds the Tree recursively
     * @param Node $root_node
     * @param Node[] $result
     * @param string $lft
     * @param string $rgt
     * @return bool
     */
    private static function buildTree(Node &$root_node, $result, $lft, $rgt) {
        if(count($result) < 1) {
            return true;
        }

        if($root_node->getValue()[$rgt] > $result[0]->getValue()[$rgt]) {
            return false;
        }
        else {
            $next_node = array_shift($result);
            $root_node->addChild($next_node);
            if(count($result) < 1) {
                return true;
            }

            while(self::buildTree($next_node, $result, $lft, $rgt) == false) {
                if($root_node->getValue()[$rgt]  > $result[0]->getValue()[$rgt] ) {
                    return false;
                }
                $next_node = array_shift($result);
                $root_node->addChild($next_node);
                if(count($result) < 1) {
                    return true;
                }
            }
            return true;
        }
    }
}