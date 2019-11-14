<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 14:10
 */

namespace Search\Request\Helper;

class Filters implements HelperInterface
{
    /**
     * @var Filter[]
     */
    private $filters;

    public function toArray()
    {
        return array_map(function(Filter $filter) {
            return $filter->toArray();
        }, $this->filters);
    }

    public function __construct(array $data)
    {
        $this->filters = array_map(function(array $filter) {
            return new Filter($filter);
        }, $data);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

}