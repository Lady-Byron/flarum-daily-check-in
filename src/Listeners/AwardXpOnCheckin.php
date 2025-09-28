<?php

namespace Ziven\checkin\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Ziven\checkin\Event\checkinUpdated;

class AwardXpOnCheckin
{
    protected SettingsRepositoryInterface $settings;
    protected Dispatcher $events;

    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $events)
    {
        $this->settings = $settings;
        $this->events   = $events;
    }

    public function handle(checkinUpdated $event): void
    {
        $user = $event->user;
        if (!$user) return;

        // 软依赖：未安装自定义等级系统则跳过
        if (!class_exists(\FoskyM\CustomLevels\Event\ExpUpdated::class)) return;

        $baseExp     = (int) ($this->settings->get('ziven-forum-checkin.checkinRewardExp') ?? 0);
        $bonusPerDay = (int) ($this->settings->get('ziven-forum-checkin.streakBonusExpPerDay') ?? 0);
        $maxDays     = (int) ($this->settings->get('ziven-forum-checkin.streakBonusMaxDays') ?? 0);

        $streak = (int) ($user->total_continuous_checkin_count ?? 0);
        $eff    = $maxDays > 0 ? min($streak, $maxDays) : $streak;

        // 从第 2 天开始算“额外”，线性：bonus = (eff - 1) * bonusPerDay
        $bonusExp = max(0, $eff - 1) * max(0, $bonusPerDay);
        $xp       = $baseExp + $bonusExp;

        if ($xp <= 0) return;

        // 不立即 save，交由外层 Saving 流程统一保存，避免递归
        $user->exp = (int) ($user->exp ?? 0) + $xp;

        $this->events->dispatch(new \FoskyM\CustomLevels\Event\ExpUpdated(
            $user,
            $xp,
            'daily_check_in',
            [
                'source'          => 'daily_check_in',
                'base_exp'        => $baseExp,
                'streak_days'     => $streak,
                'streak_bonus'    => $bonusExp,
                'streak_eff_days' => $eff,
                'date'            => date('Y-m-d'),
            ]
        ));
    }
}
