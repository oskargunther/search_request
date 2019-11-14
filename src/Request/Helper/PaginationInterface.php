<?php


namespace Search\Request\Helper;


interface PaginationInterface
{
    public function getLimit(): int;

    public function getOffset(): int;
}