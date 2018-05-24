<?php

namespace Basil\Import;


use Basil\Tree;
use Tree\Node\Node;

/**
 * Class ArrayImport
 *
 * Imports an associative array to Tree structure. The accepted and used array structure is the following:
 *
 * [
 *      "val1" => "value1",
 *      "val2" => 3,
 *      ...
 *      "children" => [
 *          "val1" => "foo",
 *          "val2" => 55,
 *          ...
 *          "children" => [
 *              ...
 *          ]
 *      ]
 * ]
 *
 * The mandatory is the "children" key, which is used to identify the child nodes of the parent node.
 *
 * Usage:
 *
 * $tree = (new ArrayImport())->execute($assoc_array);
 *
 * @package HierarchicalData\Import
 */
class ArrayImport extends Import
{
    const CHILDREN = "children";

    public function execute($data = null): Tree
    {
        return new Tree(self::buildTree($data));
    }

    private static function buildTree(array $data) {
        $data_copy = $data;
        unset($data_copy[self::CHILDREN]);

        $node = new Node($data_copy);

        if(isset($data[self::CHILDREN])) {
            foreach ($data[self::CHILDREN] as $child) {
                $node->addChild(self::buildTree($child));
            }
        }
        return $node;
    }
}