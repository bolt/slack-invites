<?php

namespace Bolt\Site\SlackInvites\Form\Handler;

use Bolt\Site\SlackInvites\Form\InviteType;
use Bolt\Site\SlackInvites\Slack;
use Hostnet\Component\FormHandler\ActionSubscriberInterface;
use Hostnet\Component\FormHandler\HandlerActions;
use Hostnet\Component\FormHandler\HandlerConfigInterface;
use Hostnet\Component\FormHandler\HandlerTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * InviteType form handler.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InviteTypeHandler implements HandlerTypeInterface, ActionSubscriberInterface
{
    /** @var Slack */
    private $slack;

    public function __construct(Slack $slack)
    {
        $this->slack = $slack;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedActions()
    {
        return [
            HandlerActions::SUCCESS => 'onSuccess',
            HandlerActions::FAILURE => 'onFailure',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configure(HandlerConfigInterface $config)
    {
        $config->setType(InviteType::class);
        $config->registerActionSubscriber($this);
    }

    public function onSuccess($data, FormInterface $form, Request $request): ? Response
    {
        $result = $this->slack->getInvite($form->get('email')->getData());
        if (!$request->isXmlHttpRequest()) {
            return null;
        }
        if ($result->get('ok')) {
            return new JsonResponse(null, Response::HTTP_OK);
        }
        $error = [
            'type'   => 'api_error',
            'title'  => 'There was an API error',
            'errors' => (array) $result->get('error')
        ];

        return new JsonResponse($error, Response::HTTP_FORBIDDEN);
    }

    public function onFailure($data, FormInterface $form, Request $request): ?Response
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }
        $error = [
            'type'   => 'validation_error',
            'title'  => 'There was a validation error',
            'errors' => $this->getFormErrors($form)
        ];

        return new JsonResponse($error, Response::HTTP_BAD_REQUEST);
    }

    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        // Parent form
        foreach ($form->getErrors() as $error) {
            $errors[$form->getName()] = $error->getMessage();
        }

        // Child forms
        foreach ($form->all() as $childForm) {
            if (!$childForm instanceof FormInterface) {
                continue;
            }
            $childErrors = $this->getFormErrors($childForm);
            if ($childErrors) {
                $errors[$childForm->getName()] = $childErrors;
            }
        }

        return $errors;
    }
}
