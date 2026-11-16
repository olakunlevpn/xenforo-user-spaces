<?php

namespace Olakunlevpn\UserSpaces\Entity;

use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $user_id
 * @property bool $is_enabled
 * @property string $space_name
 * @property string|null $bio
 * @property string|null $banner_path
 * @property string|null $custom_logo_path
 * @property bool $use_forum_avatar
 * @property string $link_1_title
 * @property string $link_1_url
 * @property string $link_2_title
 * @property string $link_2_url
 * @property string $link_3_title
 * @property string $link_3_url
 * @property int $total_views
 * @property int $total_clicks
 * @property int $created_at
 * @property int $updated_at
 *
 * GETTERS
 * @property-read string $title
 * @property-read string|null $banner_url
 * @property-read string|null $logo_url
 * @property-read array $valid_links
 *
 * RELATIONS
 * @property-read User|null $User
 */
class UserSpace extends Entity
{
    public function getTitle(): string
    {
        if ($this->space_name !== '')
        {
            return $this->space_name;
        }

        return $this->User ? $this->User->username : '';
    }

    public function getBannerUrl(): ?string
    {
        if (!$this->banner_path)
        {
            return null;
        }

        return \XF::app()->applyExternalDataUrl($this->banner_path);
    }

    public function getLogoUrl(): ?string
    {
        if ($this->use_forum_avatar || !$this->custom_logo_path)
        {
            return null;
        }

        return \XF::app()->applyExternalDataUrl($this->custom_logo_path);
    }

    /**
     * @return array<int, array{index: int, title: string, url: string, icon: string}>
     */
    public function getValidLinks(): array
    {
        $links = [];

        for ($i = 1; $i <= 3; $i++)
        {
            $titleCol = "link_{$i}_title";
            $urlCol = "link_{$i}_url";

            $title = trim($this->get($titleCol) ?: '');
            $url = trim($this->get($urlCol) ?: '');

            if ($title !== '' && $url !== '')
            {
                $links[] = [
                    'index' => $i,
                    'title' => $title,
                    'url' => $url,
                    'icon' => $this->detectLinkIcon($url)
                ];
            }
        }

        return $links;
    }

    public function detectLinkIcon(string $url): string
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));

        if (str_contains($host, 't.me') || str_contains($host, 'telegram'))
        {
            return 'fab fa-telegram';
        }
        if (str_contains($host, 'x.com') || str_contains($host, 'twitter.com'))
        {
            return 'fab fa-x-twitter';
        }
        if (str_contains($host, 'discord.gg') || str_contains($host, 'discord.com'))
        {
            return 'fab fa-discord';
        }
        if (str_contains($host, 'github.com'))
        {
            return 'fab fa-github';
        }
        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be'))
        {
            return 'fab fa-youtube';
        }
        if (str_contains($host, 'instagram.com'))
        {
            return 'fab fa-instagram';
        }
        if (str_contains($host, 'gumroad.com') || str_contains($host, 'store') || str_contains($host, 'shop'))
        {
            return 'fas fa-shopping-bag';
        }
        if (str_contains($host, 'patreon.com'))
        {
            return 'fab fa-patreon';
        }
        if (str_contains($host, 'reddit.com'))
        {
            return 'fab fa-reddit';
        }

        return 'fas fa-globe';
    }

    public function canManage(?\XF\Phrase &$error = null): bool
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return false;
        }

        if ($visitor->user_id === $this->user_id)
        {
            return true;
        }

        return $visitor->hasPermission('general', 'manageUserSpaces');
    }

    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_user_space';
        $structure->shortName = 'Olakunlevpn\UserSpaces:UserSpace';
        $structure->primaryKey = 'user_id';
        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'is_enabled' => ['type' => self::BOOL, 'default' => true],
            'space_name' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'bio' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'banner_path' => ['type' => self::STR, 'maxLength' => 255, 'nullable' => true, 'default' => null],
            'custom_logo_path' => ['type' => self::STR, 'maxLength' => 255, 'nullable' => true, 'default' => null],
            'use_forum_avatar' => ['type' => self::BOOL, 'default' => true],
            'link_1_title' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'link_1_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'link_2_title' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'link_2_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'link_3_title' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'link_3_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'total_views' => ['type' => self::UINT, 'default' => 0],
            'total_clicks' => ['type' => self::UINT, 'default' => 0],
            'created_at' => ['type' => self::UINT, 'default' => \XF::$time],
            'updated_at' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        $structure->getters = [
            'title' => true,
            'banner_url' => true,
            'logo_url' => true,
            'valid_links' => true
        ];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}
