<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /** @var RequestStack|null $request */
    private $request;

    /** @var Packages $packages */
    private $packages;

    public function __construct(RequestStack $requestStack, Packages $packages)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->packages = $packages;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('routeActive', [$this, 'getRouteActive']),
            new TwigFunction('routeActiveClass', [$this, 'getRouteActiveClass']),
            new TwigFunction('avatarUrl', [$this, 'getAvatarUrl'])
        ];
    }

    public function getRouteActive(string $route, bool $checkStart = true): bool
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

    public function getRouteActiveClass(string $route, bool $checkStart = true, string $activeClass = 'active'): ?string
    {
        return $this->getRouteActive($route, $checkStart) ? " {$activeClass}" : null;
    }

    public function getAvatarUrl(?string $avatarUuid = null): string
    {
        if ($avatarUuid === null)
        {
            return $this->packages->getUrl('build/images/default-avatar.png');
        }

        return $this->packages->getUrl('uploads/avatars/' . $avatarUuid, null);
    }
}
