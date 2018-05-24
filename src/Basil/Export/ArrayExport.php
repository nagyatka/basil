<?php

namespace Basil\Export;


use Basil\Tree;
use Tree\Node\Node;

class ArrayExport extends Export
{
    const CHILDREN = "children";

    public function execute(Tree $tree): mixed
    {
        try {
            return self::buildAssocArray($tree->getRootNode());
        }
        catch (\Exception $exception) {
            // TODO: SHOW THE EXCEPTION!!!!
            return false;
        }
    }

    private static function buildAssocArray(Node $node):array {

        $node_values= $node->getValue();
        $children   = [];

        foreach ($node->getChildren() as $child) {
            $children[] = self::buildAssocArray($child);
        }
        return array_merge($node_values, $children);
    }
}