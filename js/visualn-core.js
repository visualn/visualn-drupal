(function ($, Drupal) {
  // adapters, drawers and mapperes register themselves to be triggered when needed
// managers don't need to register themselves, they should be triggered at visualnCoreProcessed event
  Drupal.visualnData = { drawings : {}, adapters : {}, mappers : {}, drawers : {}, handlerItems : {} };

  // @todo: this would work only for newer versions of browsers
  // https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
  //var event = new CustomEvent('visualnCoreProcessed', { 'detail': elem.dataset.time });
  //var event = new CustomEvent('visualnCoreProcessed', { 'detail': elem.dataset.time });
  //var event = new CustomEvent('visualnCoreProcessed');
//Listen to your custom event
/*
window.addEventListener('visualnCoreProcessed', function (e) {
    console.log('printer state changed', e.detail);
});
*/

  // @todo: create a custom visualnCoreProcessed event that would trigger other (especially managers) behaviours
  Drupal.behaviors.visualnCoreBehaviour = {
    attach: function (context, settings) {
      $(context).find('body').once('visualn-core').each(function () {
        Drupal.visualnData.drawings = settings.visualn.drawings;
        // store a reference between handlers (drawers, mappers, adapters, managers) and provided drawings
        Drupal.visualnData.handlerItems = settings.visualn.handlerItems;
        //var arr = Object.keys(obj).map(function (key) { return obj[key]; });
        console.log(settings);
var event = new CustomEvent('visualnCoreProcessed', { 'detail': settings });
//Listen to your custom event
window.addEventListener('visualnCoreProcessed', function (e) {
    console.log('printer state changed', settings);
});
window.dispatchEvent(event);
      });
    }
  };
})(jQuery, Drupal);

