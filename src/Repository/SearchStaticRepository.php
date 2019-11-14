<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 19.12.2017
 * Time: 10:29
 */

namespace Search\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\FilterInterface;
use Search\Request\Helper\FiltersInterface;
use Search\Request\Helper\PaginationInterface;
use Search\Request\Helper\SortInterface;
use Search\Request\SearchRequestInterface;

abstract class SearchStaticRepository extends EntityRepository
{

    /** @return QueryBuilder */
    abstract public function getQueryBuilder();

    /** @return string */
    abstract public function getMainAlias();

    abstract public function addSelects(QueryBuilder $qb);

    protected function getFieldNamesTranslations()
    {
        return [];
    }

    public function getCountSelect()
    {
        return 'count('.$this->getMainAlias().')';
    }

    public function findByRequest(
        SearchRequestInterface $request,
        $oneOrNullResult = false,
        QueryBuilder $qb = null,
        Callable $addSelects = null,
        $rawReturn = false,
        $count = true
    )
    {
        if(!$qb) {
            $qb = $this->getQueryBuilder();
        }

        $this->filter($qb, $request->getFilters());

        if(!$rawReturn and $count) {
            if($request->isSearchingByRelation()) {
                $countQb = $this->createQueryBuilder($this->getMainAlias());
            } else {
                $countQb = $qb;
            }

            $count = $countQb->select($this->getCountSelect())->getQuery()->getSingleScalarResult();
        } else {
            $count = null;
        }

        if($addSelects) {
            $addSelects($qb);
        } else {
            $this->addSelects($qb);
        }

        $this->paginate($qb, $request->getPagination());
        $this->sort($qb, $request->getSort());

        if($oneOrNullResult) {
            return $qb->getQuery()->getOneOrNullResult();
        }
        $items = $qb->getQuery()->getResult();

        if($rawReturn) {
            return $items;
        }

        return [
            'items' => $items,
            'total' => intval($count),
            'pagination' => [
                'currentPage' => (int) $request->getPage(),
                'pageSize' => (int) $request->getPageSize()
            ]
        ];
    }

    private function createExpression(QueryBuilder $qb, FilterFieldInterface $field)
    {
        if(!strpos($this->getFieldName($field->getName()), '.') !== false) {
            $fieldName = $this->getMainAlias().'.'.$this->getFieldName($field->getName());
        } else {
            $fieldName = $this->getFieldName($field->getName());
        }

        $paramName = str_replace('.','',$field->getName()).$this->paramCounter;

        if(!in_array($field->getOperator(), ['isEmpty', 'isNotEmpty','isNull','isNotNull'], true)) {
            $this->paramCounter++;
            $qb->setParameter($paramName, $field->getValue());
        }

        return $this->filterField($qb, $field, $paramName, $fieldName);
    }

    private function sort(QueryBuilder $qb, SortInterface $sort = null)
    {
        if($sort) {
            if(!strpos($this->getFieldName($sort->getField()), '.') !== false) {
                $qb->addOrderBy($this->getMainAlias().'.'.$this->getFieldName($sort->getField()), $sort->getDirection());
            } else {
                $fieldName = $this->getFieldName($sort->getField());

                $qb->addOrderBy($fieldName, $sort->getDirection());
            }
        }
    }

    protected $paramCounter;

    protected function paginate(QueryBuilder $qb, PaginationInterface $pagination = null)
    {
        if($pagination) {
            $qb
                ->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }
    }

    protected function filter(QueryBuilder $qb, FiltersInterface $filters = null)
    {
        $this->paramCounter = 0;

        if(!$filters) {
            return null;
        }

        /** @var FilterInterface $filter */
        foreach ($filters->getFilters() as $filter) {
            if(strtolower($filter->getLogic()) === 'and') {
                $qb->andWhere($qb->expr()->andX(...$this->getFilters($qb, $filter)));
            } else if (strtolower($filter->getLogic()) === 'or') {
                $qb->andWhere($qb->expr()->orX(...$this->getFilters($qb, $filter)));
            }
        }
    }

    protected function getFilters(QueryBuilder $qb, FilterInterface $filter)
    {
        $expressions = [];

        /** @var FilterFieldInterface $field */
        foreach ($filter->getFields() as $field) {
            $expressions[] = $this->createExpression($qb, $field);
        }

        return $expressions;
    }

    protected function getFieldName($name)
    {
        if(key_exists($name, $this->getFieldNamesTranslations())) {
            return $this->getFieldNamesTranslations()[$name];
        }

        return $name;
    }

    public function filterField(QueryBuilder $qb, FilterFieldInterface $field, string $paramName, string $fieldName)
    {
        switch ($field->getOperator()) {
            case 'contains':
                $qb->setParameter($paramName, '%'.$field->getValue().'%');
                return $qb->expr()->like($fieldName, ':'.$paramName);
            case 'containsFromLeft':
                $qb->setParameter($paramName, $field->getValue().'%');
                return $qb->expr()->like($fieldName, ':'.$paramName);
            case 'eq':
                return $qb->expr()->eq($fieldName, ':'.$paramName);
            case 'neq':
                return $qb->expr()->neq($fieldName, ':'.$paramName);
            case 'isEmpty':
                $qb->setParameter('emptyParam', '');
                return $qb->expr()->orX(
                    $qb->expr()->isNull($fieldName),
                    $qb->expr()->eq($fieldName, ':emptyParam')
                );
            case 'isNotEmpty':
                $qb->setParameter('emptyParam', '');
                return $qb->expr()->not($qb->expr()->orX(
                    $qb->expr()->isNull($fieldName),
                    $qb->expr()->eq($fieldName, ':emptyParam')
                ));
            case 'isNull':
                return $qb->expr()->isNull($fieldName);
            case 'isNotNull':
                return $qb->expr()->isNotNull($fieldName);
            case 'lt':
                return $qb->expr()->lt($fieldName, ':'.$paramName);
            case 'lte':
                return $qb->expr()->lte($fieldName, ':'.$paramName);
            case 'gt':
                return $qb->expr()->gt($fieldName, ':'.$paramName);
            case 'gte':
                return $qb->expr()->gte($fieldName, ':'.$paramName);
            case 'in':
                return $qb->expr()->in($fieldName, ':'.$paramName);
            case 'notIn':
                return $qb->expr()->notIn($fieldName, ':'.$paramName);
            default:
                return null;
                break;
        }
    }
}