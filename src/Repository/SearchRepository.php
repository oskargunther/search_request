<?php
/**
 * Created by PhpStorm.
 * User: oskargunther
 * Date: 19.12.2017
 * Time: 10:29
 */

namespace Search\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\QueryException;
use Search\Exception\BadRequestException;
use Search\Exception\InvalidFilterException;
use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\FilterInterface;
use Search\Request\Helper\FiltersInterface;
use Search\Request\Helper\PaginationInterface;
use Search\Request\Helper\SortInterface;
use Search\Request\SearchRequestInterface;
use Doctrine\ORM\QueryBuilder;
use \Exception;
use Search\Response\SearchResponse;

abstract class SearchRepository extends EntityRepository
{
    /** @var string[] */
    private $joins;

    /** @return string[] */
    abstract public function getRelations(): array;

    /** @return string */
    abstract public function getMainAlias(): string;

    /** @return string[] */
    abstract public function getSelects(): array;

    /** @return string[] */
    abstract public function getFieldNameAliases(): array;

    /** @return string[] */
    abstract public function getForbiddenFields(): array;

    public function getCountSelect()
    {
        return 'count('.$this->getMainAlias().')';
    }

    /**
     * @param SearchRequestInterface $request
     * @return mixed|SearchResponse
     */
    public function findByRequest(SearchRequestInterface $request)
    {
        try {
            $this->joins = [];
            $qb = $this->createQueryBuilder($this->getMainAlias());

            $this->filter($qb, $request->getFilters());

            if($request->getOneOrNullResult()) {
                return $qb->getQuery()->getOneOrNullResult();
            }

            $count = $this->countItems($request, $qb);

            $qb->select($this->getSelects());
            $this->joinForSelect($qb);

            $this->paginate($qb, $request->getPagination());
            $this->sort($qb, $request->getSort());

            return new SearchResponse($request, $qb->getQuery()->getResult(), $count);
        } catch(QueryException $e) {
            throw new BadRequestException('Search query failed to process: ' . $e->getMessage());
        }
    }

    private function countItems(SearchRequestInterface $searchRequest, QueryBuilder $qb): ?int
    {
        if($searchRequest->countItems()) {
            if($searchRequest->isSearchingByRelation()) {
                $countQb = $this->createQueryBuilder($this->getMainAlias());
            } else {
                $countQb = $qb;
            }

            return (int) $countQb->select($this->getCountSelect())->getQuery()->getSingleScalarResult();
        } else {
            return null;
        }
    }

    private function joinForSelect(QueryBuilder $qb)
    {
        foreach ($this->getSelects() as $select) {
            if(
                $select !== $this->getMainAlias() and
                !in_array($select, $this->joins)
            ) {
                $qb->leftJoin($this->getRelations()[$select] . '.' . $select, $select);
                $this->joins[] = $select;
            }
        }
    }

    protected function createExpression(QueryBuilder $qb, FilterFieldInterface $field)
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

        if(strpos($fieldName, '.') !== false) {
            $relName = explode('.', $fieldName)[0];
            if($relName !== $this->getMainAlias() and !in_array($relName, $this->joins)) {
                if(key_exists($relName, $this->getRelations())) {
                    $qb->leftJoin($this->getRelations()[$relName] . '.' . $relName, $relName);
                    $this->joins[] = $relName;
                } else {
                    throw new Exception('Cannot join table: ' . $relName);
                }
            }

        }

        return $this->filterField($qb, $field, $paramName, $fieldName);
    }

    protected function sort(QueryBuilder $qb, SortInterface $sort = null)
    {
        if($sort) {

            if(!strpos($this->getFieldName($sort->getField()), '.') !== false) {
                $qb->addOrderBy($this->getMainAlias().'.'.$this->getFieldName($sort->getField()), $sort->getDirection());
            } else {
                $fieldName = $this->getFieldName($sort->getField());

                $qb->addOrderBy($fieldName, $sort->getDirection());
                $this->joinForSelect($qb);
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
            if(in_array($field->getName(), $this->getForbiddenFields())) {
                throw new BadRequestException('Forbidden filter used');
            }
            $expressions[] = $this->createExpression($qb, $field);
        }

        return $expressions;
    }

    protected function getFieldName($name)
    {
        if(key_exists($name, $this->getFieldNameAliases())) {
            return $this->getFieldNameAliases()[$name];
        }

        return $name;
    }

    public function filterField(QueryBuilder $qb, FilterFieldInterface $field, string $paramName, string $fieldName)
    {
        switch ($field->getOperator()) {
            case FilterFieldInterface::OPERATOR_CONTAINS:
                $qb->setParameter($paramName, '%'.$field->getValue().'%');
                return $qb->expr()->like($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_CONTAINS_FROM_LEFT:
                $qb->setParameter($paramName, $field->getValue().'%');
                return $qb->expr()->like($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_EQ:
                return $qb->expr()->eq($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_NEQ:
                return $qb->expr()->neq($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_IS_EMPTY:
                $qb->setParameter('emptyParam', '');
                return $qb->expr()->orX(
                    $qb->expr()->isNull($fieldName),
                    $qb->expr()->eq($fieldName, ':emptyParam')
                );
            case FilterFieldInterface::OPERATOR_IS_NOT_EMPTY:
                $qb->setParameter('emptyParam', '');
                return $qb->expr()->not($qb->expr()->orX(
                    $qb->expr()->isNull($fieldName),
                    $qb->expr()->eq($fieldName, ':emptyParam')
                ));
            case FilterFieldInterface::OPERATOR_IS_NULL:
                return $qb->expr()->isNull($fieldName);
            case FilterFieldInterface::OPERATOR_IS_NOT_NULL:
                return $qb->expr()->isNotNull($fieldName);
            case FilterFieldInterface::OPERATOR_LT:
                return $qb->expr()->lt($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_LTE:
                return $qb->expr()->lte($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_GT:
                return $qb->expr()->gt($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_GTE:
                return $qb->expr()->gte($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_IN:
                return $qb->expr()->in($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_NOT_IN:
                return $qb->expr()->notIn($fieldName, ':'.$paramName);
            case FilterFieldInterface::OPERATOR_AUTO:
                return $this->createAutoFilter($qb, $field, $fieldName, $paramName);
            default:
                throw new InvalidFilterException('Unknown filter name: ' . $field->getOperator());
        }
    }

    private function createAutoFilter(QueryBuilder $qb, FilterFieldInterface $field, string $fieldName, string $paramName): string
    {
        if(is_string($field->getValue()) and strpos($field->getValue(), ',') !== false) {

            $qb->setParameter($paramName, explode(',', $field->getValue()));
            return $qb->expr()->in($fieldName, ':'.$paramName);

        } else {

            if($fieldName === 'id' or preg_match('/(\.id)$/i', $fieldName)) {
                return $qb->expr()->eq($fieldName, ':'.$paramName);
            } else {
                $qb->setParameter($paramName, '%'.$field->getValue().'%');
                return $qb->expr()->like($fieldName, ':'.$paramName);
            }

        }
    }
}