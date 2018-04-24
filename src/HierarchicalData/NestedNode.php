<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 04. 24.
 * Time: 11:16
 */

namespace HierarchicalData;


use Tree\Node\Node;

class NestedNode extends Node
{
    /**
     * @var int
     */
    private $lft;

    /**
     * @var int
     */
    private $rgt;

    public function __construct(int $lft, int $rgt, $value = null, $children = [])
    {
        parent::__construct($value, $children);
        $this->lft = $lft;
        $this->rgt = $rgt;
    }

    /**
     * @return int
     */
    public function getLft(): int
    {
        return $this->lft;
    }

    /**
     * @return int
     */
    public function getRgt(): int
    {
        return $this->rgt;
    }



}