function showAvexStatusTableMain(){
	var seconds=100;
	jQuery('#avex-status-table tr').each(function(index,val){
			if(jQuery(val).hasClass('avex-hidden')){
				setTimeout(function(){showAvexStatusTableRow(index);},seconds);
			}
			seconds=seconds+100;
		});
}
function showAvexStatusTableRow(the_index){
	jQuery('#avex-status-table tr').each(function(index,val){
		if(the_index==index){
			jQuery(val).fadeIn("fast");
		}
	});
}
function validateHbEmail(email)
{
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
    {
        return (true)
    }
    return (false)
}
