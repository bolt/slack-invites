<?php

namespace Bolt\Site\SlackInvites\Controller;

use Bolt\Collection\ImmutableBag;
use Bolt\Site\SlackInvites\BadgeGenerator;
use Bolt\Site\SlackInvites\Slack;
use Exception;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Twig\Environment;

/**
 * Slack invitations controller.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Invites implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        $ctr->get('/', [$this, 'slack'])
            ->bind('slack')
        ;

        $ctr->get('/badge/{type}', [$this, 'slackBadge'])
            ->assert('type', '(active|ratio|total)')
            ->bind('slackBadge')
        ;

        $ctr->post('/invite', [$this, 'slackInvite'])
            ->bind('slackInvite')
        ;

        $app->error(function (Exception $e, Request $request, $code) use ($app) {
            if ($app['debug']) {
                return null;
            }

            // 404.html, or 40x.html, or 4xx.html, or error.html
            $templates = [
                '@templates/errors/' . $code . '.html.twig',
                '@templates/errors/' . substr($code, 0, 2) . 'x.html.twig',
                '@templates/errors/' . substr($code, 0, 1) . 'xx.html.twig',
                '@templates/errors/default.html.twig',
            ];
            $twig = $app['twig'];
            $html = $twig->resolveTemplate($templates)->render(['code' => $code]);

            return new Response($html, $code);
        });

        return $ctr;
    }

    /**
     * @param Application $app
     *
     * @return string
     */
    public function slack(Application $app)
    {
        /** @var Environment $twig */
        $twig = $app['twig'];
        /** @var Slack $slack */
        $slack = $app['slack'];
        $teamInfo = $slack->getTeamInfo();
        $avatars = [];

        /** @var ImmutableBag $user */
        foreach ($teamInfo->get('users') as $user) {
            if ($user->get('is_bot')) {
                continue;
            }
            if ($user->get('id') === 'USLACKBOT') {
                continue;
            }
            $userName = $user->get('name');
            $avatars[$userName] = $user->getPath('profile/image_24');
        }
        $context = [
            'image'   => $slack->getTeamIcon(),
            'team'    => $slack->getTeamName(),
            'users'   => $slack->getUserCount(),
            'avatars' => $avatars,
        ];

        return $twig->render('@templates/slack.html.twig', $context);
    }

    /**
     * @param Application $app
     * @param string      $type
     *
     * @return Response
     */
    public function slackBadge(Application $app, $type)
    {
        /** @var BadgeGenerator $generator */
        $generator = $app['slack.badge_generator'];
        $ttl = $app['debug'] ? -1 : 120;

        $response = new Response();
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $type . '.svg');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/svg+xml; charset=utf-8');
        $response->setContent($generator->get($type, $ttl));

        return $response;
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return JsonResponse
     */
    public function slackInvite(Application $app, Request $request)
    {
        $email = $request->request->get('email');
        if ($email === null) {
            return new JsonResponse('No email address', Response::HTTP_BAD_REQUEST);
        }
        /** @var ConstraintViolationListInterface $violationList */
        $violationList = $app['validator']->validate($email, new Assert\Email());
        if ($violationList->count() > 0) {
            $message = '';
            foreach ($violationList as $violation) {
                /** @var ConstraintViolationInterface $violation */
                $message .= $violation->getMessage() . PHP_EOL;
            }

            return new JsonResponse($message, Response::HTTP_BAD_REQUEST);
        }

        /** @var Slack $slack */
        $slack = $app['slack'];
        $result = $slack->getInvite($request->request->get('email'));
        if ($result->get('ok')) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        return new JsonResponse($result->get('error'), Response::HTTP_FORBIDDEN);
    }
}
