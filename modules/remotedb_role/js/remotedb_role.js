/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($, Drupal) {
  Drupal.behaviors.remoteDbRoleFieldsetSummaries = {
    attach: function (context, settings) {
      var $context = $(context);

      $.each(settings.remotedb_roles, function (rid, css_rid) {
        $context.find('#edit-roles-' + css_rid).drupalSetSummary(function(context) {
          var remotedb_radio_value = $(context).find('input[name="roles[' + rid + '][status]"]:checked').val();
          if (remotedb_radio_value == 1) {
            return '<img srcset="' + settings.path.baseUrl + settings.remotedb_role_image_enabled + '" /> ' + Drupal.t('Enabled');
          }
          else {
            return '<img srcset="' + settings.path.baseUrl + settings.remotedb_role_image_disabled + '" /> ' + Drupal.t('Disabled');
          }
        });
      });
    }
  };

})(jQuery, Drupal);
