<?php


namespace Search\Request;


use Search\Request\Helper\FiltersInterface;
use Search\Request\Helper\PaginationInterface;
use Search\Request\Helper\SortInterface;

interface SearchRequestInterface
{
    public function getFilters(): ?FiltersInterface;

    public function getPagination(): ?PaginationInterface;

    public function getSort(): ?SortInterface;

    public function getPage(): int;

    public function getPageSize(): int;

    public function getOneOrNullResult(): bool;

    public function countItems(): bool;
}