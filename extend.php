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

    // 序列化到 API（给前台/管理端可读取的用户与站点属性）
    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(AddUserCheckinAttributes::class),
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(AddUserCheckinAttributes::class),

    // 设置项序列化（保持你原有键名，另新增 EXP 与连签奖励相关键）
    (new Extend\Settings())
        // —— 原有 —— //
        ->serializeToForum('forumCheckinRewarMoney', 'ziven-forum-checkin.checkinRewardMoney', function ($raw) { // 注意原插件键名拼写保持不变
            return (float) $raw;
        })
        ->serializeToForum('forumAutoCheckin', 'ziven-forum-checkin.autoCheckIn', 'intval', 0)
        ->serializeToForum('forumAutoCheckinDelay', 'ziven-forum-checkin.autoCheckInDelay', 'intval', 0)
        ->serializeToForum('forumCheckinTimeZone', 'ziven-forum-checkin.checkinTimeZone', 'intval', 0)
        ->serializeToForum('forumCheckinSuccessPromptType', 'ziven-forum-checkin.checkinSuccessPromptType', 'intval', 0)
        ->serializeToForum('forumCheckinSuccessPromptText', 'ziven-forum-checkin.checkinSuccessPromptText')
        ->serializeToForum('forumCheckinSuccessPromptRewardText', 'ziven-forum-checkin.checkinSuccessPromptRewardText')

        // —— 新增：基础 EXP 与连签奖励设置 —— //
        ->serializeToForum('forumCheckinRewardExp', 'ziven-forum-checkin.checkinRewardExp', 'intval', 0)
        ->serializeToForum('forumCheckinStreakBonusExpPerDay', 'ziven-forum-checkin.streakBonusExpPerDay', 'intval', 0)
        ->serializeToForum('forumCheckinStreakBonusMoneyPerDay', 'ziven-forum-checkin.streakBonusMoneyPerDay', function ($raw) {
            return (float) $raw;
        })
        ->serializeToForum('forumCheckinStreakBonusMaxDays', 'ziven-forum-checkin.streakBonusMaxDays', 'intval', 0),

    // 权限策略
    (new Extend\Policy())
        ->model(\Flarum\User\User::class, UserPolicy::class),

    // 事件监听：Saving 阶段执行签到落库；签到完成事件上发 EXP 与连签额外 money
    (new Extend\Event())
        ->listen(Saving::class, [doCheckin::class, 'checkinSaved'])
        ->listen(checkinUpdated::class, [AwardXpOnCheckin::class, 'handle'])
        ->listen(checkinUpdated::class, [AwardMoneyStreakBonus::class, 'handle']),
];
