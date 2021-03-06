<?php

namespace project\Frontend;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FrontendBundle extends Bundle
{

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }

}
