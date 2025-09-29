<?php

namespace Ziven\checkin\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\User\Event\Saving;
use Ziven\checkin\Event\checkinUpdated;
use Illuminate\Support\Arr;

class doCheckin
{
    protected SettingsRepositoryInterface $settings;
    protected Dispatcher $events;

    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $events)
    {
        $this->settings = $settings;
        $this->events   = $events;
    }

    public function checkinSaved(Saving $event): void
    {
        $actor = $event->actor;
        $user  = $event->user;

        // 使用全局权限判断，避免对 Policy 的强依赖
        $allowCheckIn = $actor->hasPermission('checkin.allowCheckIn');

        if (!$allowCheckIn) {
            return;
        }

        $attributes = Arr::get($event->data, 'attributes', []);

        // 仅当前端显式发起“签到”动作时才执行
        if (!array_key_exists('canCheckin', $attributes)) {
            return;
        }

        // 站点时区（以小时为单位的偏移）
        $timezoneHours           = (int) ($this->settings->get('ziven-forum-checkin.checkinTimeZone', 0));
        $currentTimestamp        = time() + $timezoneHours * 60 * 60;
        $currentDateAtMidnightTs = strtotime(date('Y-m-d', $currentTimestamp) . ' 00:00:00');

        // 上次签到时间（字符串或 null）
        $lastCheckinTime = $user->last_checkin_time;

        // 仅当有值时才解析日期，避免 explode(null) 的弃用告警
        $lastMidnightTs = null;
        if (!empty($lastCheckinTime)) {
            // 取日期部分（更稳妥而不使用 explode(null)）
            $spacePos   = strpos($lastCheckinTime, ' ');
            $datePart   = $spacePos !== false ? substr($lastCheckinTime, 0, $spacePos) : $lastCheckinTime;
            if ($datePart) {
                $lastMidnightTs = strtotime($datePart . ' 00:00:00');
            }
        }

        // 是否允许签到：第一次签到直接允许；否则要求“跨日”
        $canCheckin = $lastMidnightTs === null
            ? true
            : ($currentDateAtMidnightTs > $lastMidnightTs);

        if (!$canCheckin) {
            return;
        }

        // ===== 真正签到：连续天数与总次数 =====
        $currentStreak = (int) ($user->total_continuous_checkin_count ?? 0);

        if ($lastMidnightTs !== null) {
            // 与“上次签到那天的 00:00” 相差不足 48 小时，视作连签
            $hoursFromLastMidnight = ($currentTimestamp - $lastMidnightTs) / 3600;
            if ($hoursFromLastMidnight < 48) {
                $currentStreak += 1;
            } else {
                $currentStreak = 1;
            }
        } else {
            // 首次签到
            $currentStreak = 1;
        }

        $user->total_continuous_checkin_count = $currentStreak;
        $user->last_checkin_time              = date('Y-m-d H:i:s', $currentTimestamp);
        $user->total_checkin_count            = (int) ($user->total_checkin_count ?? 0) + 1;

        // ===== 基础奖励：Money（如有 money 字段）=====
        if (isset($user->money)) {
            $checkinRewardMoney = (float) ($this->settings->get('ziven-forum-checkin.checkinRewardMoney', 0));
            if ($checkinRewardMoney > 0) {
                $user->money += $checkinRewardMoney;
            }
        }

        // 派发“签到完成”事件（由其他监听器执行发 EXP / 连签额外奖励 / 通知等）
        $this->events->dispatch(new checkinUpdated($user));
    }
}
