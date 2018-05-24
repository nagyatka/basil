<?php

namespace Basil\Import;


use Basil\Tree;

/**
 * Class JsonImport
 *
 * Imports a json string to Tree structure. The accepted and used json structure is the following:
 *
 * {
 *      "val1": "value1",
 *      "val2": 3,
 *      ...
 *      "children": [
 *          "val1": "foo",
 *          "val2": 55,
 *          ...
 *          "children": [
 *              ...
 *          ]
 *      ]
 * }
 *
 * The mandatory is the "children" field, which is used to identify the child nodes of the parent node.
 *
 * The implementation uses the standard json_decode method. The parameters of the method can be passed through the
 * constructor of JsonImport but they are not obligatory:
 *
 * $tree = (new JsonImport([]))->execute($json_string);
 *
 * // or
 *
 * $tree = (new JsonImport([JsonImport::JSON_ASSOC => true, JsonImport::JSON_DEPTH => 256]))->execute($json_string);
 *
 *
 * @package HierarchicalData\Import
 */
class JsonImport extends Import
{
    const JSON_ASSOC    = "assoc";
    const JSON_DEPTH    = "depth";
    const JSON_OPTIONS  = "options";

    const DEF_DEPTH     = 512;
    const DEF_ASSOC     = false;
    const DEF_OPTIONS   = 0;

    public function execute($data = null): Tree
    {
        $data_arr = json_decode($data,
            $this->getOption(self::JSON_ASSOC) ?? self::DEF_ASSOC,
            $this->getOption(self::JSON_DEPTH) ?? self::DEF_DEPTH,
            $this->getOption(self::JSON_OPTIONS) ?? self::DEF_OPTIONS
        );
        return (new ArrayImport())->execute($data_arr);
    }
}