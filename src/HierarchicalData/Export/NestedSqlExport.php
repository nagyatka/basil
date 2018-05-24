<?php

namespace Basil\Dump;


use Basil\Export\Export;
use Basil\NodeOperation;
use Basil\Tree;
use Tree\Node\Node;
use Tree\Visitor\PreOrderVisitor;

/**
 * Class NestedSqlExport
 *
 * The NestedSqlExport dumps the input Tree structure to a Mysql NestedSet table.
 * Learn more about MySQL NestedSet: http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
 *
 * The required option fields:
 *  - "pdo": It contains a valid PDO object
 *  - "table": The name of the NestedSet table
 *  - "id": The name of the primary key field
 *  - "parent_id": The name of parent node id field
 *  - "lft": The name of left field
 *  - "rgt": The name of right field
 *
 * @package HierarchicalData\Dump
 */
class NestedSqlExport extends Export
{
    const DB            = "pdo";
    const TABLE_NAME    = "table";
    const PARENT_ID     = "parent_id";
    const NODE_ID       = "node_id";
    const LEFT          = "lft";
    const RIGHT         = "rgt";

    private static $option_fields = [
        self::TABLE_NAME,
        self::PARENT_ID,
        self::NODE_ID,
        self::LEFT,
        self::RIGHT
    ];

    public function execute(Tree $tree): mixed
    {
        /** @var \PDO $db */
        if(!($db = $this->getOption(self::DB))) {
            throw new \InvalidArgumentException("Missing pdo option in NestedSqlExport");
        }

        if(!($parent_field = $this->getOption(self::PARENT_ID))) {
            throw new \InvalidArgumentException("Missing pdo option in NestedSqlExport");
        }

        $options = [];
        foreach (self::$option_fields as $option_field) {
            if(!($of = $this->getOption($option_field))) {
                throw new \InvalidArgumentException("Missing ".$option_field." option in NestedSqlExport");
            }
            $options[$option_field] = $of;
        }

        /*
         * Yielding nodes
         */
        $root = $tree->getRootNode();
        $nodes = $root->accept(new PreOrderVisitor());
        $pc_nodes = NodeOperation::groupByField($nodes, $parent_field);

        /*
         * Updating left right values
         */
        $right = self::update_lft_rgt($pc_nodes, $root);
        NodeOperation::setNodeValue($parent, $options[self::LEFT], 1);
        NodeOperation::setNodeValue($parent, $options[self::RIGHT], $right);

        /*
         * Saving result to db
         */
        $data = array_merge(...array_values($pc_nodes));
        return self::pdoMultiInsert($options[self::TABLE_NAME], $data, $db);
    }

    /**
     * @param array $pc_nodes
     * @param Node $parent
     * @param int $left
     * @return int
     */
    private function update_lft_rgt($pc_nodes, &$parent, $left = 1) {

        $lft = $this->getOption(self::LEFT);
        $rgt = $this->getOption(self::RIGHT);
        $nif = $this->getOption(self::NODE_ID);

        /*
         * The current right value is equal with left + 1
         */
        $right = $left + 1;

        /*
         * Get all child
         */
        $child_nodes = $pc_nodes[NodeOperation::getNodeValue($parent, $nif)];
        foreach ($child_nodes as $child_node) {
            $right = self::update_lft_rgt($pc_nodes, $child_node, $right);
        }

        /*
         * Set the final left ang right values
         */
        NodeOperation::setNodeValue($parent, $lft, $left);
        NodeOperation::setNodeValue($parent, $rgt, $right);

        /*
         * Returns with the next right value
         */
        return $right+1;
    }
}