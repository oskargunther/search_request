<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 19.12.2017
 * Time: 10:29
 */

namespace ConsultiaBundle\Repository;


use Doctrine\ORM\QueryBuilder;
use Search\Repository\SearchRepository;
use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\SortInterface;
use Search\Request\SearchRequestInterface;

abstract class SearchStaticRepository extends SearchRepository
{

    private $paramCounter;

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
}