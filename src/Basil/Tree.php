<?php
namespace Basil;


use Basil\DataSource\AdjacencyListSource;
use Basil\DataSource\ArraySource;
use Basil\DataSource\DataSource;
use Basil\DataSource\NestedSetSource;
use Basil\Node\AdjacencyListNode;
use Basil\Node\ArrayNode;
use Basil\Node\NestedSetNode;
use Basil\Node\Node;

class Tree
{
    /**
     * Loads the tree from the desired datasource and node. Depends on the exact DataSource, the tree will be loaded
     * on a lazy way.
     *
     * If the node_id parameter equals with null, the function assumes that the whole tree (from the root node) have
     * to be loaded.
     *
     * Currently supported DataSources: NestedSetSource, AdjacencyListSource
     *
     * @param DataSource $src Source of the tree data.
     * @param int|null $node_id Id of the node
     * @return Node
     * @throws \Exception When the $src parameter is unknown.
     */
    public static function from(DataSource $src, $node_id = null) {
        /*
         * If the node_id parameter equals with null, the function assumes that the whole tree (from the root node) have
         * to be loaded.
         */
        if($node_id == null) {
            $node_id = $src->getOption(NestedSetSource::ROOT_ID);
        }

        if($src instanceof NestedSetSource) {
            return new NestedSetNode($src, $node_id);
        }
        elseif ($src instanceof AdjacencyListSource) {
            return new AdjacencyListNode($src, $node_id);
        }
        elseif ($src instanceof ArraySource) {
            return new ArrayNode($src, $node_id);
        }

        throw new \Exception("Unknown DataSource type");
    }

    /**
     * Converts the data from $from_src format to $to_src format. Note that the whole tree will be converted.
     *
     * @param DataSource $from_src
     * @param DataSource $to_src
     * @return mixed|null
     */
    public static function convert(DataSource $from_src, DataSource $to_src) {
        $root_node = Tree::from($from_src);
        $root_node->subtree();
        return $to_src->convert($root_node);
    }
}