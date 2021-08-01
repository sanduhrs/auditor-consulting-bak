(function ($, Drupal) {
  Drupal.behaviors.siwecosCircleProgress = {
    attach: function (context, settings) {
      $('.siwecos-circle__circle', context).once('siwecos-circle-progress ').each(function () {
        $(this).circleProgress();
      });
    }
  }
} (jQuery, Drupal));
