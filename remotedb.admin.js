(function ($, Drupal) {
  Drupal.behaviors.authenticationMethodStatus = {
    attach: function attach(context, settings) {
      var $context = $(context);
      $context.find('#authentication-methods-status-wrapper input.form-checkbox').once('authentication-method-status').each(function () {
        var $checkbox = $(this);

        var $row = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-weight')).closest('tr');

        var $authenticationMethodSettings = $context.find('#' + $checkbox.attr('id').replace(/-status$/, '-settings'));
        var authenticationMethodSettingsTab = $authenticationMethodSettings.data('verticalTab');

        $checkbox.on('click.authenticationMethodUpdate', function () {
          if ($checkbox.is(':checked')) {
            $row.show();
            if (authenticationMethodSettingsTab) {
              authenticationMethodSettingsTab.tabShow().updateSummary();
            } else {
              $authenticationMethodSettings.show();
            }
          } else {
            $row.hide();
            if (authenticationMethodSettingsTab) {
              authenticationMethodSettingsTab.tabHide().updateSummary();
            } else {
              $authenticationMethodSettings.hide();
            }
          }

          Drupal.tableDrag['authentication-method-order'].restripeTable();
        });

        if (authenticationMethodSettingsTab) {
          authenticationMethodSettingsTab.details.drupalSetSummary(function () {
            return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
          });
        }

        $checkbox.triggerHandler('click.authenticationMethodUpdate');
      });
    }
  };
})(jQuery, Drupal);
