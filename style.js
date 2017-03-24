 jQuery(function(){ 
		  $("li.librarycat").children("ul").show();	//表示
		  $("ul.children").hide();	//非表示
		  $("li.current-cat").show();		//表示
		  $("li.current-cat").children("ul.children").show();		//表示
		  $("li.current-cat").parents().show();		//表示
});