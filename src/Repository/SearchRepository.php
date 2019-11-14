<?php


namespace Search\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Search\Request\Helper\FilterFieldInterface;
use Search\Request\Helper\FilterInterface;
use Search\Request\Helper\FiltersInterface;
use Search\Request\Helper\PaginationInterface;

class SearchRepository extends EntityRepository
{
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