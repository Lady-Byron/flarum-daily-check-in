<?php

namespace Ziven\checkin\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use Ziven\checkin\Event\checkinUpdated;

class AwardMoneyStreakBonus
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(checkinUpdated $event): void
    {
        $user = $event->user;
        if (!$user) return;

        // 只有在用户模型上存在 money 字段时才处理（兼容未装 money 拓展的环境）
        if (!isset($user->money)) return;

        $bonusPerDay = (float) ($this->settings->get('ziven-forum-checkin.streakBonusMoneyPerDay') ?? 0);
        $maxDays     = (int)   ($this->settings->get('ziven-forum-checkin.streakBonusMaxDays') ?? 0);

        if ($bonusPerDay <= 0) return;

        $streak = (int) ($user->total_continuous_checkin_count ?? 0);
        $eff    = $maxDays > 0 ? min($streak, $maxDays) : $streak;

        $bonusMoney = max(0, $eff - 1) * $bonusPerDay;
        if ($bonusMoney <= 0) return;

        // 不立即 save，交由外层 Saving 流程统一保存
        $user->money += $bonusMoney;
    }
}
