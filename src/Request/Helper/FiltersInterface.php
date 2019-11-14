<?php


namespace Search\Request\Helper;


interface FiltersInterface
{
    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array;
}