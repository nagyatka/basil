<?php

namespace Basil\Import;


use Basil\Tree;

class RootNodeImport extends Import
{
    const ROOT_NODE = "root_node";


    public function execute($data = null): Tree
    {
        if(!($root_node = $this->getOption(self::ROOT_NODE))) {
            throw new \InvalidArgumentException("Missing root_node option in RootNodeImport");
        }
        return new Tree($root_node);
    }
}