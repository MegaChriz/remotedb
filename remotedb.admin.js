(function ($) {

Drupal.behaviors.authenticationMethodStatus = {
  attach: function (context, settings) {
    $('#authentication-methods-status-wrapper input.form-checkbox', context).once('authentication-method-status', function () {
      var $checkbox = $(this);
      // Retrieve the tabledrag row belonging to this authentication-method.
      var $row = $('#' + $checkbox.attr('id').replace(/-status$/, '-weight'), context).closest('tr');
      // Retrieve the vertical tab belonging to this authentication-method.
      var tab = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context).data('verticalTab');

      // Bind click handler to this checkbox to conditionally show and hide the
      // authentication-method's tableDrag row and vertical tab pane.
      $checkbox.bind('click.authenticationMethodUpdate', function () {
        if ($checkbox.is(':checked')) {
          $row.show();
          if (tab) {
            tab.tabShow().updateSummary();
          }
        }
        else {
          $row.hide();
          if (tab) {
            tab.tabHide().updateSummary();
          }
        }
        // Restripe table after toggling visibility of table row.
        Drupal.tableDrag['authentication-method-order'].restripeTable();
      });

      // Attach summary for configurable authentication-methods (only for screen-readers).
      if (tab) {
        tab.fieldset.drupalSetSummary(function (tabContext) {
          return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
        });
      }

      // Trigger our bound click handler to update elements to initial state.
      $checkbox.triggerHandler('click.authenticationMethodUpdate');
    });
  }
};

})(jQuery);
