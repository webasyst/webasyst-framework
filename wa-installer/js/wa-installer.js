(function () {
    var global = this;
    var wai = global.wai = {
        options: {
            refresh_timeout: 1000,// ms
            redirect_timeout: 5000,// ms
            submit_id: 'wa-installer-submit',
            locale_id: 'wa-installer-locale-select',
            container_id: 'content-wrapper',
            scroll_id: 'content-wrapper',
            form_id: 'install_form',
            complete_id: 'install_form_complete',
            progress_id: 'i-progress-step-',
            mod_rewrite_id: 'input_mod_rewrite',
            redirect_url_id: 'redirect_url',
            redirect_message_id: 'redirect_message',
            lang: null,
            end: true
        },
        form: null,
        xmlReq: null,
        xmlReqRewrite: null,
        xmlReqExtract: null,
        date: null,
        debug: false,
        lookup: true,
        hasRestartTime: null,
        init: function (debug) {
            this.debug = debug;
            this.date = new Date();
            if (document.readyState === "complete") {
                setTimeout(wai.ready, 1);
            } else if (document.addEventListener) {
                window.addEventListener("load", wai.ready, false);
            } else if (document.attachEvent) {
                window.attachEvent("onload", wai.ready);
            }
        },
        ready: function () {
            var obj = document.getElementById(wai.options.submit_id);
            if (obj) {
                obj.onclick = wai.onContinue;
            }
            obj = document.getElementById(wai.options.locale_id);
            if (obj) {
                obj.onchange = wai.onChangeLocale;
            }
            obj = document.getElementById(wai.options.form_id);
            if (obj) {
                wai.form = obj;
            }
            var step = wai.getStep();
            if (step > 2) {
                obj = document.getElementById(wai.options.scroll_id);
                if (obj) {
                    obj.scrollTop = obj.scrollHeight;
                }
                wai.checkModRewrite();
                if (step == 5) {
                    if (document.getElementById(wai.options.redirect_url_id)) {
                        var message = document.getElementById(wai.options.redirect_message_id);
                        if (message) {
                            message.style.display = 'block';
                        }
                        setTimeout(wai.redirect, wai.options.redirect_timeout);
                    }
                }
            }
        },
        getState: function () {
            try {
                wai.xmlReq = wai.createRequestObject();
                if (wai.xmlReq) {
                    wai.xmlReq.onreadystatechange = wai.onGetState;
                    var url = 'install.php';
                    var query = [];
                    query[query.length] = 'action=getstate';
                    query[query.length] = 'source=ajax';
                    query[query.length] = 'req_time=' + wai.date.getTime();
                    url = url + '?' + query.join('&');
                    if (wai.options.lang) {
                        url += '&lang=' + wai.options.lang;
                    }
                    wai.xmlReq.open("GET", url, true);
                    wai.xmlReq.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2000 00:00:00 GMT");
                    wai.xmlReq.send(null);
                }
            } catch (e) {
                alert(e.message);
            }
        },
        checkModRewrite: function () {
            var input = document.getElementById(wai.options.mod_rewrite_id);
            if (input && (parseInt(input.value) || true)) {
                try {
                    wai.xmlReqRewrite = wai.createRequestObject();
                    if (wai.xmlReqRewrite) {
                        wai.xmlReqRewrite.onreadystatechange = wai.oncheckModRewrite;
                        var url = "./non/exists/url/?mod_rewrite=1";
                        wai.xmlReqRewrite.open("GET", url, true);
                        wai.xmlReqRewrite.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2000 00:00:00 GMT");
                        wai.xmlReqRewrite.send(null);
                    }
                } catch (e) {
                    alert(e.message);
                }
            }
        },
        oncheckModRewrite: function () {
            try {
                if (wai.xmlReqRewrite.readyState == 4) {// 4 = "loaded"
                    var input = document.getElementById(wai.options.mod_rewrite_id);
                    // 404 = mod rewrite not available
                    // 500 = mod rewrite not work properly
                    if ((wai.xmlReqRewrite.status == 404) || (wai.xmlReqRewrite.status == 500)) {
                        input.value = '0';
                    } else if ((wai.xmlReqRewrite.status == 200) && (wai.xmlReqRewrite.responseText.match(/mod_rewrite:success/))) {
                        input.value = '1';
                    }
                    wai.xmlReqRewrite = null;
                }

            } catch (e) {
                alert(e.message);
            }
        },
        onChangeLocale: function () {
            try {
                var location = window.location.toString();
                location = location.replace(/\?.*$/, '') + '?' + this.name + '=' + this.value;
                window.location = location;
            } catch (e) {
            }
        },
        start: function () {
            wai.showProgress();
            setTimeout(wai.getState, wai.options.refresh_timeout * 1.5);
            return false;
        },
        extract: function () {

        },
        showLabel: function (label) {
        },
        showProgress: function () {
            try {
                var step = wai.getStep();
                var obj;
                for (var i = 1; i < step; i++) {
                    obj = document.getElementById(wai.options.progress_id + i);
                    if (obj) {
                        obj.className = obj.className.replace(/\b(current|passed|next)\b/, '') + 'passed';
                    }
                }
                obj = document.getElementById(wai.options.progress_id + step);
                if (obj) {
                    obj.className = (obj.className).replace(/\b(current|passed|next)\b/, '') + 'current';
                }
            } catch (e) {
            }
        },
        onProgress: function (progress, description) {// intVal
            try {
                var obj = document.getElementById(wai.options.container_id);
                if (obj) {
                    obj.innerHTML = description;
                    obj = document.getElementById(wai.options.scroll_id);
                    if (obj) {
                        obj.scrollTop = obj.scrollHeight;
                    }
                }
            } catch (e) {
                alert(e.message);
            }
        },
        getLayer: function (layerName, pwindow) {
            if (!pwindow) {
                pwindow = window;
            }

            if (pwindow.document.getElementById) {
                return pwindow.document.getElementById(layerName);
            }
            if (pwindow.document.all) {
                return pwindow.document.all[layerName];
            }
            if (pwindow.document.layers) {
                return pwindow.document.layers[layerName];
            }
            return null;
        },
        onRestart: function () {// RESTART
            try {
                var curDate = new Date();
                var curTime = curDate.getTime();
                if (wai.hasRestartTime == null || (curTime - wai.hasRestartTime) > 10000) {
                    wai.hasRestartTime = curTime;
                    wai.xmlReqExtract = wai.createRequestObject();
                    var url = "install.php";
                    var query = [];
                    query[query.length] = 'step=' + '2';
                    query[query.length] = 'lang=' + wai.options.lang;
                    query[query.length] = 'timestamp=' + wai.date.getTime();
                    url = url + '?' + query.join('&');
                    wai.xmlReqExtract.open("GET", url, true);
                    wai.xmlReqExtract.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2000 00:00:00 GMT");

                    /*{
                     queryText:  queryText.join('&'),
                     queryElem:  queryElem,
                     id:		 (new Date().getTime()) + '',
                     hash:	   hash,
                     span:	   null
                     };*/
                    wai.xmlReqExtract.send(null);
                    var obj = wai.getLayer(wai.options.complete_id);
                    if (obj) {
                        obj.value = 1;
                    }
                    return false;
                }
                return true;
            } catch (e) {
                alert(e.message);
                return true;
            }
        },
        onComplete: function () {// ANOTHER
            wai.lookup = false;
            setTimeout(function () {
                if (wai.form) {
                    wai.form.submit();
                }
            }, 1000);
        },
        redirect: function () {
            var href = document.getElementById(wai.options.redirect_url_id);
            if (href && href.href) {
                window.location.replace(href.href);
            }
        },
        getStep: function () {
            var step = null;
            var form = document.forms[0];

            if (form == null) {
                return step;
            }

            for (var i = 0; i < form.elements.length; i++) {
                if (form.elements[i].name == "step") {
                    step = form.elements[i].value;
                    break;
                }
            }
            return parseInt(step);
        },
        onContinue: function (event) {
            var step = wai.getStep();
            if ((parseInt(step) == 2)) {
                this.disabled = true;
                return wai.start(event);
            } else {
                return true;
            }
        },
        onGetState: function () {
            try {
                if (wai.xmlReq.readyState == 4) {// 4 = "loaded"
                    if (wai.xmlReq.status == 200) {// 200 = OK
                        var response = wai.xmlReq.responseText.match(/^([^:]+):/g);
                        var extra_time = 0;
                        // STATE_CODE:PROGRESS_VALUE:DESCRIPTION(Base64Encoded
                        // Optional)
                        if (response) {
                            response = response[0].replace(/:$/g, '');

                            switch (response) {// ...our code here...
                                case 'RESTART' :
                                    wai.onRestart();
                                    extra_time = 1500 - wai.options.refresh_timeout;//500;
                                    break;
                                case 'COMPLETE' :
                                    wai.lookup = false;
                                    wai.onComplete();
                                    break;
                                case 'PROGRESS' :
                                    var html = wai.xmlReq.responseText.replace(/^([^:]+):/g, '');
                                    wai.onProgress(0, html);
                                    break;
                                default :
                                    wai.showLabel('unknown state: ' + response[0]);
                                    break;
                            }
                            if (wai.debug) {
                                alert('response: ' + response[0] + '\nSTATE ' + response[1])
                            }
                        } else {
                            alert(wai.xmlReq.responseText);
                        }
                        wai.xmlReq = null;
                        if (wai.lookup) {
                            setTimeout(wai.getState, wai.options.refresh_timeout + extra_time);
                        }
                    } else {
                        if (wai.lookup) {
                            setTimeout(wai.getState, wai.options.refresh_timeout);
                        }
                    }
                }

            } catch (e) {
                alert(e.message);
                if (wai.lookup) {
                    setTimeout(wai.getState, wai.options.refresh_timeout);
                }
            }
        },
        createRequestObject: function () {
            if (typeof XMLHttpRequest === 'undefined') {
                XMLHttpRequest = function () {
                    try {
                        return new ActiveXObject("Msxml2.XMLHTTP.6.0");
                    }
                    catch (e) {
                    }
                    try {
                        return new ActiveXObject("Msxml2.XMLHTTP.3.0");
                    }
                    catch (e) {
                    }
                    try {
                        return new ActiveXObject("Msxml2.XMLHTTP");
                    }
                    catch (e) {
                    }
                    try {
                        return new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    catch (e) {
                    }
                    throw new Error("This browser does not support XMLHttpRequest.");
                };
            }
            return new XMLHttpRequest();
        }
    };

})();

wai.init(false);
