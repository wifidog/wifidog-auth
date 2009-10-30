function focusElement(formName, elemName) {
  var elem = document.forms[formName].elements[elemName];
  elem.focus();
  elem.select();
}

function isEmpty(elem) {
  var str = elem.value;
  var re = /./;
  if(!str.match(re)) {
	focusElement(elem.form.name, elem.name);
	return true;
  } else {
	return false;
  }
}

function isNotEmpty(elem) {
  return !isEmpty(elem);
}

function isNumber(elem) {
  var str = elem.value;
  var re = /^[-]?\d*\.?\d*$/;
  str = str.toString();
  if (!str.match(re)) {
	focusElement(elem.form.name, elem.name);
	return false;
  }
  return true;
}

function isValidUsername(elem) {
  var str = elem.value;
  var re = /^[.0-9a-zA-Z_-]+$/;
  if (!str.match(re)) {
	focusElement(elem.form.name, elem.name);
	return false;
  } else {
	return true;
  }
}

function isValidEmail(elem) {
  var str = elem.value;
  //var re = /^[\w-+]+(\.[\w+-]+)*@([\w-]+\.)+[a-zA-Z]{2,7}$/;
  var re = /^[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/;
  if (!str.match(re)) {
	focusElement(elem.form.name, elem.name);
	return false;
  } else {
	return true;
  }
}

function isValidPassword(elem) {
  var str = elem.value;
  var re = /^[0-9a-zA-Z]{6,}$/;
  if (!str.match(re)) {
	focusElement(elem.form.name, elem.name);
	return false;
  } else {
	return true;
  }
}

function focusNext(form, elemName, evt) {
  evt = evt ? evt : event;
  var charCode = evt.charCode ? evt.charCode : ( evt.which ? evt.which : evt.keyCode );
  if (charCode == 13 || charCode == 3) {
	form.elements[elemName].focus();
	return false;
  }
  return true;
}
