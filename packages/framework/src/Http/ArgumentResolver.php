<?php

declare(strict_types=1);

namespace Winter\Http;

use Winter\Http\Argument\ArgumentResolverInterface;

final class ArgumentResolver
{
    /**
     * @param list<ArgumentResolverInterface> $resolvers
     */
    public function __construct(private readonly array $resolvers)
    {
    }

    /**
     * @return list<mixed>
     */
    public function resolve(\ReflectionMethod $method, RequestContext $context): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $resolved = false;

            foreach ($this->resolvers as $resolver) {
                if (!$resolver->supports($parameter, $context)) {
                    continue;
                }

                $arguments[] = $resolver->resolve($parameter, $context);
                $resolved = true;

                break;
            }

            if (!$resolved) {
                throw new \LogicException(sprintf('No resolver supports parameter $%s.', $parameter->getName()));
            }
        }

        return $arguments;
    }
}
