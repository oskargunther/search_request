<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 13.12.2017
 * Time: 14:10
 */

namespace Search\Request;

use Search\Request\Helper\Filter;
use Search\Request\Helper\FilterField;
use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\FiltersInterface;
use Search\Request\Helper\Pagination;
use Search\Request\Helper\PaginationInterface;
use Search\Request\Helper\Sort;
use Search\Request\Helper\Filters;
use Search\Request\Helper\SortInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SearchRequest implements SearchRequestInterface
{
    /** @var Request */
    private $request;

    /** @var Pagination|null */
    private $pagination;

    /** @var Sort */
    private $sort;

    /** @var Filters */
    private $filters;

    /** @var integer */
    private $page;

    /** @var integer */
    private $pageSize;

    /** @var integer */
    private $allPages;

    /** @var boolean */
    private $oneOrNullResult;

    /** @var boolean */
    private $countItems;

    public function __construct(RequestStack $requestStack)
    {
        $this->allPages = false;
        $this->pageSize = 0;
        $this->page = 0;
        $this->sort = null;
        $this->pagination = null;
        $this->filters = null;
        $this->oneOrNullResult = false;
        $this->countItems = true;

        if($requestStack) {
            $this->request = $requestStack->getCurrentRequest();
            $this->handleRequest();
        }
    }

    public function __toString()
    {
        $data = [];

        !$this->filters ?: $data['filters'] = $this->filters->toArray();
        !$this->sort ?: $data['sort'] = $this->sort->toArray();
        !$this->page ?: $data['page'] = $this->page;
        !$this->pageSize ?: $data['pageSize'] = $this->pageSize;
        !$this->oneOrNullResult ?: $data['oneOrNullResult'] = $this->oneOrNullResult;
        !$this->allPages ?: $data['allPages'] = $this->allPages;

        return http_build_query($data);
    }

    private function handleRequest()
    {
        $pageSize = (
            $this->request->query->has('limit') ?
                $this->request->query->get('limit') : $this->request->query->get('pageSize', 20)
        );

        $this->parsePagination(
            $this->request->query->get('allPages', 0),
            $this->request->query->get('page', 1),
            $pageSize
        );
        $this->handleShortFilters();
        $this->parseSort($this->request->query->get('sort', []));
        $this->parseFilters($this->request->query->get('filters', []));
        $this->oneOrNullResult = filter_var($this->request->query->get('oneOrNullResult', false) , FILTER_VALIDATE_BOOLEAN);
    }

    private function handleShortFilters()
    {
        $filters = $this->request->get('filter', []);

        foreach ($filters as $name => $value) {
            $name = str_replace('$', '.', $name);
            $parts = explode(':', $name);
            if(count($parts) === 2) {
                $this->addOperatorFilter($parts[0], $parts[1], $this->parseShortFilterValue($value));
            } elseif($value === 'null') {
                $this->addOperatorFilter(FilterFieldInterface::OPERATOR_IS_NULL, $name, $value);
            } else {
                $this->addOperatorFilter(FilterFieldInterface::OPERATOR_AUTO, $name, $this->parseShortFilterValue($value));
            }
        }
    }

    private function parseShortFilterValue($value)
    {
        switch ($value) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $value;
        }
    }

    public function parsePagination($allPages = false, $page = 1, $pageSize = 20)
    {
        if($allPages) {
            $this->allPages = true;
            return;
        }

        $this->page = (int) $page;
        $this->pageSize = (int) $pageSize;

        $this->pagination = new Pagination([
            'limit' => $pageSize,
            'offset' => ($page * $pageSize) - $pageSize
        ]);

    }

    private function parseSort(array $data)
    {
        if(!empty($data)) {
            $this->sort = new Sort($data);
        }
    }

    private function parseFilters(array $data)
    {
        if(!empty($data)) {
            $this->filters = new Filters($data);
        }
    }

    public function addOperatorFilter(string $operator, string $fieldName, $value = 0)
    {
        $filter = new Filter();
        $filter->setLogic(Filter::LOGIC_AND);

        $field = new FilterField();
        $field->setName($fieldName);
        $field->setOperator($operator);
        $field->setValue($value);

        $filter->addField($field);

        $this->addFilter($filter);
    }

    public function addEqFilter($fieldName, $value)
    {
        $filter = new Filter();
        $filter->setLogic(Filter::LOGIC_AND);

        $field = new FilterField();
        $field->setName($fieldName);
        $field->setOperator(FilterFieldInterface::OPERATOR_EQ);
        $field->setValue($value);

        $filter->addField($field);

        $this->addFilter($filter);
    }

    public function addInFilter($fieldName, array $value)
    {
        $filter = new Filter();
        $filter->setLogic(Filter::LOGIC_AND);

        $field = new FilterField();
        $field->setName($fieldName);
        $field->setOperator(FilterFieldInterface::OPERATOR_IN);
        $field->setValue($value);

        $filter->addField($field);

        $this->addFilter($filter);
    }

    public function addFilter(Filter $filter)
    {
        if(!$this->filters) {
            $this->filters = new Filters([]);
        }

        $this->filters->addFilter($filter);
    }

    public function findOne()
    {
        $this->pagination = new Pagination([
            'limit' => 1,
            'offset' => 0,
        ]);
        $this->setOneOrNullResult(true);
    }

    public function getPagination(): ?PaginationInterface
    {
        return $this->pagination;
    }

    public function getFilters(): ?FiltersInterface
    {
        return $this->filters;
    }

    public function getSort(): ?SortInterface
    {
        return $this->sort;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getOneOrNullResult(): bool
    {
        return $this->oneOrNullResult;
    }

    public function countItems(): bool
    {
        return $this->countItems;
    }

    public function setCountItems(bool $countItems)
    {
        $this->countItems = $countItems;
    }

    public function setOneOrNullResult(bool $oneOrNullResult)
    {
        $this->oneOrNullResult = $oneOrNullResult;
    }

    public function getAllPages()
    {
        return $this->allPages;
    }

    public function disablePagination()
    {
        $this->allPages = true;
        $this->pagination = null;
    }

    public function isSearchingByRelation()
    {
        if(!$this->filters) {
            return false;
        }

        /** @var Filter $filter */
        foreach ($this->filters->getFilters() as $filter) {
            /** @var FilterField $field */
            foreach ($filter->getFields() as $field) {
                if(strpos($field->getName(), '.') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function sort(string $field, string $direction)
    {
        $this->sort = new Sort([
            'field' => $field,
            'direction' => $direction,
        ]);
    }

}