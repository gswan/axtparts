function windowOpener(thisURL)
{
	var sw = screen.availWidth;
	var sh = screen.availHeight-20;
	if (sw > 900)
		sw = 900;
	NewWindow = window.open(thisURL,"storagebox","toolbar=no,width=640,height=480,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes");
	NewWindow.focus();
	NewWindow.resizeTo(sw, sh);
	NewWindow.moveTo(100,10);
}
function popupOpener(thisURL,windowname,wsize,hsize)
{
  newPopup = window.open(thisURL,windowname,"toolbar=no,width="+wsize+",height="+hsize+",location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes");
  newPopup.focus();
}
function popupOpenerMenus(thisURL,windowname,wsize,hsize)
{
  newPopup = window.open(thisURL,windowname,"toolbar=no,width="+wsize+",height="+hsize+",location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes");
  newPopup.focus();
}
var dirtybit = 0;
function dirtyset()
{
  dirtybit = 1;
}
function dirtyclear()
{
  dirtybit = 0;
}
function frmCheck()
{
  var ok = 1;
  if (ok)
    return true;
  else
    return false;
}
function frmCheckDirty()
{
  if (dirtybit)
  {
    alert("Please Save or Cancel changes");
    return false;
  }
  else
    return true;
}
function deleteCheck()
{
  if (delbit)
  {
    var x = confirm("Warning: Delete action will destroy data. Continue?");
    if (x)
      return true;
    else
      return false;
  }
  else
    return true;
}
var delbit = 0;
function delSet()
{
  delbit = 1;
}
function delClear()
{
  delbit = 0;
}