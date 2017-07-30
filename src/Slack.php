<?php

namespace Bolt\Site\SlackInvites;

use Bolt\Collection\ImmutableBag;
use Bolt\Common\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\SimpleCache\CacheInterface;

/**
 * Slack API service.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Slack
{
    /** @var Client */
    private $guzzle;
    /** @var CacheInterface */
    private $cache;
    /** @var string */
    private $team;
    /** @var string */
    private $token;
    /** @var ImmutableBag */
    private $teamInfo;

    /**
     * Constructor.
     *
     * @param Client         $guzzle
     * @param CacheInterface $cache
     * @param string         $team
     * @param string         $token
     */
    public function __construct(Client $guzzle, CacheInterface $cache, $team, $token)
    {
        $this->guzzle = $guzzle;
        $this->cache = $cache;
        $this->team = $team;
        $this->token = $token;

        $this->refresh();
    }

    /**
     * Return the configured Slack team name.
     *
     * @return string
     */
    public function getTeamName()
    {
        return $this->teamInfo->getPath('team/name');
    }

    /**
     * Return the configured Slack team icon.
     *
     * @param int $width
     *
     * @return string
     */
    public function getTeamIcon($width = 132)
    {
        return $this->teamInfo->getPath('team/icon/image_' . $width);
    }

    /**
     * Return the current Slack team's user count.
     *
     * @return ImmutableBag
     */
    public function getUserCount()
    {
        $total = 0;
        $active = 0;
        $bots = 0;

        /** @var ImmutableBag $user */
        foreach ($this->teamInfo->get('users') as $user) {
            if ($user->get('is_bot')) {
                ++$bots;
                continue;
            }
            ++$total;
            if ($user->get('presence') === 'active') {
                ++$active;
            }
        }

        return ImmutableBag::from([
            'active' => $active,
            'bots'   => $bots,
            'total'  => $total,
            'ratio'  => $active . ' / ' . $total,
        ]);
    }

    /**
     * Return information about the team.
     *
     * @return ImmutableBag
     */
    public function getTeamInfo()
    {
        return $this->teamInfo;
    }

    /**
     * Request a team invite for the given email address.
     *
     * @param string $email
     *
     * @return ImmutableBag
     */
    public function getInvite($email)
    {
        $url = 'https://' . $this->team . '.slack.com/api/users.admin.invite';
        $request = new Request(
            'POST',
            $url,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query(['token' => $this->token, 'email' => $email])
        );
        $response = $this->guzzle->send($request)->getBody();

        return ImmutableBag::fromRecursive(Json::parse($response));
    }

    /**
     * Refresh Slack data.
     */
    private function refresh()
    {
        if ($this->cache->has('team.info') === false) {
            $url = sprintf('https://%s.slack.com/api/rtm.start?token=%s', $this->team, $this->token);
            $response = $this->guzzle->get($url)->getBody();
            $this->cache->set('team.info', ImmutableBag::fromRecursive(Json::parse($response)), 600);
        }

        $this->teamInfo = $this->cache->get('team.info');
    }
}
