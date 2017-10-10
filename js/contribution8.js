CRM.$(function($) {

  var becMembership = CRM.$('#price_3');
  var bcaMembership = CRM.$('#price_4');
  becMembership.select2().enable(false);
  bcaMembership.select2().enable(false);
  becMembership.select2({width: '75%'});
  bcaMembership.select2({width: '75%'});
});