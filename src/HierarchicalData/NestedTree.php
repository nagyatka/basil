<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 04.
 * Time: 17:06
 */

namespace HierarchicalData;

use Tree\Node\Node;

class NestedTree implements Tree
{
    /*
     * Constant declarations
     */
    const TYPE_NESTED_SET = "nested_set";
    const TYPE_ADJACENCY_LIST = "adjacency_list";

    const TABLE_NAME = "table";
    const NODE_ID = "node_id";
    const LEFT = "lft";
    const RIGHT = "rgt";


    /*
     * Contains the default settings for the database.
     */
    const pdo_default_options = [
        NestedTree::TABLE_NAME=> "tree",
        NestedTree::NODE_ID   => "node_id",
        NestedTree::LEFT      => "lft",
        NestedTree::RIGHT     => "rgt"
    ];

    /**
     * @var NestedNode
     */
    private $root_node;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var array
     */
    private $pdo_options;

    /**
     * NestedTree constructor.
     * @param NestedNode $root_node
     * @param \PDO|null $pdo
     * @param array|null $pdo_options
     */
    public function __construct(NestedNode $root_node, \PDO $pdo = null, $pdo_options = null)
    {
        $this->root_node = $root_node;
        $this->pdo = $pdo;
        $this->pdo_options = $pdo_options;
    }


    /**
     * @param \PDO $pdo
     * @param mixed $root_data
     * @param array|null $options
     * @return Tree
     * @throws \Exception
     */
    public static function create(\PDO $pdo, mixed $root_data, array $options = null): Tree {
        $pdo_options = $options == null ? NestedTree::pdo_default_options : $options;
        return new NestedTree(new NestedNode(0,1,$root_data), $pdo, $pdo_options);
    }


    /**
     * Loads
     * @param \PDO $pdo
     * @param int|null $root_id
     * @param array|null $options
     * @return Tree
     */
    public static function load(\PDO $pdo, int $root_id = null, array $options = null): Tree
    {
        /*
         * Collecting db params
         */
        $pdo_options = $options == null ? NestedTree::pdo_default_options : $options;
        $table_name  = $pdo_options[NestedTree::TABLE_NAME];
        $lft = $pdo_options[NestedTree::LEFT];
        $rgt = $pdo_options[NestedTree::RIGHT];
        $node_id = $pdo_options[NestedTree::NODE_ID];

        /*
         * Assembling and executing the query
         */
        $statement = $pdo->prepare("
          SELECT * 
          FROM ".$table_name." as node,
               ".$table_name." as parent   
          WHERE node.".$lft." BETWEEN parent.".$lft." AND parent.".$rgt." AND parent.".$node_id." = :node_id
          ORDER BY node.".$lft);
        $statement->execute(['node_id' => $root_id]);

        /*
         * Preprocessing query result
         */
        $result = array_map(function ($row) use ($lft, $rgt) {
            return new NestedNode($row[$lft], $row[$rgt], $row);
        }, $statement->fetchAll(\PDO::FETCH_ASSOC));

        /*
         * Building tree
         */
        $root_node = array_shift($result);
        self::buildTree($root_node, $result);
        return new NestedTree($root_node, $pdo, $pdo_options);
    }

    /**
     * buildTree is a recursive function for building tree structure using an array of NestedNodes
     *
     * IMPORTANT: The function supposes that the array elements are ordered by the left value!
     *
     * @param NestedNode $root_node
     * @param NestedNode[] $result
     * @return bool
     */
    private static function buildTree(&$root_node, $result) {
        if(count($result) < 1) {
            return true;
        }

        if($root_node->getRgt() > $result[0]->getRgt()) {
            return false;
        }
        else {
            $next_node = array_shift($result);
            $root_node->addChild($next_node);
            if(count($result) < 1) {
                return true;
            }

            while(self::buildTree($next_node, $result) == false) {
                if($root_node->getRgt() > $result[0]->getRgt()) {
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

    /**
     * Dumps the whole Set structure to the desired destination
     * @param string $dump_type
     * @param mixed $dump_destination
     * @return mixed
     */
    public function dump(string $dump_type, $dump_destination = null): void {

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
         * @var NestedNode[]
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
     * @return NestedNode[]
     */
    public function getAncestorsAndSelf($depth = null) {
        if($depth == 0) {
            return [$this->root_node];
        }
        return array_merge([$this->root_node], $this->getAncestors($depth));
    }
}