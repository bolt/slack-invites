<?php

namespace Bolt\Site\SlackInvites;

use DateInterval;
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

    /**
     * Constructor.
     *
     * @param Slack          $slack
     * @param CacheInterface $cache
     * @param Environment    $twig
     * @param string         $template
     */
    public function __construct(Slack $slack, CacheInterface $cache, Environment $twig, $template = '@templates/badge.svg.twig')
    {
        $this->slack = $slack;
        $this->cache = $cache;
        $this->twig = $twig;
        $this->template = $template;
    }

    /**
     * @param string                $type
     * @param DateInterval|int|null $ttl
     *
     * @return mixed
     */
    public function get($type, $ttl = 120)
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
