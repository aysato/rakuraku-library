 jQuery(function(){ 
		  $("li.librarycat").children("ul").show();
		  $("ul.children").hide();
		  $("li.current-cat").show();
		  $("li.current-cat").children("ul.children").show();
		  $("li.current-cat").parents().show();	
});
