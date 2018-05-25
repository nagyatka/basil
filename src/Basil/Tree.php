<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 24.
 * Time: 11:28
 */

namespace Basil;


use Basil\Import\AdjacencySqlImport;
use Basil\Import\ArrayImport;
use Basil\Import\Import;
use Basil\Export\Export;
use Basil\Import\JsonImport;
use Basil\Import\RootNodeImport;
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

    /**
     * Converts a json string to Tree object. See the details in JsonImport documentation.
     *
     * @param string $json_string
     * @return Tree
     */
    public static function fromJson(string $json_string): Tree {
        return self::import(new JsonImport(), $json_string);
    }

    /**
     * Converts a Node object to Tree object. See the details in RootNodeImport documentation.
     *
     * @param Node $node
     * @return Tree
     */
    public static function fromNode(Node $node): Tree {
        return self::import(new RootNodeImport(), $node);
    }

    /**
     * Loads an AdjacencyList (from the specified table) in a Tree object. See the details in AdjacencySqlImport
     * documentation.
     *
     * Important note: The whole table will be loaded in the Tree object. Handling of a subtree is not supported
     * at this moment!
     *
     * @param \PDO $pdo Database PDO object
     * @param string $table_name Name of the table
     * @param string $id_field The name of the primary key in the table
     * @param string $parent_field
     * @param int $root_id
     * @return Tree
     */
    public static function fromAdjacencyList(
        \PDO $pdo,
        string $table_name,
        string $id_field,
        string $parent_field,
        int $root_id): Tree {
        return self::import(new AdjacencySqlImport([
            AdjacencySqlImport::DB          => $pdo,
            AdjacencySqlImport::TABLE_NAME  => $table_name,
            AdjacencySqlImport::NODE_ID     => $id_field,
            AdjacencySqlImport::PARENT_ID   => $parent_field,
            AdjacencySqlImport::ROOT_ID     => $root_id
        ]));
    }

    /**
     * @param Import $import
     * @param Export $export
     * @param mixed|null $data
     * @return mixed
     */
    public static function convert(Import $import, Export $export, mixed $data = null) {
        return self::import($import, $data)->export($export);
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
     * @return Node[]
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

    public function addChild(Node $node) {
        $this->root_node->addChild($node);
    }

    public function getSize() {
        return $this->root_node->getSize();
    }

}