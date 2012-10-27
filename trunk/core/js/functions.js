/* ====================== Mouseover/Animation ======================= */
function anim2(imgObj,url) {
	imgObj.src=url;
}

function anim(name,type) {

	if (type==0)
		document.images[name].src="/core/images/"+name+".gif";
	if (type==1)
		document.images[name].src="/core/images/"+name+"_over.gif";
	if (type==2)
		document.images[name].src="/core/images/"+name+"_down.gif";
}

/* ================= For Player Form: Checks All or None ======== */

function checkAll(cbox,formObj) {
	var i=0;
	if (cbox.checked==true)
		cbox.checked==false;
	else
		cbox.checked==true;
	while (formObj.elements[i]!=null) {
		formObj.elements[i].checked=cbox.checked;
		i++;
	}
}
/* ================== For forms: Checks if Enter key is pressed ========== */

function checkEvent(formObj){
     var key = -1 ;
     var shift ;

     key = event.keyCode ;
     shift = event.shiftKey ;

     if (!shift && key == 13)
     {
          formObj.submit() ;
     }
}
/* ================= To show/hide a block of text ==================*/
function show(block) {
	theBlock=document.getElementById(block);
//	if (theBlock!=null) {
		if (theBlock.style.display=="none") {
			theBlock.style.display="block";
		}
		else {
			theBlock.style.display="none";
		}
//	}
}

/* ============== */
function emailSelectCheck(emailObj,inputObj) {
	if (inputObj.value.indexOf(emailObj.innerHTML)!=-1) {
		emailObj.style.fontWeight='bold';
	}
	else {
		emailObj.style.fontWeight='normal';
	}
}

/* ================ */
function bolden(type,prefix,count) {
	
	for (i=0;i<count;i++) {
		document.getElementById(prefix+i).style.fontWeight=type;
	}
}

/*=================== Confirming Button Click ===================== */
function confirmClick(message,href) {
	if (confirm(message))
 	{	
		location.href=href;
	} 
	else 
	{
		return false;
	}
}