(function ($, Drupal) {
  Drupal.behaviors.planDeEstudiosBlockToggle = {
    attach: function (context, settings) {
      // Usar un método más compatible que no dependa estrictamente de la librería 'once'
      $('.toggle-block-trigger', context).each(function() {
        var $trigger = $(this);
        if (!$trigger.hasClass('processed')) {
          $trigger.addClass('processed');
          $trigger.on('click', function () {
            var $block = $(this).closest('.block-plan-de-estudios');
            var $wrapper = $block.find('.plans-collapsible-wrapper');
            
            $block.toggleClass('is-expanded');
            
            if ($block.hasClass('is-expanded')) {
              var scrollHeight = $wrapper.prop('scrollHeight');
              $wrapper.css('max-height', scrollHeight + 'px');
            } else {
              $wrapper.css('max-height', '0');
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal);
