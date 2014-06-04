var Aleph = (function(undefined)
{
  var Aleph = function()
  {
    this.pom = new POM();
    this.ajax = new Ajax();
  };
  
  $(function()
  {
    POM.prototype.setVS();
  });
  
  return Aleph;
})();

var $a = new Aleph();