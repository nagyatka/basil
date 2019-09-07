<?php

namespace Basil\Node;


use Basil\DataSource\DataSource;
use Basil\DataSource\NestedSetSource;

class NestedSetNode extends Node
{
    /**
     * @var int
     */
    private $lft;

    /**
     * @var int
     */
    private $rgt;

    public function __construct($source, $id, int $lft = null, int $rgt = null, array $data = null, $parent = -1, $children = null)
    {
        $this->lft = $lft;
        $this->rgt = $rgt;
        parent::__construct($source, $id, $data, $parent, $children);
    }

    public function lft($lft = null) {

        if($lft != null) {
            $this->lft = $lft;
            return $this->lft;
        }

        if($this->lft == null) {
            $this->load();
        }
        return $this->lft;
    }

    public function rgt($rgt = null) {

        if($rgt != null) {
            $this->rgt = $rgt;
            return $this->rgt;
        }

        if($this->rgt == null) {
            $this->load();
        }
        return $this->rgt;
    }

    public function add(array $data): Node
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $children = $this->children();
        $len = count($children);
        if($len > 0) {
            /** @var NestedSetNode $last_node */
            $last_node = $children[$len-1];
            $val = $last_node->rgt();

            // Updating left values
            $db->prepare("UPDATE $tn SET $ln = $ln + 2 WHERE $ln > :rgt_value;")
                ->execute(['rgt_value' => $val]);
            // Updating right values
            $db->prepare("UPDATE $tn SET $rn = $rn + 2 WHERE $rn > :rgt_value;")
                ->execute(['rgt_value' => $val]);
        }
        else {
            $val = $this->lft();

            // Updating left values
            $db->prepare("UPDATE $tn SET $ln = $ln + 2 WHERE $ln > :lft_value;")
                ->execute(['lft_value' => $val]);
            // Updating right values
            $db->prepare("UPDATE $tn SET $rn = $rn + 2 WHERE $rn > :lft_value;")
                ->execute(['lft_value' => $val]);
        }

        $data[$ln] = $val+1;
        $data[$rn] = $val+2;
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

        unset($data[$ln]);
        unset($data[$rn]);
        unset($data[$in]);

        return new NestedSetNode($src, $db->lastInsertId(), $val+1, $val+2, $data, $this);
    }

    public function remove(): void
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $lft = $this->lft();
        $rgt = $this->rgt();
        $width = $rgt - $lft + 1;
        $db->prepare("DELETE FROM $tn WHERE $ln BETWEEN :lft_value AND :rgt_value")
            ->execute([
                'lft_value' => $lft,
                'rgt_value' => $rgt
            ]);

        $db->prepare("UPDATE $tn SET $rn = $rn - :width WHERE $rn > :rgt_value;")
            ->execute([
                'width' => $width,
                'rgt_value' => $rgt
            ]);

        $db->prepare("UPDATE $tn SET $ln = $ln - :width WHERE $ln > :rgt_value;")
            ->execute([
                'width' => $width,
                'rgt_value' => $rgt
            ]);

        $this->id = null;
        $this->lft = null;
        $this->rgt = null;
        $this->data = null;
        $this->parent = null;
        $this->children = null;
    }

    public function load(): void
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $stmt = $db->prepare("
                SELECT node.*
                FROM $tn AS node
                WHERE node.$in = :node_id");
        $stmt->execute([":node_id" => $this->id()]);

        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->lft = $res[$ln];
        unset($res[$ln]);
        $this->rgt = $res[$rn];
        unset($res[$rn]);

        $this->data = $res;
    }

    public function subtree(): void
    {
        NestedSetNode::buildTree($this, $this->descendants());
    }

    public function find($node_id): ?Node
    {
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $stmt = $db->prepare("
                SELECT node.*
                FROM $tn AS node, $tn AS parent
                WHERE node.$ln > parent.$ln AND node.$rn < parent.$rn AND parent.$in = :parent_id AND node.$in = :node_id
                ORDER BY node.$ln;");
        $stmt->execute([":parent_id" => $this->id(), ":node_id" => $node_id]);

        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if($res == false) {
            return null;
        }

        $lft = $res[$ln];
        unset($res[$ln]);
        $rgt = $res[$rn];
        unset($res[$rn]);

        return new NestedSetNode($this->source(), $node_id, $lft, $rgt, $res);

    }

    public function descendants(int $level = -1): array {

        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        //Retrieves full tree
        if($level == -1) {
            $stmt = $db->prepare("
                SELECT node.*
                FROM $tn AS node, $tn AS parent
                WHERE node.$ln > parent.$ln AND node.$rn < parent.$rn AND parent.$in = :node_id
                ORDER BY node.$ln;");
            $stmt->execute([":node_id" => $this->id()]);
        }
        else {
            $stmt = $db->prepare("
                SELECT node.*, (COUNT(parent.$in) - (sub_tree.depth + 1)) AS depth
                FROM $tn AS node, $tn AS parent, $tn AS sub_parent,
                (
                        SELECT node.$in, (COUNT(parent.$in) - 1) AS depth
                        FROM $tn AS node, $tn AS parent
                        WHERE node.$ln > parent.$ln AND node.$rn < parent.$rn
                                AND node.$in = :node_id
                        GROUP BY node.$in
                        ORDER BY node.$ln
                )AS sub_tree
                WHERE node.$ln BETWEEN parent.$ln AND parent.$rn
                        AND node.$ln > sub_parent.$ln AND node.$rn < sub_parent.$rn
                        AND sub_parent.$in = sub_tree.$in
                GROUP BY node.$in
                HAVING depth <= $level
                ORDER BY node.$ln;");
            $stmt->execute([":node_id" => $this->id()]);
        }

        /*
         * Process the result
         */
        return array_map(function ($row) use ($src, $in, $ln, $rn) {

            // row's id value
            $riv = $row[$in];
            unset($row[$in]);
            // row's left value
            $rlv = $row[$ln];
            unset($row[$ln]);
            // row's right value
            $rrv = $row[$rn];
            unset($row[$rn]);

            unset($row["depth"]);

            return new NestedSetNode($src, $riv, $rlv, $rrv, $row);
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function ancestors(int $level = -1): array
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $stmt = $db->prepare("
                SELECT node.*
                FROM $tn AS node, $tn AS parent
                WHERE node.$ln < parent.$ln AND node.$rn > parent.$rn AND parent.$in = :node_id
                ORDER BY node.$ln DESC;");
        $stmt->execute([":node_id" => $this->id()]);

        $res = array_map(function ($row) use ($src, $in, $ln, $rn) {
            // row's id value
            $riv = $row[$in];
            unset($row[$in]);
            // row's left value
            $rlv = $row[$ln];
            unset($row[$ln]);
            // row's right value
            $rrv = $row[$rn];
            unset($row[$rn]);

            return new NestedSetNode($src, $riv, $rlv, $rrv, $row);
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        if($level == -1) {
            return $res;
        }
        else {
            return array_slice($res, 0, $level);
        }
    }

    public function leaves(): array
    {
        /** @var DataSource $src */
        /** @var \PDO $db */
        list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();

        $stmt = $db->prepare( "
              SELECT node.*
              FROM $tn AS node, $tn AS parent
              WHERE node.$rn = node.$ln + 1 AND node.$ln BETWEEN parent.$ln AND parent.$rn AND parent.$in = :node_id
              ORDER BY node.$ln;");
        $stmt->execute([":node_id" => $this->id()]);

        return array_map(function ($row) use ($src, $in, $ln, $rn) {
            // row's id value
            $riv = $row[$in];
            unset($row[$in]);
            // row's left value
            $rlv = $row[$ln];
            unset($row[$ln]);
            // row's right value
            $rrv = $row[$rn];
            unset($row[$rn]);

            return new NestedSetNode($src, $riv, $rlv, $rrv, $row);
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * It is a helper method which returns with the source, pdo objects and all necessary naming to manipulate the
     * database.
     *
     * Recommended usage:
     *  list($src, $db, $tn, $in, $ln, $rn) = $this->db_params();
     *
     * @return array
     */
    private function db_params() {
        $src = $this->source();
        return [
            $src,
            $src->getOption(NestedSetSource::DB),
            $src->getOption(NestedSetSource::TABLE_NAME),
            $src->getOption(NestedSetSource::NODE_ID),
            $src->getOption(NestedSetSource::LEFT),
            $src->getOption(NestedSetSource::RIGHT)
        ];
    }

    /**
     * @param NestedSetNode $root_node
     * @param NestedSetNode[] $result
     * @return bool
     */
    private static function buildTree(NestedSetNode &$root_node, $result) {
        if(count($result) < 1) {
            return true;
        }

        if($root_node->rgt() > $result[0]->rgt()) {
            return false;
        }
        else {
            $next_node = array_shift($result);
            $root_node->addChild($next_node);
            if(count($result) < 1) {
                return true;
            }

            while(self::buildTree($next_node, $result) == false) {
                if($root_node->rgt() > $result[0]->rgt() ) {
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
}