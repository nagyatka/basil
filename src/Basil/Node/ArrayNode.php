<?php

namespace Basil\Node;


use Basil\DataSource\ArraySource;


/**
 * Class ArrayNode
 *
 *
 * @package Basil\Node
 */
class ArrayNode extends Node
{
    /**
     * ArrayNode constructor.
     * @param ArraySource $source
     * @param $id
     * @param array|null $data
     * @param int $parent
     * @param null $children
     */
    public function __construct($source, $id, array $data = null, $parent = -1, $children = null)
    {
        /**
         * in := node_id_fieldname
         * cn := children_fieldname
         */
        list($in, $cn) = $source->arr_params();

        if($id == $source->getOption(ArraySource::ROOT_ID)) {
            $whole_tree = $source->data();

            $id = $whole_tree[$in];
            $children = [];
            if(isset($whole_tree[$cn])){
                foreach ($whole_tree[$cn] as $child) {
                    $children[] = new ArrayNode($source, $child[$in], $child, $id);
                }
            }
            unset($whole_tree[$cn]);

            $data = $whole_tree;
            parent::__construct($source, $id, $data, $parent, $children);
        }
        else {
            $children = [];
            if(isset($data[$cn])) {
                foreach ($data[$cn] as $child) {
                    $children[] = new ArrayNode($source, $child[$in], $child, $id);
                }
            }
            unset($data[$cn]);
            parent::__construct($source, $id, $data, $parent, $children);
        }
    }


    public function add(array $data): Node
    {
        /** @var ArraySource $src */
        $src = $this->source();
        list($in, $cn) = $src->arr_params();
        $node = new ArrayNode($src, $data[$in], $data, $this->id());
        $this->addChild($node);

        return $node;
    }

    public function remove(): void
    {
        throw new \Exception("Not yet implemented");
    }

    public function load(): void
    {
        // It already happened in the constructor
    }

    public function subtree(): void
    {
        // It already happened in the constructor
    }

    public function find($node_id): ?Node
    {
        throw new \Exception("Not yet implemented");
    }

    public function descendants(int $level = -1): array
    {
        throw new \Exception("Not yet implemented");
    }

    public function ancestors(int $level = -1): array
    {
        throw new \Exception("Not yet implemented");
    }

    public function leaves(): array
    {
        throw new \Exception("Not yet implemented");
    }
}