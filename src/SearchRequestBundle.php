<?php

namespace Search;

use Search\DependencyInjection\SearchRequestExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SearchRequestBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new SearchRequestExtension();
    }
}
