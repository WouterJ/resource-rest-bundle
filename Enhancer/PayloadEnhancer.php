<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\ResourceRestBundle\Enhancer;

use JMS\Serializer\Context;
use Puli\Repository\Api\Resource\Resource;

/**
 * Serialize the payload
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PayloadEnhancer implements EnhancerInterface
{
    /**
     * {@inheritDoc}
     */
    public function enhance(Context $context, Resource $resource)
    {
        $visitor = $context->getVisitor();
        $visitor->addData('payload', $context->accept($resource->getPayload()));
    }
}