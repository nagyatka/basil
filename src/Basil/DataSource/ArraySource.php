<?php


namespace Basil\DataSource;


use Basil\Node\Node;

class ArraySource extends DataSource
{
    const CHILDREN = "children";

    /**
     * @param Node $root_node
     * @return mixed|null
     * @throws \Exception
     */
    public function convert(Node $root_node): ?mixed
    {
        throw new \Exception("Not yet implemented");
    }

    public function arr_params() {
        return [
            $this->getOption(ArraySource::NODE_ID),
            $this->getOption(ArraySource::CHILDREN),
        ];
    }
}