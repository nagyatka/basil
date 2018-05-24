<?php

namespace Basil\Export;


use Basil\Tree;

class RootNodeExport extends Export
{

    public function execute(Tree $tree): mixed
    {
        return $tree->getRootNode();
    }
}