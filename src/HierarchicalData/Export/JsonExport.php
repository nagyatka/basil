<?php

namespace Basil\Export;


use Basil\Tree;

class JsonExport extends Export
{
    const CHILDREN = "children";

    public function execute(Tree $tree): mixed
    {
        return (new ArrayExport([]))->$this->execute($tree);
    }

}