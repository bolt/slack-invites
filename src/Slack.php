<?php

declare(strict_types=1);

namespace Bolt\Site\SlackInvites;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Common\Json;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Slack API service.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Slack
{
    /** @var HttpClient */
    private $client;
    /** @var CacheItemPoolInterface */
    private $cache;
    /** @var string */
    private $team;
    /** @var string */
    private $token;
    /** @var Bag */
    private $teamInfo;

    public function __construct(HttpClient $client, CacheItemPoolInterface $cache, string $team, string $token)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->team = $team;
        $this->token = $token;
    }

    /**
     * Return the configured Slack team name.
     *
     * @return string
     */
    public function getTeamName(): string
    {
        return $this->getTeamInfo()->getPath('team/name');
    }

    /**
     * Return the configured Slack team icon.
     *
     * @param int $width
     *
     * @return string
     */
    public function getTeamIcon(int $width = 132): string
    {
        return $this->getTeamInfo()->getPath('team/icon/image_' . $width);
    }

    /**
     * Return the current Slack team's user count.
     *
     * @return Bag
     */
    public function getUserCount(): Bag
    {
        $total = 0;
        $active = 0;
        $bots = 0;

        /** @var Bag $user */
        foreach ($this->getTeamInfo()->get('users') as $user) {
            if ($user->get('is_bot')) {
                ++$bots;
                continue;
            }
            ++$total;
            if ($user->get('presence') === 'active') {
                ++$active;
            }
        }

        return Bag::from([
            'active' => $active,
            'bots'   => $bots,
            'total'  => $total,
            'ratio'  => $active . ' / ' . $total,
        ]);
    }

    /**
     * Return information about the team.
     *
     * @return Bag
     */
    public function getTeamInfo(): Bag
    {
        if ($this->teamInfo === null) {
            $this->refresh();
        }

        return $this->teamInfo;
    }

    /**
     * Request a team invite for the given email address.
     *
     * @param string $email
     *
     * @throws \Http\Client\Exception
     *
     * @return Bag
     */
    public function getInvite(string $email): Bag
    {
        $url = 'https://' . $this->team . '.slack.com/api/users.admin.invite';
        $request = new Request(
            'POST',
            $url,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            \http_build_query(['token' => $this->token, 'email' => $email])
        );
        $response = $this->client->sendRequest($request)->getBody();
        $bag = MutableBag::fromRecursive(Json::parse($response));
        if ($bag->get('error')) {
            $this->addErrorMessage($bag);
        }

        return $bag->immutable();
    }

    private function addErrorMessage(MutableBag $bag): void
    {
        $error = $bag->get('error');
        if ($error === 'already_invited') {
            $bag->set('error', ['already_invited' => 'Email address has already received an invitation']);
        } elseif ($error === 'already_in_team') {
            $bag->set('error', ['already_in_team' => 'Email address is already registered to a member of the team']);
        } elseif ($error === 'channel_not_found') {
            $bag->set('error', ['channel_not_found' => 'Provided channel ID does not match a real channel']);
        } elseif ($error === 'sent_recently') {
            $bag->set('error', ['sent_recently' => 'Invitation has already been sent recently']);
        } elseif ($error === 'user_disabled') {
            $bag->set('error', ['user_disabled' => 'User account has been deactivated']);
        } elseif ($error === 'missing_scope') {
            $bag->set('error', ['missing_scope' => 'Using an access_token not authorized for \'client\' scope']);
        } elseif ($error === 'invalid_email') {
            $bag->set('error', ['invalid_email' => 'Slack does not recognize this as a valid email addresses, even though it might be valid. This is a known issue.']);
        } elseif ($error === 'not_allowed') {
            $bag->set('error', ['not_allowed' => 'When SSO is enabeld this method can not be used to invite new users except guests. The SCIM API needs to be used instead to invite new users. For inviting guests the restricted or ultra_restricted property needs to be provided']);
        }
    }

    /**
     * Refresh Slack data.
     */
    private function refresh(): void
    {
        $item = $this->cache->getItem('team.info');
        if (!$item->isHit()) {
            $url = \sprintf('https://%s.slack.com/api/rtm.start?token=%s', $this->team, $this->token);
            $response = $this->client->get($url)->getBody();
            $item->set(Bag::fromRecursive(Json::parse($response)));
            $item->expiresAfter(600);
            $this->cache->save($item);
        }

        $this->teamInfo = $item->get();
    }
}
