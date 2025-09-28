<?php

use Illuminate\Database\Schema\Builder;
use Flarum\Settings\SettingsRepositoryInterface;

return [
    'up' => function (Builder $schema) {
        /** @var SettingsRepositoryInterface $settings */
        $settings = resolve(SettingsRepositoryInterface::class);

        $defaults = [
            // 基础：每次签到经验（原本你可以用旧迁移写过；此处仅在为空时补 0）
            'ziven-forum-checkin.checkinRewardExp' => 0,

            // 连签加成（新增）
            'ziven-forum-checkin.streakBonusExpPerDay'   => 0,  // 连续签到的每日额外 EXP（线性）
            'ziven-forum-checkin.streakBonusMoneyPerDay' => 0,  // 连续签到的每日额外 Money（线性）
            'ziven-forum-checkin.streakBonusMaxDays'     => 0,  // 连签加成计入的最大天数；0 表示不封顶
        ];

        foreach ($defaults as $key => $val) {
            if ($settings->get($key) === null) {
                $settings->set($key, $val);
            }
        }
    },

    'down' => function (Builder $schema) {
        /** @var SettingsRepositoryInterface $settings */
        $settings = resolve(SettingsRepositoryInterface::class);

        $keys = [
            'ziven-forum-checkin.checkinRewardExp',
            'ziven-forum-checkin.streakBonusExpPerDay',
            'ziven-forum-checkin.streakBonusMoneyPerDay',
            'ziven-forum-checkin.streakBonusMaxDays',
        ];

        foreach ($keys as $key) {
            $settings->delete($key);
        }
    },
];
