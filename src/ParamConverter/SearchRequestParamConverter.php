<?php


namespace Search\ParamConverter;


use Search\Request\SearchRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchRequestParamConverter implements ParamConverterInterface
{
    /** @var SearchRequest */
    private $searchRequest;

    public function __construct(SearchRequest $searchRequest)
    {
        $this->searchRequest = $searchRequest;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        return $this->searchRequest;
    }

    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        if ($configuration->getClass() !== SearchRequest::class) {
            return false;
        }

        return true;
    }
}