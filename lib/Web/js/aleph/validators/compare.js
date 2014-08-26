/**
 * This validator checks whether the values of the validating controls are equal to each other.
 *
 * @constructor
 * @this {VCompare}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VCompare = function(el, pom)
{
  VCompare.superclass.constructor.call(this, el, pom);
    
  /**
   * Validates value of a control.
   * The returning result depends on the validator's mode:
   * AND - it returns TRUE if all controls values are equal to each other and FALSE otherwise.
   * OR - it returns TRUE if value of one control is equal to the value of at least one other control and FALSE otherwise.
   * XOR - it returns TRUE if exactly two controls have equal values and FALSE otherwise. 
   *
   * @this {VCompare}
   * @return {boolean}
   */
  this.validate = function(value)
  {
    var ctrls = this.getControls();
    this.result = {};
    if (ctrls.length == 0) 
    {
      this.setState(true);
      return true;
    }
    var i, j, flag, value1, value2, ctrl1, ctrl2;
    switch (this.getMode())
    {
      default:
      case 'AND':
        flag = true;
        for (i = 0; i < ctrls.length - 1; i++) 
        {
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2) this.result[ctrls[i]] = this.result[ctrls[j]] = true;
            else this.result[ctrls[i]] = this.result[ctrls[j]] = flag = false;
          }
        }
        break;
      case 'OR':
        flag = false;
        for (i = 0; i < ctrls.length - 1; i++)
        {
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2) this.result[ctrls[i]] = this.result[ctrls[j]] = flag = true;
            else this.result[ctrls[i]] = this.result[ctrls[j]] = false; 
          }
        }
        break;
      case 'XOR':
        var n = 0;
        for (i = 0; i < ctrls.length - 1; i++)
        {
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2)
            {
              this.result[ctrls[i]] = this.result[ctrls[j]] = n < 1;
              n++;
            }
            else this.result[ctrls[i]] = this.result[ctrls[j]] = false;
          }
        }
        flag = n == 1;
        break;
    }
    this.setState(flag);
    return flag;
  };
  
  /**
   * Returns the given value in lower case if attributes caseInsensitive is defined. Otherwise it returns the given value without any changes.
   *
   * @this {VCompare}
   * @param {mixed} value - the validating value.
   * @return {mixed}
   */
  this.check = function(value)
  {
    return this.el.attr('data-caseinsensitive') == '1' ? (value + '').toLowerCase() : value;
  };
};

$pom.registerValidator('vcompare', VCompare);  