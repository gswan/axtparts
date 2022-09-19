var hD="0123456789ABCDEF";

function d2h(d) 
{
	var h = hD.substr(d&15,1);
	while ((d>15)||(d<-15))
	{
		d>>>=4;
		h=hD.substr(d&15,1)+h;
	}
	return h;
}

function h2d(h)
{
	var d=parseInt(h,16);
	return d;
}

function updateRolebits()
{
	var maskval;
	
	maskval=h2d(document.forms[0].rolemask.value);
	
	if (maskval & 0x00000001)
		document.forms[0].rb0.checked=true;
	else
		document.forms[0].rb0.checked=false;
	if (maskval & 0x00000002)
		document.forms[0].rb1.checked=true;
	else
		document.forms[0].rb1.checked=false;
	if (maskval & 0x00000004)
		document.forms[0].rb2.checked=true;
	else
		document.forms[0].rb2.checked=false;
	if (maskval & 0x00000008)
		document.forms[0].rb3.checked=true;
	else
		document.forms[0].rb3.checked=false;
	if (maskval & 0x00000010)
		document.forms[0].rb4.checked=true;
	else
		document.forms[0].rb4.checked=false;
	if (maskval & 0x00000020)
		document.forms[0].rb5.checked=true;
	else
		document.forms[0].rb5.checked=false;
	if (maskval & 0x00000040)
		document.forms[0].rb6.checked=true;
	else
		document.forms[0].rb6.checked=false;
	if (maskval & 0x00000080)
		document.forms[0].rb7.checked=true;
	else
		document.forms[0].rb7.checked=false;
	if (maskval & 0x00000100)
		document.forms[0].rb8.checked=true;
	else
		document.forms[0].rb8.checked=false;
	if (maskval & 0x00000200)
		document.forms[0].rb9.checked=true;
	else
		document.forms[0].rb9.checked=false;
	if (maskval & 0x00000400)
		document.forms[0].rb10.checked=true;
	else
		document.forms[0].rb10.checked=false;
	if (maskval & 0x00000800)
		document.forms[0].rb11.checked=true;
	else
		document.forms[0].rb11.checked=false;
	if (maskval & 0x00001000)
		document.forms[0].rb12.checked=true;
	else
		document.forms[0].rb12.checked=false;
	if (maskval & 0x00002000)
		document.forms[0].rb13.checked=true;
	else
		document.forms[0].rb13.checked=false;
	if (maskval & 0x00004000)
		document.forms[0].rb14.checked=true;
	else
		document.forms[0].rb14.checked=false;
	if (maskval & 0x00008000)
		document.forms[0].rb15.checked=true;
	else
		document.forms[0].rb15.checked=false;
	if (maskval & 0x00010000)
		document.forms[0].rb16.checked=true;
	else
		document.forms[0].rb16.checked=false;
	if (maskval & 0x00020000)
		document.forms[0].rb17.checked=true;
	else
		document.forms[0].rb17.checked=false;
	if (maskval & 0x00040000)
		document.forms[0].rb18.checked=true;
	else
		document.forms[0].rb18.checked=false;
	if (maskval & 0x00080000)
		document.forms[0].rb19.checked=true;
	else
		document.forms[0].rb19.checked=false;
	if (maskval & 0x00100000)
		document.forms[0].rb20.checked=true;
	else
		document.forms[0].rb20.checked=false;
	if (maskval & 0x00200000)
		document.forms[0].rb21.checked=true;
	else
		document.forms[0].rb21.checked=false;
	if (maskval & 0x00400000)
		document.forms[0].rb22.checked=true;
	else
		document.forms[0].rb22.checked=false;
	if (maskval & 0x00800000)
		document.forms[0].rb23.checked=true;
	else
		document.forms[0].rb23.checked=false;
	if (maskval & 0x01000000)
		document.forms[0].rb24.checked=true;
	else
		document.forms[0].rb24.checked=false;
	if (maskval & 0x02000000)
		document.forms[0].rb25.checked=true;
	else
		document.forms[0].rb25.checked=false;
	if (maskval & 0x04000000)
		document.forms[0].rb26.checked=true;
	else
		document.forms[0].rb26.checked=false;
	if (maskval & 0x08000000)
		document.forms[0].rb27.checked=true;
	else
		document.forms[0].rb27.checked=false;
	if (maskval & 0x10000000)
		document.forms[0].rb28.checked=true;
	else
		document.forms[0].rb28.checked=false;
	if (maskval & 0x20000000)
		document.forms[0].rb29.checked=true;
	else
		document.forms[0].rb29.checked=false;
	if (maskval & 0x40000000)
		document.forms[0].rb30.checked=true;
	else
		document.forms[0].rb30.checked=false;
	if (maskval & 0x80000000)
		document.forms[0].rb31.checked=true;
	else
		document.forms[0].rb31.checked=false;
}

function updateRolemask()
{
	var i;
	var maskval;
	var s;
	var l;
	var z;
	
	// updates the rolemask value in the rolemask element
	// with the contents calculated from the checkboxes rb0 .. rb31
	maskval=0;
	if (document.forms[0].rb0.checked)
		maskval|=0x00000001;
	if (document.forms[0].rb1.checked)
		maskval|=0x00000002;
	if (document.forms[0].rb2.checked)
		maskval|=0x00000004;
	if (document.forms[0].rb3.checked)
		maskval|=0x00000008;
	if (document.forms[0].rb4.checked)
		maskval|=0x00000010;
	if (document.forms[0].rb5.checked)
		maskval|=0x00000020;
	if (document.forms[0].rb6.checked)
		maskval|=0x00000040;
	if (document.forms[0].rb7.checked)
		maskval|=0x00000080;
	if (document.forms[0].rb8.checked)
		maskval|=0x00000100;
	if (document.forms[0].rb9.checked)
		maskval|=0x00000200;
	if (document.forms[0].rb10.checked)
		maskval|=0x00000400;
	if (document.forms[0].rb11.checked)
		maskval|=0x00000800;
	if (document.forms[0].rb12.checked)
		maskval|=0x00001000;
	if (document.forms[0].rb13.checked)
		maskval|=0x00002000;
	if (document.forms[0].rb14.checked)
		maskval|=0x00004000;
	if (document.forms[0].rb15.checked)
		maskval|=0x00008000;
	if (document.forms[0].rb16.checked)
		maskval|=0x00010000;
	if (document.forms[0].rb17.checked)
		maskval|=0x00020000;
	if (document.forms[0].rb18.checked)
		maskval|=0x00040000;
	if (document.forms[0].rb19.checked)
		maskval|=0x00080000;
	if (document.forms[0].rb20.checked)
		maskval|=0x00100000;
	if (document.forms[0].rb21.checked)
		maskval|=0x00200000;
	if (document.forms[0].rb22.checked)
		maskval|=0x00400000;
	if (document.forms[0].rb23.checked)
		maskval|=0x00800000;
	if (document.forms[0].rb24.checked)
		maskval|=0x01000000;
	if (document.forms[0].rb25.checked)
		maskval|=0x02000000;
	if (document.forms[0].rb26.checked)
		maskval|=0x04000000;
	if (document.forms[0].rb27.checked)
		maskval|=0x08000000;
	if (document.forms[0].rb28.checked)
		maskval|=0x10000000;
	if (document.forms[0].rb29.checked)
		maskval|=0x20000000;
	if (document.forms[0].rb30.checked)
		maskval|=0x40000000;
	if (document.forms[0].rb31.checked)
		maskval|=0x80000000;
	
	s=d2h(maskval);
	l=s.length;
	z="";
	for (i=l; i<8; i++)
		z=z+"0";
	document.forms[0].rolemask.value="0x"+z+s;
}
