<?php

namespace Basil\DataSource;


use Basil\Node\Node;

class NestedSetSource extends DataSource
{
    const LEFT      = "lft";
    const RIGHT     = "rgt";

    public function convert(Node $root_node): ?mixed
    {
        $data = [];
        $this->iter_rows($root_node, 1, $data);
        $data = array_reverse($data);

        /** @var \PDO $db */
        list($db, $tn, $in, $ln, $rn) = $this->db_params();

        $params_key = array_keys($data[0]);

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

        foreach ($data as $row) {
            $stmt->execute($row);
        }
        return null;
    }

    private function iter_rows(Node $node, $lft, &$data) {
        $children = $node->children();

        if(count($children) < 1) {
            $data[] = array_merge([
                $this->getOption(NestedSetSource::LEFT) => $lft,
                $this->getOption(NestedSetSource::RIGHT) => $lft + 1,
            ], $node->data());

            return $lft + 1;
        }

        $last_rgt = $lft;
        foreach ($children as $child) {
            $last_rgt = $this->iter_rows($child, $last_rgt + 1, $data);
        }

        $data[] = array_merge([
            $this->getOption(NestedSetSource::LEFT) => $lft,
            $this->getOption(NestedSetSource::RIGHT) => $last_rgt + 1,
        ], $node->data());

        return $last_rgt + 1;
    }

    private function db_params() {
        return [
            $this->getOption(NestedSetSource::DB),
            $this->getOption(NestedSetSource::TABLE_NAME),
            $this->getOption(NestedSetSource::NODE_ID),
            $this->getOption(NestedSetSource::LEFT),
            $this->getOption(NestedSetSource::RIGHT)
        ];
    }

}