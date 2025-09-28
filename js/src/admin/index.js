import { extend, override } from 'flarum/extend';

app.initializers.add('ziven-checkin', () => {
  app.extensionData
    .for('ziiven-daily-check-in')
    // 基础 money
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.reward-money')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.reward-money-requirement')}</div>
          <input type="number" className="FormControl" step="any" bidi={this.setting('ziven-forum-checkin.checkinRewardMoney')} />
        </div>
      );
    })
    // 基础 EXP
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.reward-exp')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.reward-exp-help')}</div>
          <input type="number" className="FormControl" min="0" step="1" bidi={this.setting('ziven-forum-checkin.checkinRewardExp')} />
        </div>
      );
    })
    // 连签：每日额外 EXP
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.streak-exp-per-day')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.streak-exp-per-day-help')}</div>
          <input type="number" className="FormControl" min="0" step="1" bidi={this.setting('ziven-forum-checkin.streakBonusExpPerDay')} />
        </div>
      );
    })
    // 连签：每日额外 Money
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.streak-money-per-day')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.streak-money-per-day-help')}</div>
          <input type="number" className="FormControl" min="0" step="any" bidi={this.setting('ziven-forum-checkin.streakBonusMoneyPerDay')} />
        </div>
      );
    })
    // 连签：加成封顶天数
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.streak-max-days')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.streak-max-days-help')}</div>
          <input type="number" className="FormControl" min="0" step="1" bidi={this.setting('ziven-forum-checkin.streakBonusMaxDays')} />
        </div>
      );
    })
    // 你原有的其余设置项……
    .registerSetting({
      setting: 'ziven-forum-checkin.checkinTimeZone',
      label: app.translator.trans('ziven-checkin.admin.settings.timezone'),
      type: 'number',
    })
    .registerSetting({
      setting: 'ziven-forum-checkin.autoCheckIn',
      label: app.translator.trans('ziven-checkin.admin.settings.auto-check-in'),
      type: 'switch',
    })
    .registerSetting({
      setting: 'ziven-forum-checkin.autoCheckInDelay',
      label: app.translator.trans('ziven-checkin.admin.settings.auto-check-in-delay'),
      type: 'number',
    })
    .registerSetting({
      setting: 'ziven-forum-checkin.checkinSuccessPromptType',
      label: app.translator.trans('ziven-checkin.admin.settings.check-in-success-prompt-type'),
      type: 'select',
      options: {
        0: app.translator.trans('ziven-checkin.admin.settings.None'),
        1: app.translator.trans('ziven-checkin.admin.settings.Alert'),
        2: app.translator.trans('ziven-checkin.admin.settings.Modal')
      },
    })
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.check-in-success-prompt-text')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.check-in-success-prompt-example-text')}</div>
          <input type="string" className="FormControl" step="any" bidi={this.setting('ziven-forum-checkin.checkinSuccessPromptText')} />
        </div>
      );
    })
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label>{app.translator.trans('ziven-checkin.admin.settings.check-in-success-prompt-reward-text')}</label>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.reward-money-requirement')}</div>
          <div class="helpText">{app.translator.trans('ziven-checkin.admin.settings.check-in-success-prompt-example-reward-text')}</div>
          <input type="string" className="FormControl" step="any" bidi={this.setting('ziven-forum-checkin.checkinSuccessPromptRewardText')} />
        </div>
      );
    })
    .registerPermission(
      {
        icon: 'fas fa-id-card',
        label: app.translator.trans('ziven-checkin.admin.settings.allow-check-in'),
        permission: 'checkin.allowCheckIn',
      },
      'moderate',
      90
    );
});
