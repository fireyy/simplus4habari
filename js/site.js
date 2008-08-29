function searchSlide_toggle () {
	var positons = $("search").getCoordinates();
	var mixheight = $("search-toggle").offsetHeight;
	var searchSlide_fx = new Fx.Tween("search");
	if(positons["top"] < 0){
		searchSlide_fx.start("top",0);
	}else{
		searchSlide_fx.start("top",0-(positons["bottom"]-mixheight));
	}
}
window.addEvent('domready', function(){
	//new SmoothScroll();
	new Element("div",{
		id:"search-toggle",
		"events":{
			"click":function(){
				searchSlide_toggle();
			}
		}
	}).inject("search");
	
	searchSlide_toggle();
	
});