<?php


namespace Search\Request\Helper;


interface SortInterface
{
    public function getField(): string;

    public function getDirection(): string;
}