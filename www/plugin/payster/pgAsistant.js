var isMobile = false;
var exTarget;
var exAction;
var url = "https://api.payster.co.kr";

if(checkPlatform(window.navigator.userAgent) == "mobile"){
    isMobile = true;
} else {
    var html = '<div id="pgPayMask" style="position:fixed;z-index:9000;background-color:#000;display:none;left:0;top:0;width:100%;height:100%;"></div>';
    html += '<div id="pgPayWindow" style="display: none;position:fixed;top:0%;width:100%;height:100%;z-index:10000;">';
    html += '<div style="width:100%;height:100%;">';
    html += '<iframe id="pg_pay_frame" name="pg_pay_frame" style="width:100%; height:100%;" src="" marginwidth="0" marginheight="0" frameborder="no" scrolling="no"></iframe>';
    html += '</div></div>';
    document.write(html);
}

function setSendPayUrl(tagetUrl){
    url = tagetUrl;
}

function SendPay(form){
    if (isMobile || (isObjectEmpty(form.mobileMode) == false && form.mobileMode.value == "Y")){
        //form.target = "_self";
        form.method = "post";
        form.action = url + "/payInit_hash.do"; // SHA256 Hash
        form.submit();
    } else {
        exTarget = form.target;
        exAction = form.action;
        form.target = "pg_pay_frame"; // SHA256 Hash
        form.action = url + "/payInit_hash.do"; // SHA256 Hash

        // Calculate mask size
        //var maskHeight = $(document).height();
        //var maskWidth = $(document).width();

        //$("#pgPayMask").fadeIn(0);
        //$("#pgPayMask").fadeTo("slow", 0.6);

        var targetElement = document.getElementById('pgPayMask');
        fadeIn(targetElement, 150, 0.6);

        form.submit();
        document.getElementById('pgPayWindow').style.display = 'block';
        //$(".pgPayWindow").show();
    }
}

const ajax = getXMLHTTPRequest();

function getXMLHTTPRequest() {
    let request = false;
    try { request = new XMLHttpRequest(); }
    catch(err01) {
        try { request = new ActiveXObject("Msxml2.XMLHTTP"); }
        catch(err02) {
            try { request = new ActiveXObject("Microsoft.XMLHTTP"); }
            catch(err03) { request = false; }
        }
    }
    return request;
}

window.addEventListener("message", returnData, false);

function returnData (e){
    //$("#pgPayMask, .pgPayWindow").hide();
    document.getElementById('pgPayMask').style.display = 'none';
    document.getElementById('pgPayWindow').style.display = 'none';
    //$("#pg_pay_frame").attr("src", "");
    if (e.data.resultCode == '0000'){
        var resData = e.data.data;
//		if (e.data.isNoti == 'Y'){
//			ajax.open("POST", e.data.sendUrl, true);
//			var sData = "tid="+resData.tid+"&payMethod="+resData.payMethod+"&canAmt="+resData.amt;
//			ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
//			ajax.send(sData);
//		}

        var form = document.createElement("form");
        document.getElementsByTagName('body')[0].appendChild(form);
        for(var key in resData){
            var input = document.createElement("input");
            input.name = key;
            input.type = "hidden";
            input.value = resData[key];
            form.appendChild(input);
        }

        form.name = "payResult";
        form.acceptCharset = e.data.charSet;
        form.action = e.data.returnUrl;
        form.method = 'post';

        //form.submit();
        pay_result_submit();

    }else if (e.data[0] == 'SUCCESS'){
        var form = document.createElement("form");
        document.getElementsByTagName('body')[0].appendChild(form);
        var keys=Object.keys(e.data[1]);
        for(var key in keys){
            var input = document.createElement("input");
            input.name = keys[key];
            input.type = "hidden";
            input.value = e.data[1][keys[key]];
            form.appendChild(input);
        }
        form.name = "payResult";
        //form.acceptCharset = charSet;
        form.acceptCharset = e.data[1]["charSet"];
        form.method = 'post';
        form.target = exTarget;
        form.action = exAction;
        //form.submit();
        pay_result_submit();
    } else {
        document.pg_pay_frame.location.href = "about:blank";
        pay_result_close()
    }

    document.payInit.target = exTarget;
    document.payInit.action = exAction;
}

function payResultSubmit(){
    document.payResult.submit();
}

function checkPlatform(ua) {
    if(ua === undefined) {
        ua = window.navigator.userAgent;
    }

    ua = ua.toLowerCase();
    var platform = {};
    var matched = {};
    var userPlatform = "pc";
    var platform_match = /(ipad)/.exec(ua) || /(ipod)/.exec(ua)
        || /(windows phone)/.exec(ua) || /(iphone)/.exec(ua)
        || /(kindle)/.exec(ua) || /(silk)/.exec(ua) || /(android)/.exec(ua)
        || /(win)/.exec(ua) || /(mac)/.exec(ua) || /(linux)/.exec(ua)
        || /(cros)/.exec(ua) || /(playbook)/.exec(ua)
        || /(bb)/.exec(ua) || /(blackberry)/.exec(ua)
        || [];

    matched.platform = platform_match[0] || "";

    if(matched.platform) {
        platform[matched.platform] = true;
    }

    if(platform.android || platform.bb || platform.blackberry
        || platform.ipad || platform.iphone
        || platform.ipod || platform.kindle
        || platform.playbook || platform.silk
        || platform["windows phone"]) {
        userPlatform = "mobile";
    }

    if(platform.cros || platform.mac || platform.linux || platform.win) {
        userPlatform = "pc";
    }

    return userPlatform;
}

function isObjectEmpty(value){
    if( value == "" || value == null || value == undefined || ( value != null && typeof value == "object" && !Object.keys(value).length ) ){
        return true
    } else {
        return false
    }
}

function fadeIn(target, time, opacity) {
    var level = 0;
    var inTimer = null;
    inTimer = setInterval( function() {
        level = fadeInAction(target, level, inTimer, time, opacity);
        target.style.display = 'block';
    }, 0);

}
function fadeInAction(target, level, inTimer, time, opacity) {
    level = level + opacity/time;
    changeOpacity(target, level);
    if(level>=opacity) clearInterval(inTimer);
    return level;
}
function fadeOut(target) {
    var level = 1;
    var outTimer = null;
    outTimer = setInterval( function() {
        level = fadeOutAction(target, level, outTimer);
    }, 50);
}
function fadeOutAction(target, level, outTimer) {
    level = level - 0.1;
    changeOpacity(target, level);
    if(level < 0) {
        clearInterval(outTimer);
    }
    return level;
}
function changeOpacity(target, level) {
    var obj = target;
    obj.style.opacity = level;
    obj.style.MozOpacity = level;
    obj.style.KhtmlOpacity = level;
    obj.style.MsFilter = "'progid: DXImageTransform.Microsoft.Alpha(Opacity=" + (level * 100) + ")'";
    obj.style.filter = "alpha(opacity=" + (level * 100) + ");";
}