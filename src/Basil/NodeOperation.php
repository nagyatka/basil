<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 05. 24.
 * Time: 10:18
 */

namespace Basil;


use Tree\Node\Node;

class NodeOperation
{

    /**
     * Sets the desired value in the input key.
     *
     * @param Node $node
     * @param $key
     * @param $value
     */
    public static function setNodeValue(Node &$node, $key, $value): void {
        $values = $node->getValue();
        $values[$key] = $value;
        $node->setValue($values);
    }

    /**
     * Returns with the stored value which belongs to the specified key.
     *
     * @param Node $node
     * @param $key
     * @return mixed
     */
    public static function getNodeValue(Node $node, string $key): mixed {
        return $node->getValue()[$key];
    }

    /**
     * Converts the array of Nodes to an associative array of Nodes in which the nodes are grouped by the field_name.
     * The keys of the associative array will be the distinct values of the field_name.
     *
     * @param Node[] $array
     * @param string $field_name
     * @return array
     */
    public static function groupByField(array $array, string $field_name) {
        $result = [];

        foreach ($array as $item) {
            $field_value = self::getNodeValue($item, $field_name);

            if($field_value == null) {
                continue;
            }

            if(isset($result[$field_value])) {
                $result[$field_value] = [$item];
            }
            else {
                $result[$field_value][] = $item;
            }
        }

        return $result;
    }
}