<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 14:10
 */

namespace Search\Request\Helper;

class Pagination implements HelperInterface, PaginationInterface
{
    /**
     * @var integer
     */
    private $limit;
    /**
     * @var integer
     */
    private $offset;

    public function __construct(array $data)
    {
        $this->limit = (int) $data['limit'];
        $this->offset = (int) $data['offset'];
    }

    public function toArray()
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }


}