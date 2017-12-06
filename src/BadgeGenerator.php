<?php

declare(strict_types=1);

namespace Bolt\Site\SlackInvites;

use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment;

/**
 * Badge generator.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BadgeGenerator
{
    /** @var Slack */
    private $slack;
    /** @var CacheInterface */
    private $cache;
    /** @var Environment */
    private $twig;
    /** @var string */
    private $template;

    public function __construct(Slack $slack, CacheItemPoolInterface $cache, Environment $twig, string $template = '@templates/badge.svg.twig')
    {
        $this->slack = $slack;
        $this->cache = $cache;
        $this->twig = $twig;
        $this->template = $template;
    }

    /**
     * @param string       $type
     * @param DateInterval $ttl
     *
     * @return string
     */
    public function get(string $type, DateInterval $ttl): string
    {
        $cacheKey = 'badge.' . $type;
        if ($this->cache->has($cacheKey) === false) {
            $count = $this->slack->getUserCount();
            $context = [
                'name'  => 'Slack',
                'count' => $count->get($type),
            ];
            $svg = $this->twig->render($this->template, $context);
            $this->cache->set($cacheKey, $svg, $ttl);
        }

        return $this->cache->get($cacheKey);
    }
}
