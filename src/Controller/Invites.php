<?php

declare(strict_types=1);

namespace Bolt\Site\SlackInvites\Controller;

use Bolt\Collection\Bag;
use Bolt\Site\SlackInvites\BadgeGenerator;
use Bolt\Site\SlackInvites\Form\Handler;
use Bolt\Site\SlackInvites\Slack;
use Hostnet\Component\FormHandler\HandlerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Slack invitations controller.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Invites extends AbstractController
{
    /** @var Slack */
    private $slack;

    public function __construct(Slack $slack)
    {
        $this->slack = $slack;
    }

    public function getSlack(Request $request, HandlerFactory $handlerFactory): Response
    {
        $handler = $handlerFactory->create(Handler\InviteTypeHandler::class);
        $response = $handler->handle($request);
        if ($response instanceof Response) {
            return $response;
        }

        return $this->render('@templates/slack.html.twig', [
            'form'  => $handler->getForm()->createView(),
            'slack' => $this->slack,
        ]);
    }

    public function getSlackMembers(Request $request): Response
    {
        $invite = Bag::from($request->request->get('invite', []));
        if (!$this->isCsrfTokenValid('invite', $invite->get('_token', ''))) {
            throw new AccessDeniedHttpException();
        }
        $avatars = [];
        /** @var Bag $user */
        foreach ($this->slack->getTeamInfo()->get('users') as $user) {
            if ($user->get('is_bot')) {
                continue;
            }
            if ($user->get('id') === 'USLACKBOT') {
                continue;
            }
            $userName = $user->get('name');
            $avatars[$userName] = $user->getPath('profile/image_24');
        }

        return $this->render('@templates/avatars.html.twig', ['avatars' => $avatars]);
    }

    public function getSlackBadge(BadgeGenerator $generator, string $type): Response
    {
        $ttl = $this->get('kernel')->isDebug() ? new \DateInterval('PT0S') : new \DateInterval('PT2M');

        $response = new Response();
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $type . '.svg');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/svg+xml; charset=utf-8');
        $response->setContent($generator->get($type, $ttl));

        return $response;
    }

    public function getSlackInvite(Request $request, ValidatorInterface $validator): Response
    {
        $email = $request->request->get('email');
        if ($email === null) {
            return $this->json('No email address', Response::HTTP_BAD_REQUEST);
        }
        /** @var ConstraintViolationListInterface $violationList */
        $violationList = $validator->validate($email, new Assert\Email());
        if ($violationList->count() > 0) {
            $message = '';
            foreach ($violationList as $violation) {
                /* @var ConstraintViolationInterface $violation */
                $message .= $violation->getMessage() . PHP_EOL;
            }

            return $this->json($message, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->slack->getInvite($request->request->get('email'));
        if ($result->get('ok')) {
            return $this->json(null, Response::HTTP_OK);
        }

        return $this->json($result->get('error'), Response::HTTP_FORBIDDEN);
    }
}
