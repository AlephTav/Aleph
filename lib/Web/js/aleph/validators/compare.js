var VCompare = function(el, pom)
{
  VCompare.superclass.constructor.call(this, el, pom);
    
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
  
  this.check = function(value)
  {
    return this.el.attr('data-caseinsensitive') == '1' ? (value + '').toLowerCase() : value;
  };
};

$pom.registerValidator('vcompare', VCompare);  