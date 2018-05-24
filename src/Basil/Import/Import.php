<?php

namespace Basil\Import;

use Basil\Tree;

/**
 * Importer Interface
 *
 * The interface has only one method, which is able to convert an arbitrary source to the Tree structure.
 *
 * @package HierarchicalData\Load
 */
abstract class Import
{
    /**
     * Associative array of load options.
     *
     * @var array
     */
    private $options;

    /**
     * Importer constructor.
     * @param array $options
     */
    public final function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Executes the import process on the input source.
     *
     * @param array $data
     * @return Tree
     */
    public abstract function execute($data=null): Tree;

    /**
     * Returns the value which associates with the input key. If the key does not exist, it returns with null.
     *
     * @param $key
     * @return mixed
     */
    protected function getOption($key): mixed {
        return $this->options[$key];
    }
}