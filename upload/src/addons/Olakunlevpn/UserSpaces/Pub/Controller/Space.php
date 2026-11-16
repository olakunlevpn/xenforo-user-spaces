<?php

namespace Olakunlevpn\UserSpaces\Pub\Controller;

use Olakunlevpn\UserSpaces\Entity\UserSpace;
use Olakunlevpn\UserSpaces\Repository\UserSpaceRepository;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;
use XF\Repository\AttachmentRepository;
use XF\Repository\EmbedResolverRepository;
use XF\Repository\ProfilePostRepository;
use XF\Repository\UnfurlRepository;
use XF\Repository\UserAlertRepository;

class Space extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $username = $params->username;
        if (!$username)
        {
            return $this->notFound();
        }

        /** @var UserSpaceRepository $spaceRepo */
        $spaceRepo = $this->repository(UserSpaceRepository::class);
        $space = $spaceRepo->getUserSpaceByUsername($username);

        if (!$space || !$space->User || !$space->is_enabled)
        {
            return $this->notFound(\XF::phrase('olakunlevpn_user_space_not_found'));
        }

        $user = $space->User;

        // Log view counter
        $spaceRepo->logView($space);

        $page = $this->filterPage();
        $perPage = $this->options()->messagesPerPage ?: 15;

        /** @var ProfilePostRepository $profilePostRepo */
        $profilePostRepo = $this->repository(ProfilePostRepository::class);
        /** @var AttachmentRepository $attachmentRepo */
        $attachmentRepo = $this->repository(AttachmentRepository::class);

        if ($user->canViewPostsOnProfile())
        {
            $profilePostFinder = $profilePostRepo->findProfilePostsOnProfile($user, [
                'allowOwnPending' => $this->hasContentPendingApproval(),
            ]);

            $total = $profilePostFinder->total();
            $profilePosts = $profilePostFinder->limitByPage($page, $perPage)->fetch();

            $attachmentRepo->addAttachmentsToContent($profilePosts, 'profile_post');

            $isRobot = $this->isRobot();
            $profilePosts = $profilePostRepo->addCommentsToProfilePosts($profilePosts, $isRobot);

            $unfurlRepo = $this->repository(UnfurlRepository::class);
            $unfurlRepo->addUnfurlsToContent($profilePosts, $isRobot);

            $embedRepo = $this->repository(EmbedResolverRepository::class);
            $embedRepo->addEmbedsToContent($profilePosts);

            $commentIds = [];
            foreach ($profilePosts as $profilePost)
            {
                if ($profilePost->LatestComments)
                {
                    $commentIds = array_merge($commentIds, $profilePost->LatestComments->keys());
                }
            }

            /** @var UserAlertRepository $userAlertRepo */
            $userAlertRepo = $this->repository(UserAlertRepository::class);
            $userAlertRepo->markUserAlertsReadForContent('profile_post', $profilePosts->keys());
            $userAlertRepo->markUserAlertsReadForContent('profile_post_comment', $commentIds);
        }
        else
        {
            $total = 0;
            $profilePosts = $this->em()->getEmptyCollection();
        }

        $canInlineMod = false;
        $canViewAttachments = false;
        $profilePostAttachData = [];
        foreach ($profilePosts as $profilePost)
        {
            if (!$canInlineMod && $profilePost->canUseInlineModeration())
            {
                $canInlineMod = true;
            }
            if (!$canViewAttachments && $profilePost->canViewAttachments())
            {
                $canViewAttachments = true;
            }
            if ($profilePost->canUploadAndManageAttachments())
            {
                $profilePostAttachData[$profilePost->profile_post_id] = $attachmentRepo->getEditorData('profile_post_comment', $profilePost);
            }
        }

        if ($user->canUploadAndManageAttachmentsOnProfile())
        {
            $attachmentData = $attachmentRepo->getEditorData('profile_post', $user);
        }
        else
        {
            $attachmentData = null;
        }

        $boardUrl = rtrim($this->options()->boardUrl, '/');
        $vanityUrl = $boardUrl . '/@' . rawurlencode($user->username);

        $viewParams = [
            'space' => $space,
            'user' => $user,
            'vanityUrl' => $vanityUrl,
            'profilePosts' => $profilePosts,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'canInlineMod' => $canInlineMod,
            'canViewAttachments' => $canViewAttachments,
            'profilePostAttachData' => $profilePostAttachData,
            'attachmentData' => $attachmentData,
            'isOwner' => (\XF::visitor()->user_id === $user->user_id),
        ];

        return $this->view('Olakunlevpn\UserSpaces:Space\View', 'olakunlevpn_user_space_view', $viewParams);
    }

    public function actionShare(ParameterBag $params)
    {
        $username = $params->username;
        if (!$username)
        {
            return $this->notFound();
        }

        /** @var UserSpaceRepository $spaceRepo */
        $spaceRepo = $this->repository(UserSpaceRepository::class);
        $space = $spaceRepo->getUserSpaceByUsername($username);

        if (!$space || !$space->User || !$space->is_enabled)
        {
            return $this->notFound(\XF::phrase('olakunlevpn_user_space_not_found'));
        }

        $user = $space->User;
        $boardUrl = rtrim($this->options()->boardUrl, '/');
        $vanityUrl = $boardUrl . '/@' . rawurlencode($user->username);

        $viewParams = [
            'space' => $space,
            'user' => $user,
            'vanityUrl' => $vanityUrl,
        ];

        return $this->view('Olakunlevpn\UserSpaces:Space\Share', 'olakunlevpn_user_space_share', $viewParams);
    }

    public function actionClick(ParameterBag $params)
    {
        $username = $params->username;
        $index = $this->filter('idx', 'uint');

        if ($index < 1 || $index > 3)
        {
            return $this->notFound();
        }

        /** @var UserSpaceRepository $spaceRepo */
        $spaceRepo = $this->repository(UserSpaceRepository::class);
        $space = $spaceRepo->getUserSpaceByUsername($username);

        if (!$space || !$space->User)
        {
            return $this->notFound();
        }

        $urlCol = "link_{$index}_url";
        $targetUrl = (string)$space->get($urlCol);

        if ($targetUrl === '')
        {
            return $this->notFound();
        }

        // Log click
        $spaceRepo->logClick($space, $index);

        return $this->redirectPermanently($targetUrl);
    }
}
