<?php

namespace Ziven\checkin\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Ziven\checkin\Event\checkinUpdated;

/**
 * 在每日签到完成后，给用户增加自定义等级系统的经验。
 * 软依赖 foskym/flarum-custom-levels：未安装则静默跳过。
 *
 * 设计要点：
 * - 不调用 save()，仅修改传入的 $event->user 对象的 exp，
 *   交由外层保存流程统一落库，避免递归触发 Saving。
 * - 派发 ExpUpdated 事件，让对方扩展完成日志/升级/通知等副作用。
 */
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
        if (!$user) {
            return;
        }

        // 软依赖：未安装自定义等级扩展则跳过
        if (!class_exists(\FoskyM\CustomLevels\Event\ExpUpdated::class)) {
            return;
        }

        $xp = (int) ($this->settings->get('ziven-forum-checkin.checkinRewardExp') ?? 0);
        if ($xp <= 0) {
            return;
        }

        // 修改同一 User 模型实例，交由外层保存
        $currentExp = (int) ($user->exp ?? 0);
        $user->exp  = $currentExp + $xp;

        // 附加一些上下文信息，便于对方日志区分来源
        $relationship = [
            'source' => 'daily_check_in',
            'streak' => (int) ($user->total_continuous_checkin_count ?? 0),
            'total'  => (int) ($user->total_checkin_count ?? 0),
            'date'   => date('Y-m-d'),
        ];

        // 让自定义等级扩展完成日志与升级通知
        $this->events->dispatch(
            new \FoskyM\CustomLevels\Event\ExpUpdated($user, $xp, 'daily_check_in', $relationship)
        );
    }
}
