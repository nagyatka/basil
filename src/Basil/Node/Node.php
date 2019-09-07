<?php

namespace Basil\Node;


use Basil\DataSource\DataSource;

abstract class Node implements \ArrayAccess
{
    /**
     * @var DataSource
     */
    private $source;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array
     */
    protected $data;

    /**
     * Ha a null akkor nincs szülő, ha -1 nem tudjuk
     * @var Node|null|-1
     */
    protected $parent;

    /**
     * Gyerekek a fában. Ha üres nincs gyerek, ha null akkor még nem lett felépítve
     * @var Node[]|null
     */
    protected $children;

    /**
     * Node constructor.
     * @param $source
     * @param $id
     * @param array $data
     * @param int|Node|null $parent
     * @param Node[]|null $children
     */
    public function __construct($source, $id, array $data = null, $parent = -1, $children = null)
    {
        $this->source = $source;
        $this->id = $id;
        $this->data = $data;
        $this->parent = $parent;
        $this->children = $children;
    }


    /*
     * Módosító műveleteknél állapotot kell eltárolni, majd amikor a tényleges mentés van akkor kell egy nagy műveletet
     * végrehajtani, ami először lehúzza az egész fát, belerakja a módosítást.
     */

    /**
     * Adds a new child node to the current node. The new node will be added to table immediately too.
     *
     * @param array $data
     * @return mixed
     */
    public abstract function add(array $data): Node;

    /**
     * Removes the node from the tree.
     */
    public abstract function remove(): void;

    /*
     * A lekérdező műveletek esetén közvetlenül futtassunk lekérdezéseket, így sokkal gyorsabb lesz a művelet (nagy fák esetén)
     *
     * ha van id beállítva tudjuk melyik node, ha nincs akkor rootot töltjük le
     */

    /**
     * Loads data which correspond to that specific node.
     */
    public abstract function load(): void;

    /**
     * Builds up the whole tree under the node and returns itself.
     *
     */
    public abstract function subtree(): void;

    /**
     * @param $node_id
     * @return Node|null
     */
    public abstract function find($node_id): ?Node;

    /**
     * @param int $level
     * @return Node[]
     */
    public abstract function descendants(int $level = -1): array;

    /**
     * @param int $level
     * @return Node[]
     */
    public abstract function ancestors(int $level = -1): array;

    /**
     * @return Node[]
     */
    public abstract function leaves(): array;

    /*
     * Alap metódusok
     */

    /**
     * @return DataSource
     */
    public function source() {
        return $this->source;
    }

    /**
     * Returns with the node_id. The null value can represent the root node of the tree.
     *
     * @return int|null
     */
    public function id(): ?int {
        return $this->id;
    }

    /**
     * Returns with the node data.
     *
     * @return array
     */
    public function data(): array {
        if(!isset($this->data)) {
            $this->load();
        }
        return $this->data;
    }


    public function addChild(Node $node) {
        $this->children[] = $node;
    }

    public function addParent(Node $node) {
        $this->parent = $node;
    }

    /**
     * @return Node[]
     */
    public function children() {
        if(!isset($this->children)) {
            $this->children = $this->descendants(1);
            foreach ($this->children as $child) {
                $child->parent = $this;
            }
        }
        return $this->children;
    }

    /**
     * @return Node|null
     */
    public function parent() {
        if($this->parent == -1) {
            $this->parent = $this->ancestors(1)[0];
        }
        return $this->parent;
    }



    /*
     * ArrayAccess implementation
     */

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }


}