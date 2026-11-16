<?php

namespace Olakunlevpn\UserSpaces;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;
use XF\Util\File;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $schema = $this->schemaManager();
        $schema->createTable('xf_user_space', function (Create $table)
        {
            $table->addColumn('user_id', 'int')->unsigned();
            $table->addColumn('is_enabled', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('space_name', 'varchar', 100)->setDefault('');
            $table->addColumn('bio', 'text')->nullable();
            $table->addColumn('banner_path', 'varchar', 255)->nullable();
            $table->addColumn('custom_logo_path', 'varchar', 255)->nullable();
            $table->addColumn('use_forum_avatar', 'tinyint')->unsigned()->setDefault(1);
            $table->addColumn('link_1_title', 'varchar', 100)->setDefault('');
            $table->addColumn('link_1_url', 'varchar', 255)->setDefault('');
            $table->addColumn('link_2_title', 'varchar', 100)->setDefault('');
            $table->addColumn('link_2_url', 'varchar', 255)->setDefault('');
            $table->addColumn('link_3_title', 'varchar', 100)->setDefault('');
            $table->addColumn('link_3_url', 'varchar', 255)->setDefault('');
            $table->addColumn('total_views', 'int')->unsigned()->setDefault(0);
            $table->addColumn('total_clicks', 'int')->unsigned()->setDefault(0);
            $table->addColumn('created_at', 'int')->unsigned()->setDefault(0);
            $table->addColumn('updated_at', 'int')->unsigned()->setDefault(0);
            $table->addPrimaryKey('user_id');
            $table->addKey('is_enabled');
        });

        $bannerDir = \XF::getRootDirectory() . '/data/user_spaces/banners';
        File::createDirectory($bannerDir, false);

        $logoDir = \XF::getRootDirectory() . '/data/user_spaces/logos';
        File::createDirectory($logoDir, false);
    }

    public function installStep2(): void
    {
        $notice = \XF::finder(\XF\Finder\NoticeFinder::class)->where('title', 'Claim your personal vanity link')->fetchOne();
        if (!$notice)
        {
            /** @var \XF\Entity\Notice $notice */
            $notice = \XF::em()->create(\XF\Entity\Notice::class);
            $notice->title = 'Claim your personal vanity link';
            $domain = parse_url(\XF::app()->options()->boardUrl, PHP_URL_HOST) ?: 'yourforum.com';
            $link = \XF::app()->router('public')->buildLink('account/user-space');
            $notice->message = '<div class="userSpace-notice">'
                . '<div class="userSpace-notice-title">Claim your personal vanity link: <strong>' . htmlspecialchars($domain) . '/@{name}</strong>!</div>'
                . '<div class="userSpace-notice-desc">Upload a custom banner, add your bio, and feature your top 3 links in 60 seconds.</div>'
                . '<div class="userSpace-notice-cta"><a href="' . htmlspecialchars($link) . '" class="button button--cta button--small"><span class="button-text">Customize My Space &rarr;</span></a></div>'
                . '</div>';
            $notice->active = 1;
            $notice->display_order = 1;
            $notice->dismissible = 1;
            $notice->user_criteria = [['rule' => 'is_logged_in', 'data' => []]];
            $notice->page_criteria = [];
            $notice->display_image = 'avatar';
            $notice->notice_type = 'block';
            $notice->display_style = 'primary';
            $notice->save();
        }
    }

    public function uninstallStep1(): void
    {
        $schema = $this->schemaManager();
        $schema->dropTable('xf_user_space');
    }

    public function uninstallStep2(): void
    {
        $notice = \XF::finder(\XF\Finder\NoticeFinder::class)->where('title', 'Claim your personal vanity link')->fetchOne();
        if ($notice)
        {
            $notice->delete();
        }
    }
}
