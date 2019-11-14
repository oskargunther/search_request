<?php


namespace Search\Request\Helper;


interface FilterInterface
{
    const LOGIC_AND = 'and';
    const LOGIC_OR = 'or';

    public function getLogic(): string;

    /**
     * @return FilterFieldInterface[]
     */
    public function getFields(): array;
}