var hs={lang:{creditsText:'Powered by <i>Highslide JS</i>',creditsTitle:'',closeTitle:'',moveTitle:''},graphicsDir:'hs/',restoreCursor:'zoomout.cur',expandSteps:10,expandDuration:250,restoreSteps:10,restoreDuration:250,marginLeft:15,marginRight:15,marginTop:15,marginBottom:15,zIndexCounter:1001,loadingOpacity:0.75,outlineWhileAnimating:2,outlineStartOffset:3,fullExpandPosition:'bottom right',fullExpandOpacity:1,padToMinWidth:true,showCredits:false,creditsHref:'http://vikjavev.no/highslide/',allowWidthReduction:false,allowHeightReduction:true,preserveContent:true,dragByHeading:true,minWidth:200,minHeight:200,allowSizeReduction:true,outlineType:'drop-shadow',wrapperClassName:'highslide-wrapper',skin:{contentWrapper:'<div class="highslide-header"><ul>'+'<li class="highslide-previous">'+'<a href="#" title="{hs.lang.previousTitle}" onclick="return hs.previous(this)">'+'<span>{hs.lang.previousText}</span></a>'+'</li>'+'<li class="highslide-next">'+'<a href="#" title="{hs.lang.nextTitle}" onclick="return hs.next(this)">'+'<span>{hs.lang.nextText}</span></a>'+'</li>'+'<li class="highslide-move">'+'<a href="#" title="{hs.lang.moveTitle}" onclick="return false">'+'<span>{hs.lang.moveText}</span></a>'+'</li>'+'<li class="highslide-close">'+'<a href="#" title="{hs.lang.closeTitle}" onclick="return hs.close(this)">'+'<span>{hs.lang.closeText}</span></a>'+'</li>'+'</ul></div>'+'<div class="highslide-body"></div>'+'<div class="highslide-footer"><div>'+'<span class="highslide-resize" title="{hs.lang.resizeTitle}"><span></span></span>'+'</div></div>'},expanders:[],overrides:['allowSizeReduction','outlineType','outlineWhileAnimating','captionId','captionText','captionEval','captionOverlay','headingId','headingText','headingEval','headingOverlay','dragByHeading','contentId','width','height','allowWidthReduction','allowHeightReduction','preserveContent','maincontentId','maincontentText','maincontentEval','wrapperClassName','minWidth','minHeight','maxWidth','maxHeight','slideshowGroup','easing','easingClose','fadeInOut','src'],overlays:[],idCounter:0,oPos:{x:['leftpanel','left','center','right','rightpanel'],y:['above','top','middle','bottom','below']},mouse:{},headingOverlay:{},captionOverlay:{},faders:[],pendingOutlines:{},sleeping:[],clones:{},ie:(document.all&&!window.opera),safari:/Safari/.test(navigator.userAgent),geckoMac:/Macintosh.+rv:1\.[0-8].+Gecko/.test(navigator.userAgent),$:function(a){return document.getElementById(a)},push:function(a,b){a[a.length]=b},createElement:function(a,b,c,d,e){var f=document.createElement(a);if(b)hs.setAttribs(f,b);if(e)hs.setStyles(f,{padding:0,border:'none',margin:0});if(c)hs.setStyles(f,c);if(d)d.appendChild(f);return f},setAttribs:function(a,b){for(var x in b)a[x]=b[x]},setStyles:function(a,b){for(var x in b){if(hs.ie&&x=='opacity'){if(b[x]>0.99)a.style.removeAttribute('filter');else a.style.filter='alpha(opacity='+(b[x]*100)+')'}else a.style[x]=b[x]}},ieVersion:function(){var a=navigator.appVersion.split("MSIE");return a[1]?parseFloat(a[1]):null},getPageSize:function(){var a=document.compatMode&&document.compatMode!='BackCompat'?document.documentElement:document.body;var b=hs.ie?a.clientWidth:(document.documentElement.clientWidth||self.innerWidth),height=hs.ie?a.clientHeight:self.innerHeight;return{width:b,height:height,scrollLeft:hs.ie?a.scrollLeft:pageXOffset,scrollTop:hs.ie?a.scrollTop:pageYOffset}},getPosition:function(a){var p={x:a.offsetLeft,y:a.offsetTop};while(a.offsetParent){a=a.offsetParent;p.x+=a.offsetLeft;p.y+=a.offsetTop;if(a!=document.body&&a!=document.documentElement){p.x-=a.scrollLeft;p.y-=a.scrollTop}}return p},expand:function(a,b,c){if(a.getParams)return b;try{new hs.Expander(a,b,c);return false}catch(e){return true}},htmlExpand:function(a,b,c){if(a.getParams)return b;for(var i=0;i<hs.sleeping.length;i++){if(hs.sleeping[i]&&hs.sleeping[i].a==a){hs.sleeping[i].awake();hs.sleeping[i]=null;return false}}try{hs.hasHtmlexpanders=true;new hs.Expander(a,b,c,'html');return false}catch(e){return true}},getSelfRendered:function(){return hs.createElement('div',{className:'highslide-html-content windowbg2',innerHTML:hs.replaceLang(hs.skin.contentWrapper)})},getElementByClass:function(a,b,c){var d=a.getElementsByTagName(b);for(var i=0;i<d.length;i++){if((new RegExp(c)).test(d[i].className)){return d[i]}}return null},replaceLang:function(s){s=s.replace(/\s/g,' ');var a=/{hs\.lang\.([^}]+)\}/g,matches=s.match(a),lang;for(var i=0;i<matches.length;i++){lang=matches[i].replace(a,"$1");if(typeof hs.lang[lang]!='undefined')s=s.replace(matches[i],hs.lang[lang])}return s},getParam:function(a,b){a.getParams=a.onclick;var p=a.getParams?a.getParams():null;a.getParams=null;return(p&&typeof p[b]!='undefined')?p[b]:(typeof hs[b]!='undefined'?hs[b]:null)},getSrc:function(a){var b=hs.getParam(a,'src');if(b)return b;return a.href},getNode:function(b){var c=hs.$(b),clone=hs.clones[b],a={};if(!c&&!clone)return null;if(!clone){clone=c.cloneNode(true);clone.id='';hs.clones[b]=clone;return c}else{return clone.cloneNode(true)}},discardElement:function(d){hs.garbageBin.appendChild(d);hs.garbageBin.innerHTML=''},registerOverlay:function(a){hs.push(hs.overlays,a)},getWrapperKey:function(a){var b,re=/^highslide-wrapper-([0-9]+)$/;b=a;while(b.parentNode){if(b.id&&re.test(b.id))return b.id.replace(re,"$1");b=b.parentNode}b=a;while(b.parentNode){if(b.tagName&&hs.isHsAnchor(b)){for(var c=0;c<hs.expanders.length;c++){var d=hs.expanders[c];if(d&&d.a==b)return c}}b=b.parentNode}return null},getExpander:function(a){if(typeof a=='undefined')return hs.expanders[hs.focusKey]||null;if(typeof a=='number')return hs.expanders[a]||null;if(typeof a=='string')a=hs.$(a);return hs.expanders[hs.getWrapperKey(a)]||null},isHsAnchor:function(a){return(a.onclick&&a.onclick.toString().replace(/\s/g,' ').match(/hs.(htmlE|e)xpand/))},mouseClickHandler:function(e){if(!e)e=window.event;if(e.button>1)return true;if(!e.target)e.target=e.srcElement;var a=e.target;while(a.parentNode&&!(/highslide-(image|move|html|resize)/.test(a.className))){a=a.parentNode}var b=hs.getExpander(a);if(b&&(b.isClosing||!b.isExpanded))return true;if(b&&e.type=='mousedown'){if(e.target.form)return true;var c=a.className.match(/highslide-(image|move|resize)/);if(c){hs.dragArgs={exp:b,type:c[1],left:b.x.min,width:b.x.span,top:b.y.min,height:b.y.span,clickX:e.clientX,clickY:e.clientY};hs.addEventListener(document,'mousemove',hs.dragHandler);if(e.preventDefault)e.preventDefault();if(/highslide-(image|html)-blur/.test(b.content.className)){b.focus();hs.hasFocused=true}return false}else if(/highslide-html/.test(a.className)&&hs.focusKey!=b.key){b.focus();b.redoShowHide()}}else if(e.type=='mouseup'){hs.removeEventListener(document,'mousemove',hs.dragHandler);if(hs.dragArgs){if(hs.dragArgs.type=='image')hs.dragArgs.exp.content.style.cursor=hs.styleRestoreCursor;var d=hs.dragArgs.hasDragged;if(!d&&!hs.hasFocused&&!/(move|resize)/.test(hs.dragArgs.type)){b.close()}else if(d||(!d&&hs.hasHtmlexpanders)){hs.dragArgs.exp.redoShowHide()}hs.hasFocused=false;hs.dragArgs=null}else if(/highslide-image-blur/.test(a.className)){a.style.cursor=hs.styleRestoreCursor}}return false},dragHandler:function(e){if(!hs.dragArgs)return true;if(!e)e=window.event;var a=hs.dragArgs,exp=a.exp;a.dX=e.clientX-a.clickX;a.dY=e.clientY-a.clickY;var b=Math.sqrt(Math.pow(a.dX,2)+Math.pow(a.dY,2));if(!a.hasDragged)a.hasDragged=(a.type!='image'&&b>0)||(b>(hs.dragSensitivity||5));if(a.hasDragged&&e.clientX>5&&e.clientY>5){if(a.type=='resize')exp.resize(a);else exp.move(a)}return false},wrapperMouseHandler:function(e){try{if(!e)e=window.event;var a=/mouseover/i.test(e.type);if(!e.target)e.target=e.srcElement;if(hs.ie)e.relatedTarget=a?e.fromElement:e.toElement;var b=hs.getExpander(e.target);if(!b.isExpanded)return;if(!b||!e.relatedTarget||hs.getExpander(e.relatedTarget)==b||hs.dragArgs)return;for(var i=0;i<b.overlays.length;i++){var o=hs.$('hsId'+b.overlays[i]);if(o&&o.hideOnMouseOut){var c=a?0:o.opacity,to=a?o.opacity:0;hs.fade(o,c,to)}}}catch(e){}},addEventListener:function(a,b,c){try{a.addEventListener(b,c,false)}catch(e){try{a.detachEvent('on'+b,c);a.attachEvent('on'+b,c)}catch(e){a['on'+b]=c}}},removeEventListener:function(a,b,c){try{a.removeEventListener(b,c,false)}catch(e){try{a.detachEvent('on'+b,c)}catch(e){a['on'+b]=null}}},init:function(){if(!hs.container){hs.container=hs.createElement('div',null,{position:'absolute',left:0,top:0,width:'100%',zIndex:hs.zIndexCounter},document.body,true);hs.loading=hs.createElement('a',{className:'highslide-loading',title:hs.lang.loadingTitle,innerHTML:hs.lang.loadingText,href:'javascript:;'},{position:'absolute',top:'-9999px',opacity:hs.loadingOpacity,zIndex:1},hs.container);hs.garbageBin=hs.createElement('div',null,{display:'none'},hs.container);hs.clearing=hs.createElement('div',null,{clear:'both',paddingTop:'1px'},null,true);Math.linearTween=function(t,b,c,d){return c*t/d+b};Math.easeInQuad=function(t,b,c,d){return c*(t/=d)*t+b};for(var x in hs.langDefaults){if(typeof hs[x]!='undefined')hs.lang[x]=hs[x];else if(typeof hs.lang[x]=='undefined'&&typeof hs.langDefaults[x]!='undefined')hs.lang[x]=hs.langDefaults[x]}}},domReady:function(){hs.isDomReady=true;if(hs.onDomReady)hs.onDomReady()},fade:function(a,o,b,c,i,d){if(typeof i=='undefined'){if(typeof c!='number')c=250;if(c<25){hs.setStyles(a,{opacity:b});return}i=hs.faders.length;d=b>o?1:-1;var e=(25/(c-c%25))*Math.abs(o-b)}o=parseFloat(o);var f=(a.fade===0||a.fade===false||(a.fade==2&&hs.ie));a.style.visibility=((f?b:o)<=0)?'hidden':'visible';if(f||o<0||(d==1&&o>b))return;if(a.fading&&a.fading.i!=i){clearTimeout(hs.faders[a.fading.i]);o=a.fading.o}a.fading={i:i,o:o,step:(e||a.fading.step)};a.style.visibility=(o<=0)?'hidden':'visible';hs.setStyles(a,{opacity:o});hs.faders[i]=setTimeout(function(){hs.fade(a,o+a.fading.step*d,b,null,i,d)},25)},close:function(a){var b=hs.getExpander(a);if(b)b.close();return false}};hs.Outline=function(a,b){this.onLoad=b;this.outlineType=a;var v=hs.ieVersion(),tr;this.hasAlphaImageLoader=hs.ie&&v>=5.5&&v<7;if(!a){if(b)b();return}hs.init();this.table=hs.createElement('table',{cellSpacing:0},{visibility:'hidden',position:'absolute',borderCollapse:'collapse'},hs.container,true);var c=hs.createElement('tbody',null,null,this.table,1);this.td=[];for(var i=0;i<=8;i++){if(i%3==0)tr=hs.createElement('tr',null,{height:'auto'},c,true);this.td[i]=hs.createElement('td',null,null,tr,true);var d=i!=4?{lineHeight:0,fontSize:0}:{position:'relative'};hs.setStyles(this.td[i],d)}this.td[4].className=a;this.preloadGraphic()};hs.Outline.prototype={preloadGraphic:function(){var a=hs.graphicsDir+(hs.outlinesDir||"outlines/")+this.outlineType+".png";var b=hs.safari?hs.container:null;this.graphic=hs.createElement('img',null,{position:'absolute',left:'-9999px',top:'-9999px'},b,true);var c=this;this.graphic.onload=function(){c.onGraphicLoad()};this.graphic.src=a},onGraphicLoad:function(){var o=this.offset=this.graphic.width/4,pos=[[0,0],[0,-4],[-2,0],[0,-8],0,[-2,-8],[0,-2],[0,-6],[-2,-2]],dim={height:(2*o)+'px',width:(2*o)+'px'};hs.discardElement(this.graphic);for(var i=0;i<=8;i++){if(pos[i]){if(this.hasAlphaImageLoader){var w=(i==1||i==7)?'100%':this.graphic.width+'px';var a=hs.createElement('div',null,{width:'100%',height:'100%',position:'relative',overflow:'hidden'},this.td[i],true);hs.createElement('div',null,{filter:"progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale, src='"+this.graphic.src+"')",position:'absolute',width:w,height:this.graphic.height+'px',left:(pos[i][0]*o)+'px',top:(pos[i][1]*o)+'px'},a,true)}else{hs.setStyles(this.td[i],{background:'url('+this.graphic.src+') '+(pos[i][0]*o)+'px '+(pos[i][1]*o)+'px'})}if(window.opera&&(i==3||i==5))hs.createElement('div',null,dim,this.td[i],true);hs.setStyles(this.td[i],dim)}}if(hs.pendingOutlines[this.outlineType])hs.pendingOutlines[this.outlineType].destroy();hs.pendingOutlines[this.outlineType]=this;if(this.onLoad)this.onLoad()},setPosition:function(a,b,c){b=b||{x:a.x.min,y:a.y.min,w:a.x.span+a.x.p1+a.x.p2,h:a.y.span+a.y.p1+a.y.p2};if(c)this.table.style.visibility=(b.h>=4*this.offset)?'visible':'hidden';hs.setStyles(this.table,{left:(b.x-this.offset)+'px',top:(b.y-this.offset)+'px',width:(b.w+2*(a.x.cb+this.offset))+'px'});b.w+=2*(a.x.cb-this.offset);b.h+=+2*(a.y.cb-this.offset);hs.setStyles(this.td[4],{width:b.w>=0?b.w+'px':0,height:b.h>=0?b.h+'px':0});if(this.hasAlphaImageLoader)this.td[3].style.height=this.td[5].style.height=this.td[4].style.height},destroy:function(a){if(a)this.table.style.visibility='hidden';else hs.discardElement(this.table)}};hs.Expander=function(a,b,c,d){if(document.readyState&&hs.ie&&!hs.isDomReady){hs.onDomReady=function(){new hs.Expander(a,b,c,d)};return}this.a=a;this.custom=c;this.contentType=d||'image';this.isHtml=(d=='html');this.isImage=!this.isHtml;this.overlays=[];hs.init();var e=this.key=hs.expanders.length;for(var i=0;i<hs.overrides.length;i++){var f=hs.overrides[i];this[f]=b&&typeof b[f]!='undefined'?b[f]:hs[f]}if(!this.src)this.src=a.href;var g=(b&&b.thumbnailId)?hs.$(b.thumbnailId):a;g=this.thumb=g.getElementsByTagName('img')[0]||g;this.thumbsUserSetId=g.id||a.id;for(var i=0;i<hs.expanders.length;i++){if(hs.expanders[i]&&hs.expanders[i].a==a){hs.expanders[i].focus();return false}}for(var i=0;i<hs.expanders.length;i++){if(hs.expanders[i]&&hs.expanders[i].thumb!=g&&!hs.expanders[i].onLoadStarted){hs.expanders[i].cancelLoading()}}hs.expanders[this.key]=this;if(hs.expanders[e-1])hs.expanders[e-1].close();if(typeof hs.focusKey!='undefined'&&hs.expanders[hs.focusKey])hs.expanders[hs.focusKey].close();var h=hs.getPosition(g);var x=this.x={};x.t=g.width?parseInt(g.width):g.offsetWidth;x.tpos=h.x;x.tb=(g.offsetWidth-x.t)/2;var y=this.y={};y.t=g.height?parseInt(g.height):g.offsetHeight;y.tpos=h.y;y.tb=(g.offsetHeight-y.t)/2;x.p1=x.p2=y.p1=y.p2=0;this.wrapper=hs.createElement('div',{id:'highslide-wrapper-'+this.key,className:this.wrapperClassName},{visibility:'hidden',position:'absolute',zIndex:hs.zIndexCounter++},null,true);this.wrapper.onmouseover=this.wrapper.onmouseout=hs.wrapperMouseHandler;if(this.contentType=='image'&&this.outlineWhileAnimating==2)this.outlineWhileAnimating=0;if(!this.outlineType){this[this.contentType+'Create']()}else if(hs.pendingOutlines[this.outlineType]){this.connectOutline();this[this.contentType+'Create']()}else{this.showLoading();var j=this;new hs.Outline(this.outlineType,function(){j.connectOutline();j[j.contentType+'Create']()})}return true};hs.Expander.prototype={connectOutline:function(x,y){var o=this.outline=hs.pendingOutlines[this.outlineType];o.table.style.zIndex=this.wrapper.style.zIndex;hs.pendingOutlines[this.outlineType]=null},showLoading:function(){if(this.onLoadStarted||this.loading)return;this.loading=hs.loading;var a=this;this.loading.onclick=function(){a.cancelLoading()};var a=this,l=(this.x.tpos+this.x.tb+(this.x.t-this.loading.offsetWidth)/2)+'px',t=(this.y.tpos+(this.y.t-this.loading.offsetHeight)/2)+'px';setTimeout(function(){if(a.loading)hs.setStyles(a.loading,{left:l,top:t})},100)},imageCreate:function(){var a=this;var b=document.createElement('img');this.content=b;b.onload=function(){if(hs.expanders[a.key])a.contentLoaded()};if(hs.blockRightClick)b.oncontextmenu=function(){return false};b.className='highslide-image';hs.setStyles(b,{visibility:'hidden',display:'block',position:'absolute',maxWidth:'9999px',zIndex:3});b.title=hs.lang.restoreTitle;if(hs.safari)hs.container.appendChild(b);if(hs.ie&&hs.flushImgSize)b.src=null;b.src=this.src;this.showLoading()},htmlCreate:function(){this.content=hs.getNode(this.contentId);if(!this.content)this.content=hs.getSelfRendered();this.getInline(['maincontent']);if(this.maincontent){var a=hs.getElementByClass(this.content,'div','highslide-body');if(a)a.appendChild(this.maincontent);this.maincontent.style.display='block'}this.innerContent=this.content;hs.container.appendChild(this.wrapper);hs.setStyles(this.wrapper,{position:'static',padding:'0 '+hs.marginRight+'px 0 '+hs.marginLeft+'px'});this.content=hs.createElement('div',{className:'highslide-html windowbg2'},{position:'relative',zIndex:3,overflow:'hidden'},this.wrapper);this.mediumContent=hs.createElement('div',null,null,this.content,1);this.mediumContent.appendChild(this.innerContent);hs.setStyles(this.innerContent,{position:'relative',display:'block'});if(this.width)this.innerContent.style.width=this.width+'px';if(this.height)this.innerContent.style.height=this.height+'px';if(this.innerContent.offsetWidth<this.minWidth)this.innerContent.style.width=this.minWidth+'px';this.contentLoaded()},contentLoaded:function(){try{if(!this.content)return;this.content.onload=null;if(this.onLoadStarted)return;else this.onLoadStarted=true;var x=this.x,y=this.y;if(this.loading){hs.setStyles(this.loading,{top:'-9999px'});this.loading=null}this.marginBottom=hs.marginBottom;if(this.isImage){x.full=this.content.width;y.full=this.content.height;hs.setStyles(this.content,{width:this.x.t+'px',height:this.y.t+'px'})}else if(this.htmlGetSize)this.htmlGetSize();this.wrapper.appendChild(this.content);hs.setStyles(this.wrapper,{left:this.x.tpos+'px',top:this.y.tpos+'px'});hs.container.appendChild(this.wrapper);x.cb=(this.content.offsetWidth-this.x.t)/2;y.cb=(this.content.offsetHeight-this.y.t)/2;var a=hs.marginRight+2*x.cb;this.marginBottom+=2*y.cb;this.getOverlays();var b=x.full/y.full;var c=this.allowSizeReduction?this.minWidth:x.full;var d=this.allowSizeReduction?this.minHeight:y.full;var f={x:'auto',y:'auto'};var g=hs.getPageSize();x.min=x.tpos-x.cb+x.tb;x.span=Math.min(x.full,this.maxWidth||x.full);x.minSpan=Math.min(x.full,c);x.marginMin=hs.marginLeft;x.marginMax=a;x.scroll=g.scrollLeft;x.clientSpan=g.width;this.justify(x);y.min=y.tpos-y.cb+y.tb;y.span=Math.min(y.full,this.maxHeight||y.full);y.minSpan=Math.min(y.full,d);y.marginMin=hs.marginTop;y.marginMax=this.marginBottom;y.scroll=g.scrollTop;y.clientSpan=g.height;this.justify(y);if(this.isHtml)this.htmlSizeOperations();if(this.overlayBox)this.sizeOverlayBox(0,1);if(this.allowSizeReduction){if(this.isImage)this.correctRatio(b);else this.fitOverlayBox();if(this.isImage&&this.x.full>this.x.span){this.createFullExpand();if(this.overlays.length==1)this.sizeOverlayBox()}}this.show()}catch(e){window.location.href=this.src}},htmlGetSize:function(){this.innerContent.appendChild(hs.clearing);if(!this.x.full)this.x.full=this.innerContent.offsetWidth;this.y.full=this.innerContent.offsetHeight;this.innerContent.removeChild(hs.clearing);if(hs.ie&&this.newHeight>parseInt(this.innerContent.currentStyle.height)){this.newHeight=parseInt(this.innerContent.currentStyle.height)}hs.setStyles(this.wrapper,{position:'absolute',padding:'0'});hs.setStyles(this.content,{width:this.x.t+'px',height:this.y.t+'px'})},htmlSizeOperations:function(){if(this.x.span<this.x.full&&!this.allowWidthReduction)this.x.span=this.x.full;if(this.y.span<this.y.full&&!this.allowHeightReduction)this.y.span=this.y.full;this.scrollerDiv=this.innerContent;hs.setStyles(this.mediumContent,{width:this.x.span+'px',position:'relative',left:(this.x.min-this.x.tpos)+'px',top:(this.y.min-this.y.tpos)+'px'});hs.setStyles(this.innerContent,{border:'none',width:'auto',height:'auto'});var a=hs.getElementByClass(this.innerContent,'DIV','highslide-body');if(a){var b=a;a=hs.createElement(b.nodeName,null,{overflow:'hidden'},null,true);b.parentNode.insertBefore(a,b);a.appendChild(hs.clearing);a.appendChild(b);var c=this.innerContent.offsetWidth-a.offsetWidth;var d=this.innerContent.offsetHeight-a.offsetHeight;a.removeChild(hs.clearing);var e=hs.safari||navigator.vendor=='KDE'?1:0;hs.setStyles(a,{width:(this.x.span-c-e)+'px',height:(this.y.span-d)+'px',overflow:'auto',position:'relative'});if(e&&b.offsetHeight>a.offsetHeight){a.style.width=(parseInt(a.style.width)+e)+'px'}this.scrollingContent=a;this.scrollerDiv=this.scrollingContent}if(!this.scrollingContent&&this.y.span<this.mediumContent.offsetHeight)this.scrollerDiv=this.content;if(this.scrollerDiv==this.content&&!this.allowWidthReduction&&!/(iframe|swf)/.test(this.objectType)){this.x.span+=17}if(this.scrollerDiv&&this.scrollerDiv.offsetHeight>this.scrollerDiv.parentNode.offsetHeight){setTimeout("try { hs.expanders["+this.key+"].scrollerDiv.style.overflow = 'auto'; } catch(e) {}",hs.expandDuration)}},justify:function(p,a){var b,dim=p==this.x?'x':'y';var c=false;var d=true;p.min=Math.round(p.min-((p.span+p.p1+p.p2-p.t)/2));if(p.min<p.scroll+p.marginMin){p.min=p.scroll+p.marginMin;c=true}if(!a&&p.span<p.minSpan){p.span=p.minSpan;d=false}if(p.min+p.span+p.p1+p.p2>p.scroll+p.clientSpan-p.marginMax){if(!a&&c&&d){p.span=p.clientSpan-p.marginMin-p.marginMax}else if(p.span+p.p1+p.p2<p.clientSpan-p.marginMin-p.marginMax){p.min=p.scroll+p.clientSpan-p.span-p.marginMin-p.marginMax-p.p1-p.p2}else{p.min=p.scroll+p.marginMin;if(!a&&d)p.span=p.clientSpan-p.marginMin-p.marginMax}}if(!a&&p.span<p.minSpan){p.span=p.minSpan;d=false}if(p.min<p.marginMin){var e=p.min;p.min=p.marginMin;if(d&&!a)p.span=p.span-(p.min-e)}},correctRatio:function(a){var x=this.x,y=this.y;var b=false;if(x.span/y.span>a){x.span=y.span*a;if(x.span<x.minSpan){x.span=x.minSpan;y.span=x.span/a}b=true}else if(x.span/y.span<a){var c=y.span;y.span=x.span/a;b=true}this.fitOverlayBox(a);if(b){x.min=x.tpos-x.cb+x.tb;x.minSpan=x.span;this.justify(x,true);y.min=y.tpos-y.cb+y.tb;y.minSpan=y.span;this.justify(y,true);if(this.overlayBox)this.sizeOverlayBox()}},fitOverlayBox:function(a){var x=this.x,y=this.y;if(this.overlayBox){while(y.span>this.minHeight&&x.span>this.minWidth&&y.marginMin+y.p1+y.span+y.p2+y.marginMax>y.clientSpan){y.span-=10;if(a)x.span=y.span*a;this.sizeOverlayBox(0,1)}}},show:function(){var a={x:this.x.min-20,y:this.y.min-20,w:this.x.span+40,h:this.y.span+40};hs.hideSelects=(hs.ie&&hs.ieVersion()<7);if(hs.hideSelects)this.showHideElements('SELECT','hidden',a);hs.hideIframes=((window.opera&&navigator.appVersion<9)||navigator.vendor=='KDE'||(hs.ie&&hs.ieVersion()<5.5));if(hs.hideIframes)this.showHideElements('IFRAME','hidden',a);if(hs.geckoMac)this.showHideElements('*','hidden',a);this.changeSize(1,{xmin:this.x.tpos+this.x.tb-this.x.cb,ymin:this.y.tpos+this.y.tb-this.y.cb,xspan:this.x.t,yspan:this.y.t,xp1:0,xp2:0,yp1:0,yp2:0,o:hs.outlineStartOffset},{xmin:this.x.min,ymin:this.y.min,xspan:this.x.span,yspan:this.y.span,xp1:this.x.p1,yp1:this.y.p1,xp2:this.x.p2,yp2:this.y.p2,o:this.outline?this.outline.offset:0},hs.expandDuration,hs.expandSteps)},changeSize:function(b,c,d,e,f){if(this.outline&&!this.outlineWhileAnimating){if(b)this.outline.setPosition(this);else this.outline.destroy((this.isHtml&&this.preserveContent))}if(!b&&this.overlayBox){if(this.isHtml&&this.preserveContent){this.overlayBox.style.top='-9999px';hs.container.appendChild(this.overlayBox)}else hs.discardElement(this.overlayBox)}if(this.fadeInOut){c.op=b?0:1;d.op=b}var t,exp=this,easing=Math[this.easing]||Math.easeInQuad;if(!b)easing=Math[this.easingClose]||easing;for(var i=1;i<=f;i++){t=Math.round(i*(e/f));(function(){var a=i,size={};for(var x in c){size[x]=easing(t,c[x],d[x]-c[x],e);if(!/^op$/.test(x))size[x]=Math.round(size[x])}setTimeout(function(){if(b&&a==1){exp.content.style.visibility='visible';exp.a.className+=' highslide-active-anchor'}exp.setSize(size)},t)})()}if(b){setTimeout(function(){if(exp.outline)exp.outline.table.style.visibility="visible"},t);setTimeout(function(){exp.afterExpand()},t+50)}else setTimeout(function(){exp.afterClose()},t)},setSize:function(a){try{if(a.op)hs.setStyles(this.wrapper,{opacity:a.op});hs.setStyles(this.wrapper,{width:(a.xspan+a.xp1+a.xp2+2*this.x.cb)+'px',height:(a.yspan+a.yp1+a.yp2+2*this.y.cb)+'px',left:a.xmin+'px',top:a.ymin+'px'});hs.setStyles(this.content,{top:a.yp1+'px',left:a.xp1+'px',width:a.xspan+'px',height:a.yspan+'px'});if(this.isHtml){hs.setStyles(this.mediumContent,{left:(this.x.min-a.xmin+this.x.p1-a.xp1)+'px',top:(this.y.min-a.ymin+this.y.p1-a.yp1)+'px'});this.innerContent.style.visibility='visible'}if(this.outline&&this.outlineWhileAnimating){var o=this.outline.offset-a.o;this.outline.setPosition(this,{x:a.xmin+o,y:a.ymin+o,w:a.xspan+a.xp1+a.xp2+ -2*o,h:a.yspan+a.yp1+a.yp2+ -2*o},1)}this.wrapper.style.visibility='visible'}catch(e){window.location.href=this.src}},afterExpand:function(){this.isExpanded=true;this.focus();if(this.isHtml){}this.prepareNextOutline();if(this.overlayBox)this.showOverlays()},prepareNextOutline:function(){var a=this.key;var b=this.outlineType;new hs.Outline(b)},cancelLoading:function(){hs.expanders[this.key]=null;if(this.loading)hs.loading.style.left='-9999px'},writeCredits:function(){this.credits=hs.createElement('a',{href:hs.creditsHref,className:'highslide-credits',innerHTML:hs.lang.creditsText,title:hs.lang.creditsTitle});this.createOverlay({overlayId:this.credits,position:'top left'})},getInline:function(a,b){for(var i=0;i<a.length;i++){var c=a[i],s=null;if(!this[c+'Id']&&this.thumbsUserSetId)this[c+'Id']=c+'-for-'+this.thumbsUserSetId;if(this[c+'Id'])this[c]=hs.getNode(this[c+'Id']);if(!this[c]&&!this[c+'Text']&&this[c+'Eval'])try{s=eval(this[c+'Eval'])}catch(e){}if(!this[c]&&this[c+'Text']){s=this[c+'Text']}if(!this[c]&&!s){var d=this.a.nextSibling;while(d&&!hs.isHsAnchor(d)){if((new RegExp('highslide-'+c)).test(d.className||null)){this[c]=d.cloneNode(1);break}d=d.nextSibling}}if(!this[c]&&s)this[c]=hs.createElement('div',{className:'highslide-'+c,innerHTML:s});if(b&&this[c]){var o={position:(c=='heading')?'above':'below'};for(var x in this[c+'Overlay'])o[x]=this[c+'Overlay'][x];o.overlayId=this[c];this.createOverlay(o)}}},showHideElements:function(a,b,c){var d=document.getElementsByTagName(a);var e=a=='*'?'overflow':'visibility';for(var i=0;i<d.length;i++){if(e=='visibility'||(document.defaultView.getComputedStyle(d[i],"").getPropertyValue('overflow')=='auto'||d[i].getAttribute('hidden-by')!=null)){var f=d[i].getAttribute('hidden-by');if(b=='visible'&&f){f=f.replace('['+this.key+']','');d[i].setAttribute('hidden-by',f);if(!f)d[i].style[e]=d[i].origProp}else if(b=='hidden'){var g=hs.getPosition(d[i]);g.w=d[i].offsetWidth;g.h=d[i].offsetHeight;var h=(g.x+g.w<c.x||g.x>c.x+c.w);var j=(g.y+g.h<c.y||g.y>c.y+c.h);var k=hs.getWrapperKey(d[i]);if(!h&&!j&&k!=this.key){if(!f){d[i].setAttribute('hidden-by','['+this.key+']');d[i].origProp=d[i].style[e];d[i].style[e]='hidden'}else if(!f.match('['+this.key+']')){d[i].setAttribute('hidden-by',f+'['+this.key+']')}}else if(f=='['+this.key+']'||hs.focusKey==k){d[i].setAttribute('hidden-by','');d[i].style[e]=d[i].origProp||''}else if(f&&f.match('['+this.key+']')){d[i].setAttribute('hidden-by',f.replace('['+this.key+']',''))}}}}},focus:function(){this.wrapper.style.zIndex=hs.zIndexCounter++;if(this.isImage){this.content.title=hs.lang.restoreTitle;hs.styleRestoreCursor=window.opera?'pointer':'url('+hs.graphicsDir+hs.restoreCursor+'), pointer';if(hs.ie&&hs.ieVersion()<6)hs.styleRestoreCursor='hand';this.content.style.cursor=hs.styleRestoreCursor}hs.focusKey=this.key},move:function(e){this.x.min=e.left+e.dX;this.y.min=e.top+e.dY;if(e.type=='image')this.content.style.cursor='move';hs.setStyles(this.wrapper,{left:this.x.min+'px',top:this.y.min+'px'});if(this.outline)this.outline.setPosition(this)},resize:function(e){var w,h,r=e.width/e.height;w=Math.max(e.width+e.dX,Math.min(this.minWidth,this.x.full));if(this.isImage&&Math.abs(w-this.x.full)<12)w=this.x.full;h=this.isHtml?e.height+e.dY:w/r;if(h<Math.min(this.minHeight,this.y.full)){h=Math.min(this.minHeight,this.y.full);if(this.isImage)w=h*r}this.x.span=w;this.y.span=h;if(this.isHtml){var d=this.scrollerDiv;if(typeof this.wDiff=='undefined'){this.wDiff=this.innerContent.offsetWidth-d.offsetWidth;this.hDiff=this.innerContent.offsetHeight-d.offsetHeight}hs.setStyles(d,{width:(this.x.span-this.wDiff)+'px',height:(this.y.span-this.hDiff)+'px'})}var a={width:this.x.span+'px',height:this.y.span+'px'};hs.setStyles(this.content,a);if(this.isHtml){this.mediumContent.style.width='auto';if(this.body)hs.setStyles(this.body,{width:'auto',height:'auto'})}if(this.overlayBox)this.sizeOverlayBox(true);hs.setStyles(this.wrapper,{width:(this.x.p1+this.x.p2+2*this.x.cb+this.x.span)+'px',height:(this.y.p1+this.y.p2+2*this.y.cb+this.y.span)+'px'});if(this.outline)this.outline.setPosition(this)},close:function(){if(this.isClosing||!this.isExpanded)return;this.isClosing=true;try{if(this.isHtml)this.htmlPrepareClose();this.content.style.cursor='default';this.changeSize(0,{xmin:this.x.min,ymin:this.y.min,xspan:this.x.span,yspan:parseInt(this.content.style.height),xp1:this.x.p1,yp1:this.y.p1,xp2:this.x.p2,yp2:this.y.p2,o:this.outline?this.outline.offset:0},{xmin:this.x.tpos-this.x.cb+this.x.tb,ymin:this.y.tpos-this.y.cb+this.y.tb,xspan:this.x.t,yspan:this.y.t,xp1:0,yp1:0,xp2:0,yp2:0,o:hs.outlineStartOffset},hs.restoreDuration,hs.restoreSteps)}catch(e){this.afterClose()}},htmlPrepareClose:function(){if(hs.geckoMac){if(!hs.mask)hs.mask=hs.createElement('div',null,{position:'absolute'},hs.container);hs.setStyles(hs.mask,{width:this.x.span+'px',height:this.y.span+'px',left:this.x.min+'px',top:this.y.min+'px',display:'block'})}if(this.scrollerDiv&&this.scrollerDiv!=this.scrollingContent)this.scrollerDiv.style.overflow='hidden'},destroyObject:function(){this.body.innerHTML=''},sleep:function(){if(this.outline)this.outline.table.style.display='none';this.wrapper.style.display='none';hs.push(hs.sleeping,this)},awake:function(){hs.expanders[this.key]=this;if(hs.focusKey!=this.key){try{hs.expanders[hs.focusKey].close()}catch(e){}}var z=hs.zIndexCounter++,stl={display:'',zIndex:z};hs.setStyles(this.wrapper,stl);this.isClosing=false;var o=this.outline||0;if(o){if(!this.outlineWhileAnimating)stl.visibility='hidden';hs.setStyles(o.table,stl)}this.show()},createOverlay:function(o){var a=o.overlayId;if(typeof a=='string')a=hs.getNode(a);if(!a||typeof a=='string')return;a.style.display='block';this.genOverlayBox();var b=o.width&&/^[0-9]+(px|%)$/.test(o.width)?o.width:'auto';if(/^(left|right)panel$/.test(o.position)&&!/^[0-9]+px$/.test(o.width))b='200px';var c=hs.createElement('div',{id:'hsId'+hs.idCounter++,hsId:o.hsId},{position:'absolute',visibility:'hidden',width:b},this.overlayBox,true);c.appendChild(a);hs.setAttribs(c,{hideOnMouseOut:o.hideOnMouseOut,opacity:o.opacity||1,hsPos:o.position,fade:o.fade});if(this.gotOverlays){this.positionOverlay(c);if(!c.hideOnMouseOut||this.mouseIsOver)hs.fade(c,0,c.opacity)}hs.push(this.overlays,hs.idCounter-1)},positionOverlay:function(a){var p=a.hsPos||'middle center';if(/left$/.test(p))a.style.left=0;if(/center$/.test(p))hs.setStyles(a,{left:'50%',marginLeft:'-'+Math.round(a.offsetWidth/2)+'px'});if(/right$/.test(p))a.style.right=0;if(/^leftpanel$/.test(p)){hs.setStyles(a,{right:'100%',marginRight:this.x.cb+'px',top:-this.y.cb+'px',bottom:-this.y.cb+'px',overflow:'auto'});this.x.p1=a.offsetWidth}else if(/^rightpanel$/.test(p)){hs.setStyles(a,{left:'100%',marginLeft:this.x.cb+'px',top:-this.y.cb+'px',bottom:-this.y.cb+'px',overflow:'auto'});this.x.p2=a.offsetWidth}if(/^top/.test(p))a.style.top=0;if(/^middle/.test(p))hs.setStyles(a,{top:'50%',marginTop:'-'+Math.round(a.offsetHeight/2)+'px'});if(/^bottom/.test(p))a.style.bottom=0;if(/^above$/.test(p)){hs.setStyles(a,{left:(-this.x.p1-this.x.cb)+'px',right:(-this.x.p2-this.x.cb)+'px',bottom:'100%',marginBottom:this.y.cb+'px',width:'auto'});this.y.p1=a.offsetHeight}else if(/^below$/.test(p)){hs.setStyles(a,{position:'relative',left:(-this.x.p1-this.x.cb)+'px',right:(-this.x.p2-this.x.cb)+'px',top:'100%',marginTop:this.y.cb+'px',width:'auto'});this.y.p2=a.offsetHeight;a.style.position='absolute'}},getOverlays:function(){this.getInline(['heading','caption'],true);if(this.heading&&this.dragByHeading)this.heading.className+=' highslide-move';if(hs.showCredits)this.writeCredits();for(var i=0;i<hs.overlays.length;i++){var o=hs.overlays[i],tId=o.thumbnailId,sg=o.slideshowGroup;if((!tId&&!sg)||(tId&&tId==this.thumbsUserSetId)||(sg&&sg===this.slideshowGroup)){if(this.isImage||(this.isHtml&&o.useOnHtml))this.createOverlay(o)}}var a=[];for(var i=0;i<this.overlays.length;i++){var o=hs.$('hsId'+this.overlays[i]);if(/panel$/.test(o.hsPos))this.positionOverlay(o);else hs.push(a,o)}var b=this.x.p1+this.x.full+this.x.p2;if(hs.padToMinWidth&&b<hs.minWidth){this.x.p1+=(hs.minWidth-b)/2;this.x.p2+=(hs.minWidth-b)/2}for(var i=0;i<a.length;i++)this.positionOverlay(a[i]);this.gotOverlays=true},genOverlayBox:function(){if(!this.overlayBox)this.overlayBox=hs.createElement('div',null,{position:'absolute',width:this.x.span?this.x.span+'px':this.x.full+'px',height:0,visibility:'hidden',overflow:'hidden',zIndex:hs.ie?4:null},hs.container,true)},sizeOverlayBox:function(a,b){hs.setStyles(this.overlayBox,{width:this.x.span+'px',height:this.y.span+'px'});if(a||b){for(var i=0;i<this.overlays.length;i++){var o=hs.$('hsId'+this.overlays[i]);if(o&&/^(above|below)$/.test(o.hsPos)){if(hs.ie&&(hs.ieVersion()<=6||document.compatMode=='BackCompat')){o.style.width=(this.overlayBox.offsetWidth+2*this.x.cb-this.x.p1-this.x.p2)+'px'}this.y[o.hsPos=='above'?'p1':'p2']=o.offsetHeight}}}if(a){hs.setStyles(this.content,{top:this.y.p1+'px'});hs.setStyles(this.overlayBox,{top:(this.y.p1+this.y.cb)+'px'})}},showOverlays:function(){var b=this.overlayBox,p=hs.getPageSize(),mX=hs.mouse.x+p.scrollLeft,mY=hs.mouse.y+p.scrollTop;hs.setStyles(b,{top:(this.y.p1+this.y.cb)+'px',left:(this.x.p1+this.x.cb)+'px',overflow:'visible'});if(hs.safari)b.style.visibility='visible';this.wrapper.appendChild(b);this.mouseIsOver=this.x.min<mX&&mX<this.x.min+this.x.p1+this.x.span+this.x.p2&&this.y.min<mY&&mY<this.y.min+this.y.p1+this.y.span+this.y.p2;for(var i=0;i<this.overlays.length;i++){var o=hs.$('hsId'+this.overlays[i]);o.style.zIndex=4;if(!o.hideOnMouseOut||this.mouseIsOver)hs.fade(o,0,o.opacity)}},createFullExpand:function(){this.fullExpandLabel=hs.createElement('a',{href:'javascript:hs.expanders['+this.key+'].doFullExpand();',title:hs.lang.fullExpandTitle,className:'highslide-full-expand'});this.createOverlay({overlayId:this.fullExpandLabel,position:hs.fullExpandPosition,hideOnMouseOut:true,opacity:hs.fullExpandOpacity})},doFullExpand:function(){try{if(this.fullExpandLabel)hs.discardElement(this.fullExpandLabel);this.focus();this.x.min=parseInt(this.wrapper.style.left)-(this.x.full-this.content.width)/2;if(this.x.min<hs.marginLeft)this.x.min=hs.marginLeft;this.wrapper.style.left=this.x.min+'px';hs.setStyles(this.content,{width:this.x.full+'px',height:this.y.full+'px'});this.x.span=this.x.full;this.y.span=this.y.full;if(this.overlayBox)this.sizeOverlayBox(true);hs.setStyles(this.wrapper,{width:(this.x.p1+2*this.x.cb+this.x.span+this.x.p2)+'px',height:(this.y.p1+2*this.y.cb+this.y.span+this.y.p2)+'px'});if(this.outline)this.outline.setPosition(this);this.redoShowHide()}catch(e){window.location.href=this.content.src}},redoShowHide:function(){var a={x:parseInt(this.wrapper.style.left)-20,y:parseInt(this.wrapper.style.top)-20,w:this.content.offsetWidth+40,h:this.content.offsetHeight+40};if(hs.hideSelects)this.showHideElements('SELECT','hidden',a);if(hs.hideIframes)this.showHideElements('IFRAME','hidden',a);if(hs.geckoMac)this.showHideElements('*','hidden',a)},afterClose:function(){this.a.className=this.a.className.replace('highslide-active-anchor','');if(hs.hideSelects)this.showHideElements('SELECT','visible');if(hs.hideIframes)this.showHideElements('IFRAME','visible');if(hs.geckoMac)this.showHideElements('*','visible');if(this.isHtml&&this.preserveContent)this.sleep();else{if(this.outline&&this.outlineWhileAnimating)this.outline.destroy();hs.discardElement(this.wrapper)}if(hs.mask)hs.mask.style.display='none';hs.expanders[this.key]=null}};if(document.readyState&&hs.ie){var src=(window.location.protocol=='https:')?'://0':'javascript:void(0)';document.write('<script type="text/javascript" defer="defer" src="'+src+'" '+'onreadystatechange="if (this.readyState == \'complete\') hs.domReady();"'+'><\/script>')}hs.langDefaults=hs.lang;var HsExpander=hs.Expander;hs.addEventListener(document,'mousemove',function(e){hs.mouse={x:e.clientX,y:e.clientY}});hs.addEventListener(document,'mousedown',hs.mouseClickHandler);hs.addEventListener(document,'mouseup',hs.mouseClickHandler);