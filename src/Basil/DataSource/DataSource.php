<?php

namespace Basil\DataSource;


use Basil\Node\Node;

abstract class DataSource
{
    const DB        = "pdo";
    const TABLE_NAME= "table";
    const NODE_ID   = "node_id";
    const ROOT_ID   = "root_id";


    /**
     * Associative array of load options.
     *
     * @var array
     */
    private $options;

    /**
     * @var mixed
     */
    private $data;

    /**
     * Importer constructor.
     * @param array $options
     * @param null $data
     */
    public final function __construct(array $options = [], $data = null)
    {
        $this->options = $options;
        $this->data = $data;
    }

    /**
     * Returns the value which associates with the input key. If the key does not exist, it returns with null.
     *
     * @param $key
     * @return mixed
     */
    public function getOption($key) {
        return $this->options[$key];
    }

    public function data() {
        return $this->data;
    }

    public abstract function convert(Node $root_node): ?mixed;

}