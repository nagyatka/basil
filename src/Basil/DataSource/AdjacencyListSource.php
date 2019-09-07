<?php

namespace Basil\DataSource;


use Basil\Node\Node;

class AdjacencyListSource extends DataSource
{
    const PARENT = "parent";

    public function convert(Node $root_node): ?mixed
    {

        $data = [];
        $this->iter_rows($root_node, $data);

        /** @var \PDO $db */
        list($db, $tn, $in, $pn, $rni) = $this->db_params();

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

    private function iter_rows(Node $node, &$data) {
        $n_data = $node->data();
        $n_data[$this->getOption(AdjacencyListSource::NODE_ID)] = $node->id();
        $n_data[$this->getOption(AdjacencyListSource::PARENT)] = $node->parent() != null ? $node->parent()->id() : null;
        $data[] = $n_data;

        $children = $node->children();
        foreach ($children as $child) {
            $this->iter_rows($child, $data);
        }
    }

    private function db_params() {
        return [
            $this->getOption(AdjacencyListSource::DB),
            $this->getOption(AdjacencyListSource::TABLE_NAME),
            $this->getOption(AdjacencyListSource::NODE_ID),
            $this->getOption(AdjacencyListSource::PARENT),
            $this->getOption(AdjacencyListSource::ROOT_ID)
        ];
    }
}