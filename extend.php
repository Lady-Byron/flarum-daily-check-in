<?php

use Flarum\Extend;
use Flarum\User\Event\Saving;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Api\Serializer\ForumSerializer;

use Ziven\checkin\AddAttribute\AddUserCheckinAttributes;
use Ziven\checkin\Listeners\doCheckin;
use Ziven\checkin\Listeners\AwardXpOnCheckin;
use Ziven\checkin\Listeners\AwardMoneyStreakBonus;
use Ziven\checkin\Access\UserPolicy;
use Ziven\checkin\Event\checkinUpdated;

return [
    // 前台/后台资源
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less'),

    // 语言包（原插件就有，保留）
    (new Extend\Locales(__DIR__ . '/locale')),

    // 仅给 User 序列化器注入用户相关属性（避免类型不匹配）
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(AddUserCheckinAttributes::class),

    // 给 Forum 序列化器下发论坛级属性：allowCheckIn（沿用原插件的闭包写法）
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('allowCheckIn', function (ForumSerializer $serializer) {
            return $serializer->getActor()->hasPermission('checkin.allowCheckIn');
        }),

    // 设置项序列化到 forum 数据（前端读取）
    (new Extend\Settings())
        // —— 原有 —— //
        ->serializeToForum('forumCheckinRewarMoney', 'ziven-forum-checkin.checkinRewardMoney', function ($raw) {
            return (float) $raw;
        })
        ->serializeToForum('forumAutoCheckin', 'ziven-forum-checkin.autoCheckIn', 'intval', 0)
        ->serializeToForum('forumAutoCheckinDelay', 'ziven-forum-checkin.autoCheckInDelay', 'intval', 0)
        ->serializeToForum('forumCheckinTimeZone', 'ziven-forum-checkin.checkinTimeZone', 'intval', 0)
        ->serializeToForum('forumCheckinSuccessPromptType', 'ziven-forum-checkin.checkinSuccessPromptType', 'intval', 0)
        ->serializeToForum('forumCheckinSuccessPromptText', 'ziven-forum-checkin.checkinSuccessPromptText')
        ->serializeToForum('forumCheckinSuccessPromptRewardText', 'ziven-forum-checkin.checkinSuccessPromptRewardText')

        // —— 新增（经验 & 连签加成） —— //
        ->serializeToForum('forumCheckinRewardExp', 'ziven-forum-checkin.checkinRewardExp', 'intval', 0)
        ->serializeToForum('forumCheckinStreakBonusExpPerDay', 'ziven-forum-checkin.streakBonusExpPerDay', 'intval', 0)
        ->serializeToForum('forumCheckinStreakBonusMoneyPerDay', 'ziven-forum-checkin.streakBonusMoneyPerDay', function ($raw) {
            return (float) $raw;
        })
        ->serializeToForum('forumCheckinStreakBonusMaxDays', 'ziven-forum-checkin.streakBonusMaxDays', 'intval', 0),

    // 权限策略
    (new Extend\Policy())
        ->modelPolicy(\Flarum\User\User::class, UserPolicy::class),

    // 事件监听：Saving 阶段执行签到落库；签到完成事件上发 EXP 与连签额外 money
    (new Extend\Event())
        ->listen(Saving::class, [doCheckin::class, 'checkinSaved'])
        ->listen(checkinUpdated::class, [AwardXpOnCheckin::class, 'handle'])
        ->listen(checkinUpdated::class, [AwardMoneyStreakBonus::class, 'handle']),
];
