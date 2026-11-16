<?php

namespace Olakunlevpn\UserSpaces\Repository;

use Olakunlevpn\UserSpaces\Entity\UserSpace;
use XF\Entity\User;
use XF\Mvc\Entity\Repository;

class UserSpaceRepository extends Repository
{
    public function getUserSpace(int $userId): ?UserSpace
    {
        return $this->em->find(UserSpace::class, $userId);
    }

    public function getOrCreateUserSpace(User $user): UserSpace
    {
        $space = $this->getUserSpace($user->user_id);
        if (!$space)
        {
            /** @var UserSpace $space */
            $space = $this->em->create(UserSpace::class);
            $space->user_id = $user->user_id;
            $space->space_name = $user->username;
            $space->save();
        }

        return $space;
    }

    public function getUserSpaceByUsername(string $username): ?UserSpace
    {
        /** @var User|null $user */
        $user = $this->finder(\XF\Finder\UserFinder::class)
            ->where('username', $username)
            ->fetchOne();

        if (!$user)
        {
            return null;
        }

        return $this->getOrCreateUserSpace($user);
    }

    public function logView(UserSpace $space): void
    {
        $this->db()->query('
            UPDATE xf_user_space
            SET total_views = total_views + 1
            WHERE user_id = ?
        ', [$space->user_id]);
    }

    public function logClick(UserSpace $space, int $linkIndex): void
    {
        $this->db()->query('
            UPDATE xf_user_space
            SET total_clicks = total_clicks + 1
            WHERE user_id = ?
        ', [$space->user_id]);
    }
}
