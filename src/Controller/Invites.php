<?php

namespace Bolt\Site\SlackInvites\Controller;

use Bolt\Collection\ImmutableBag;
use Bolt\Site\SlackInvites\Slack;
use Exception;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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

        $ctr->post('/invite', [$this, 'slackInvite'])
            ->bind('slackInvite')
        ;

        $app->error(function (Exception $e, Request $request, $code) use ($app) {
            if ($app['debug']) {
                return null;
            }

            // 404.html, or 40x.html, or 4xx.html, or error.html
            $templates = [
                'errors/' . $code . '.html.twig',
                'errors/' . substr($code, 0, 2) . 'x.html.twig',
                'errors/' . substr($code, 0, 1) . 'xx.html.twig',
                'errors/default.html.twig',
            ];
            $html = $app['twig']->resolveTemplate($templates)->render(['code' => $code]);

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

        return $app['twig']->render('slack.html.twig', $context);
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
