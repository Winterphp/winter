<?php

declare(strict_types=1);

namespace App\Controller;

use App\Documentation\DocsRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Winter\Attribute\Controller;
use Winter\Attribute\GetMapping;
use Winter\Attribute\PathVariable;
use Winter\Attribute\Route;
use Winter\Attribute\Template;
use Winter\Http\HttpException\NotFoundHttpException;
use Winter\Http\RouteGenerator;

#[Controller]
#[Route('/docs')]
final class DocsController
{
    public function __construct(
        private readonly DocsRepository $docs,
        private readonly RouteGenerator $routes,
    ) {
    }

    #[GetMapping('')]
    public function index(): Response
    {
        return new RedirectResponse($this->routes->path('docs.show', ['slug' => 'introduction']));
    }

    #[GetMapping('/{slug}')]
    #[Template('docs/show.html.twig')]
    public function show(#[PathVariable] string $slug): array
    {
        $page = $this->docs->page($slug);

        if ($page === null) {
            throw new NotFoundHttpException(sprintf('No documentation page "%s".', $slug));
        }

        return [
            'page' => $page,
            'pages' => $this->docs->pages(),
            'previous' => $this->docs->sibling($slug, -1),
            'next' => $this->docs->sibling($slug, 1),
        ];
    }
}
