<?php

namespace Basil\Import;


use Basil\Tree;

/**
 * Class RootNodeImport
 *
 * Imports a Node instance to Tree object. It is useful when an existing Node instance have to be exported to other
 * format.
 *
 * @package Basil\Import
 */
class RootNodeImport extends Import
{
    public function execute($data = null): Tree
    {
        return new Tree($data);
    }
}