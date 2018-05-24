<?php

namespace Basil\Export;


use Basil\NodeOperation;
use Basil\Tree;
use Tree\Node\Node;
use Tree\Visitor\PreOrderVisitor;

class AdjacencySqlExport extends Export
{
    const DB            = "pdo";
    const TABLE_NAME    = "table";
    const PARENT_ID     = "parent_id";
    const NODE_ID       = "node_id";

    public function execute(Tree $tree): mixed
    {
        /** @var \PDO $db */
        if(!($db = $this->getOption(self::DB))) {
            throw new \InvalidArgumentException("Missing ".self::DB." option in AdjacencySqlExport");
        }

        if(!($parent_field = $this->getOption(self::PARENT_ID))) {
            throw new \InvalidArgumentException("Missing ".self::PARENT_ID." option in AdjacencySqlExport");
        }

        if(!($node_id_field = $this->getOption(self::NODE_ID))) {
            throw new \InvalidArgumentException("Missing ".self::NODE_ID." option in AdjacencySqlExport");
        }

        if(!($table_name = $this->getOption(self::TABLE_NAME))) {
            throw new \InvalidArgumentException("Missing ".self::TABLE_NAME." option in AdjacencySqlExport");
        }

        /*
         * Yielding nodes
         */
        $root = $tree->getRootNode();
        $nodes = $root->accept(new PreOrderVisitor());

        /*
         * Mapping nodes to associative array
         */
        $p_nodes = array_map(function ($node) use($node_id_field, $parent_field) {
            /** @var Node $node */
            $parent_id = NodeOperation::getNodeValue($node, $node_id_field);
            $values = $node->getValue();
            $values[$parent_field] = $parent_id;
            return $values;
        }, $nodes);

        /*
         * Executing multi insert (replace into)
         */
        return Export::pdoMultiInsert($table_name, $p_nodes, $db);
    }
}