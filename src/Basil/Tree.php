<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 24.
 * Time: 11:28
 */

namespace Basil;


use Basil\Import\ArrayImport;
use Basil\Import\Import;
use Basil\Export\Export;
use Tree\Node\Node;

class Tree
{
    /**
     * @var Node
     */
    private $root_node;

    /**
     * Tree constructor.
     * @param Node $root_node
     */
    public function __construct(Node $root_node)
    {
        $this->root_node = $root_node;
    }

    /**
     * @param Import $importer
     * @param null $data
     * @return Tree
     */
    public static function import(Import $importer, $data = null) {
        return $importer->execute($data);
    }

    /**
     * Executes the input Dump process and returns with its result.
     *
     * @param Export $exporter
     * @return mixed
     */
    public function export(Export $exporter): mixed {
        return $exporter->execute($this);
    }

    /**
     * Converts an array to Tree object. See the details in ArrayImport documentation.
     *
     * @param array $arr
     * @return Tree
     */
    public static function fromArray(array $arr): Tree {
        return self::import(new ArrayImport(), $arr);
    }


    /*
     * Operations
     */

    /**
     * Returns with the root node.
     *
     * @return Node
     */
    public function getRootNode(): Node
    {
        return $this->root_node;
    }

    /**
     * @param null $depth
     * @return NestedNode[]
     */
    public function getAncestors($depth = null) {
        if($depth == null) {
            return $this->root_node->getAncestors();
        }

        $result = [];
        /**
         * @var Node[]
         */
        $working_nodes = [$this->root_node];
        for($i = 0; $i < $depth-1; $i++) {
            $temp_nodes = [];
            foreach ($working_nodes as $node) {
                $temp_nodes = array_merge($temp_nodes, $node->getChildren());
            }
            $working_nodes = $temp_nodes;
            $result = array_merge($result, $temp_nodes);
        }
        return $result;
    }

    /**
     * @param null $depth
     * @return Node[]
     */
    public function getAncestorsAndSelf($depth = null) {
        if($depth == 0) {
            return [$this->root_node];
        }
        return array_merge([$this->root_node], $this->getAncestors($depth));
    }

    public function addChild() {
        // TODO
        $this->root_node->getChildren();
    }

    public function getSize() {
        return $this->root_node->getSize();
    }

}