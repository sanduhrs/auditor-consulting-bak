(function ($, Drupal) {
  Drupal.behaviors.siwecos = {
    attach: function (context, settings) {
      $('.siwecos__circle-progress', context).once('siwecos-circle-progress ').each(function () {
        $(this).circleProgress();
      });
    }
  }
} (jQuery, Drupal));
