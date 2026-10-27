<?php

declare(strict_types=1);

namespace Winter\Kernel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Winter\Kernel\MiddlewareInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $request->setSession($this->session);

        $response = $next($request);

        if ($this->session->isStarted()) {
            $this->session->save();
        }

        return $response;
    }
}
