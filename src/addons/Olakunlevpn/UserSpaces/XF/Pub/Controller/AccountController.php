<?php

declare(strict_types=1);

namespace Olakunlevpn\UserSpaces\XF\Pub\Controller;

use Olakunlevpn\UserSpaces\Entity\UserSpace;
use Olakunlevpn\UserSpaces\Repository\UserSpaceRepository;
use XF\Util\File;

class AccountController extends XFCP_AccountController
{
    public function actionUserSpace()
    {
        $this->assertRegistrationRequired();

        $visitor = \XF::visitor();
        /** @var UserSpaceRepository $spaceRepo */
        $spaceRepo = $this->repository(UserSpaceRepository::class);
        $space = $spaceRepo->getOrCreateUserSpace($visitor);

        $viewParams = [
            'space' => $space,
        ];

        $view = $this->view('Olakunlevpn\UserSpaces:Account\UserSpace', 'olakunlevpn_user_space_edit', $viewParams);
        return $this->addAccountWrapperParams($view, 'user_space');
    }

    public function actionUserSpaceSave()
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();

        $visitor = \XF::visitor();
        /** @var UserSpaceRepository $spaceRepo */
        $spaceRepo = $this->repository(UserSpaceRepository::class);
        $space = $spaceRepo->getOrCreateUserSpace($visitor);

        $input = $this->filter([
            'space_name' => 'str',
            'bio' => 'str',
            'use_forum_avatar' => 'bool',
            'link_1_title' => 'str',
            'link_1_url' => 'str',
            'link_2_title' => 'str',
            'link_2_url' => 'str',
            'link_3_title' => 'str',
            'link_3_url' => 'str',
        ]);

        // Helper to validate and normalize URLs
        for ($i = 1; $i <= 3; $i++)
        {
            $urlKey = "link_{$i}_url";
            $url = trim($input[$urlKey]);
            if ($url !== '')
            {
                if (!preg_match('#^https?://#i', $url))
                {
                    $url = 'https://' . $url;
                }
                $input[$urlKey] = $url;
            }
        }

        $space->bulkSet($input);

        // Banner processing
        if ($this->filter('remove_banner', 'bool'))
        {
            if ($space->banner_path)
            {
                $oldPath = \XF::getRootDirectory() . '/data/' . preg_replace('#^data/#', '', $space->banner_path);
                if (file_exists($oldPath))
                {
                    @unlink($oldPath);
                }
                $space->banner_path = null;
            }
        }
        else
        {
            $bannerUpload = $this->request->getFile('banner', false, false);
            if ($bannerUpload && $bannerUpload->isValid())
            {
                $bannerExt = strtolower($bannerUpload->getExtension());
                if (in_array($bannerExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                {
                    $bannerRelPath = 'user_spaces/banners/' . $visitor->user_id . '_' . \XF::$time . '.' . $bannerExt;
                    $bannerFullPath = \XF::getRootDirectory() . '/data/' . $bannerRelPath;

                    if (File::copyFile($bannerUpload->getTempFile(), $bannerFullPath))
                    {
                        if ($space->banner_path)
                        {
                            $oldPath = \XF::getRootDirectory() . '/data/' . preg_replace('#^data/#', '', $space->banner_path);
                            if (file_exists($oldPath))
                            {
                                @unlink($oldPath);
                            }
                        }
                        $space->banner_path = $bannerRelPath;
                    }
                }
            }
        }

        // Custom logo processing
        if ($this->filter('remove_logo', 'bool'))
        {
            if ($space->custom_logo_path)
            {
                $oldPath = \XF::getRootDirectory() . '/data/' . preg_replace('#^data/#', '', $space->custom_logo_path);
                if (file_exists($oldPath))
                {
                    @unlink($oldPath);
                }
                $space->custom_logo_path = null;
            }
        }
        else
        {
            $logoUpload = $this->request->getFile('custom_logo', false, false);
            if ($logoUpload && $logoUpload->isValid())
            {
                $logoExt = strtolower($logoUpload->getExtension());
                if (in_array($logoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                {
                    $logoRelPath = 'user_spaces/logos/' . $visitor->user_id . '_' . \XF::$time . '.' . $logoExt;
                    $logoFullPath = \XF::getRootDirectory() . '/data/' . $logoRelPath;

                    if (File::copyFile($logoUpload->getTempFile(), $logoFullPath))
                    {
                        if ($space->custom_logo_path)
                        {
                            $oldPath = \XF::getRootDirectory() . '/data/' . preg_replace('#^data/#', '', $space->custom_logo_path);
                            if (file_exists($oldPath))
                            {
                                @unlink($oldPath);
                            }
                        }
                        $space->custom_logo_path = $logoRelPath;
                        $space->use_forum_avatar = false;
                    }
                }
            }
        }

        $space->save();

        return $this->redirect($this->buildLink('account/user-space'), \XF::phrase('your_changes_have_been_saved'));
    }
}
