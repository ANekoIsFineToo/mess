<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /** @var RequestStack|null $request */
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('routeActive', [$this, 'routeActive']),
            new TwigFunction('routeActiveClass', [$this, 'routeActiveClass'])
        ];
    }

    public function routeActive(string $route, bool $checkStart = true): bool
    {
        if ($this->request === null)
        {
            return false;
        }

        $currentRoute = $this->request->attributes->get('_route');

        if ($currentRoute === $route)
        {
            return true;
        }

        if ($checkStart && strpos($currentRoute, $route) === 0)
        {
            return true;
        }

        return false;
    }

    public function routeActiveClass(string $route, bool $checkStart = true, string $activeClass = 'active'): ?string
    {
        return $this->routeActive($route, $checkStart) ? " {$activeClass}" : null;
    }
}
