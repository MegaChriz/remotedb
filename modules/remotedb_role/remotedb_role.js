/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

Drupal.behaviors.remoteDbRoleFieldsetSummaries = {
  attach: function (context) {
    for(var i in Drupal.settings.roles) {
      var remotedb_fieldset = 'fieldset#edit-remotedb-role-' + i;

      $(remotedb_fieldset, context).drupalSetSummary(function(context) {
        var role_id = context.id;
        role_id = role_id.replace('edit-remotedb-role-', '');
        var remotedb_radio_value = $('input[name="remotedb_role_' + role_id + '_active"]:checked').val();
        if (remotedb_radio_value == 1) {
          return '<img src="' + Drupal.settings.basePath + 'misc/message-16-ok.png" />' + Drupal.t('Enabled');
          //return Drupal.t('Enabled');
        }
        else {
          return '<img src="' + Drupal.settings.basePath + 'misc/message-16-error.png" />' + Drupal.t('Disabled');
          //return Drupal.t('Disabled');
        }
      });
    }
  }
};

})(jQuery);
