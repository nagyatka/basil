<?php

namespace Basil\Node;


use Basil\DataSource\AdjacencyListSource;
use Basil\DataSource\DataSource;

class AdjacencyListNode extends Node
{

    public function add(array $data): Node
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $pn, $rni) = $this->db_params();

        $data[$pn] = $this->id();
        unset($data[$in]);
        $params_key = array_keys($data);

        //Lekérdezés összeállítása
        $insert_query   =   "INSERT INTO $tn (";
        for($i = 0; $i < count($params_key); ++$i) {
            $insert_query.= $params_key[$i];
            if($i < (count($params_key)-1) ) $insert_query.=",";
        }
        $insert_query .= ") VALUES (";

        for($i = 0; $i < count($params_key); ++$i) {
            $insert_query.= ":".$params_key[$i];
            if($i < (count($params_key)-1) ) $insert_query.=",";
        }
        $insert_query.= ")";

        $stmt = $db->prepare($insert_query);
        for($i = 0; $i < count($data); ++$i) {
            $stmt->bindValue($params_key[$i],$data[$params_key[$i]]);
        }
        $stmt->execute();

        return new AdjacencyListNode($this->src, $db->lastInsertId(), $data, $this);
    }

    public function remove(): void
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $ids = implode(',', array_map(function($node){
            /** @var Node $node */
            return $node->id();
        }, $this->descendants()));

        $db->prepare("DELETE FROM $tn WHERE $in IN ($ids)")->execute();
    }

    public function load(): void
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $pn, $rni) = $this->db_params();

        $stmt = $db->prepare("SELECT * FROM $tn WHERE $in=:node_id;");
        $stmt->execute([
            'node_id' => $this->id()
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        unset($result[$in]);
        $this->data = $result;
    }

    public function subtree(): void
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $pn, $rni) = $this->db_params();

        //Betöltjük az egész fát
        $stmt = $db->prepare("SELECT * FROM $tn;");
        $stmt->execute();
        /*
         * Preprocessing query result
         */

        $root_node = null;
        /** @var Node $selected_node */
        $selected_node = $this;
        $snid = $this->id();

        $result = array_map(function ($row) use ($src, $in, &$root_node, $selected_node, $snid, $rni) {
            switch ($row[$in]) {
                case $snid:
                    return $selected_node;
                case $rni:
                    $root_node = new AdjacencyListNode($src, $row[$in]);
                    return $root_node;
                default:
                    return new AdjacencyListNode($src, $row[$in]);
            }
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        /*
         * Building tree
         */

        $grouped_result = [];
        foreach ($result as $item) {
            $field_value = $item[$pn];
            if($field_value == null) {
                continue;
            }
            if(isset($result[$field_value])) {
                $grouped_result[$field_value] = [$item];
            }
            else {
                $grouped_result[$field_value][] = $item;
            }
        }

        function buildTree(Node &$root, array &$pc_nodes) {
            /** @var Node $node */
            foreach ($pc_nodes[$root->id()] as $node) {
                $root->addChild($node);
                $node->addParent($root);
                buildTree($node, $pc_nodes);
            }
        }
        buildTree($root_node, $pc_nodes);
    }

    public function find($node_id): ?Node
    {
        function iter_recursively(Node $node, $node_id) {
            $children = $node->children();
            if(count($children) < 1) {
                return null;
            }

            $found = null;
            foreach ($children as $child) {
                if($child->id() == $node_id) {
                    $found = $node;
                    break;
                }
                else {
                    $found = iter_recursively($child);
                    if($found != null) {
                        break;
                    }
                }
            }
            return $found;
        }
        return iter_recursively($this, $node_id);
    }

    public function descendants(int $level = -1): array
    {
        function iter_tree(Node $node, $level = -1, $curr_level = 0) {

            if(!($level == -1 || $curr_level <= $level)) {
                return [];
            }

            $children = $node->children();
            if(count($children) < 1) {
                return [$node];
            }

            $nodes = [];
            foreach ($children as $child) {
                $nodes = array_merge($nodes, iter_tree($child, $level, $curr_level+1));
            }
            return array_merge([$node], $nodes);
        }

        $this->subtree();
        return iter_tree($this, $level);
    }

    public function ancestors(int $level = -1): array
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $pn, $rni) = $this->db_params();

        $result = [];

        $curr_node = $this;
        $curr_level = 0;
        while($curr_node->data()[$pn] != $rni && ($curr_level <= $level || $level == -1)) {
            $curr_node->addParent(new AdjacencyListNode($this->source(), $curr_node->data()[$pn]));
            $curr_node = $curr_node->parent();
            $result[] = $curr_node;
            $curr_level++;
        }
    }

    public function leaves(): array
    {
        function iter_recursively(Node $node) {
            $children = $node->children();
            if(count($children) < 1) {
                return [$node];
            }

            $nodes = [];
            foreach ($children as $child) {
                $nodes = array_merge($nodes, iter_recursively($child));
            }
            return $nodes;
        }
        return iter_recursively($this);
    }

    /**
     * It is a helper method which returns with the source, pdo objects and all necessary naming to manipulate the
     * database.
     *
     * Recommended usage:
     *  list($src, $db, $tn, $in, $pn, $rn) = $this->db_params();
     *
     * @return array
     */
    private function db_params() {
        $src = $this->source();
        return [
            $src,
            $src->getOption(AdjacencyListSource::DB),
            $src->getOption(AdjacencyListSource::TABLE_NAME),
            $src->getOption(AdjacencyListSource::NODE_ID),
            $src->getOption(AdjacencyListSource::PARENT),
            $src->getOption(AdjacencyListSource::ROOT_ID)

        ];
    }
}