import Modal from 'flarum/components/Modal';

export default class checkInResultModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
  }

  className() {
    return 'checkInResultModal Modal--small';
  }

  title() {
    return (
      <div className="checkInResultModal successTitleText">
        {app.translator.trans('ziven-checkin.forum.check-in-success')}
      </div>
    );
  }

  // 把字符串中的 \n 处理成 <br/>。既支持字面量 "\n"，也支持真实换行。
  renderWithBreaks(str) {
    const normalized = String(str ?? '')
      .replace(/\\n/g, '\n');       // 把字面量 \n 变成真实换行符
    const parts = normalized.split(/\r?\n/g);

    const nodes = [];
    parts.forEach((part, i) => {
      if (i) nodes.push(<br />);
      nodes.push(part);
    });
    return nodes;
  }

  content() {
    const u = app.session.user;

    // ---- 用户与站点配置 ----
    const days = Number(u.attribute('totalContinuousCheckIn') || 0);

    const baseMoney   = Number(app.forum.attribute('forumCheckinRewarMoney') || 0);
    const baseExp     = Number(app.forum.attribute('forumCheckinRewardExp') || 0);
    const bonusExpDay = Number(app.forum.attribute('forumCheckinStreakBonusExpPerDay') || 0);
    const bonusMonDay = Number(app.forum.attribute('forumCheckinStreakBonusMoneyPerDay') || 0);
    const capDays     = Number(app.forum.attribute('forumCheckinStreakBonusMaxDays') || 0);

    // 有效参与加成的连续天数（考虑封顶）
    const effDays     = capDays > 0 ? Math.min(days, capDays) : days;

    // 连签从第2天起才有加成
    const bonusExp    = Math.max(0, effDays - 1) * Math.max(0, bonusExpDay);
    const bonusMoney  = Math.max(0, effDays - 1) * Math.max(0, bonusMonDay);
    const totalExp    = baseExp + bonusExp;
    const totalMoney  = baseMoney + bonusMoney;

    const tplText     = app.forum.attribute('forumCheckinSuccessPromptText') || '';
    const tplReward   = app.forum.attribute('forumCheckinSuccessPromptRewardText') || '';

    // money 扩展支持
    const moneyNameSetting = app.forum.attribute('antoinefr-money.moneyname'); // 可能是 "金币" 或 "[money] 金币"
    const moneyExtPresent  = typeof moneyNameSetting !== 'undefined';

    // 将金额按 money 扩展命名渲染
    const renderMoney = (val) => {
      const n = Number(val).toString();
      const name = moneyNameSetting || '';
      if (!name) return n;
      if (name.includes('[money]')) return name.replace(/\[money\]/g, n);
      return `${n} ${name}`;
    };

    // 占位符替换
    const replacePlaceholders = (tpl) => {
      if (!tpl) return '';
      let out = tpl
        .replace(/\[days\]/g, String(days))
        .replace(/\[eff_days\]/g, String(effDays))
        .replace(/\[base_money\]/g, renderMoney(baseMoney))
        .replace(/\[bonus_money\]/g, renderMoney(bonusMoney))
        .replace(/\[money\]/g, renderMoney(totalMoney))
        .replace(/\[base_exp\]/g, String(baseExp))
        .replace(/\[bonus_exp\]/g, String(bonusExp))
        .replace(/\[exp\]/g, String(totalExp));

      // [reward]：优先 money，否则 exp
      const rewardString = moneyExtPresent ? renderMoney(totalMoney) : String(totalExp);
      out = out.replace(/\[reward\]/g, rewardString);

      return out;
    };

    const successText = replacePlaceholders(tplText);
    const rewardText  = replacePlaceholders(tplReward);

    const successTextClassName = successText ? 'checkInResultModal successText' : 'checkInResultModal hideText';
    const rewardTextClassName  = rewardText ? 'checkInResultModal rewardText'  : 'checkInResultModal hideText';

    return (
      <div className="Modal-body">
        <div className={successTextClassName}>{this.renderWithBreaks(successText)}</div>
        <div className={rewardTextClassName}>{this.renderWithBreaks(rewardText)}</div>
      </div>
    );
  }
}
