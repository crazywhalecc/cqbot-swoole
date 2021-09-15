hljs.initHighlighting()

var _hmt = _hmt || [];
(function () {
    var hm = document.createElement("script");
    hm.src = "https://hm.baidu.com/hm.js?f0f276cefa10aa31a20ae3815a50b795";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(hm, s);
})();

function appendChatModule(id, chatDialogs) {
    let insertDiv = document.getElementById(id);
    let ss = '';
    ss += '<div class="doc-chat-container">';
    for(let i of chatDialogs) {
        if (i.role === 0) {
            ss += '<div class="doc-chat-row doc-chat-row-robot">\n' +
                '    <img class="doc-chat-avatar" src="https://docs-v1.zhamao.xin/logo.png" alt=""/>\n' +
                '    <div class="doc-chat-box doc-chat-box-robot">' + i.msg + '</div>\n' +
                '  </div>';
        } else {
            ss += '<div class="doc-chat-row">\n' +
                '    <div class="doc-chat-box">' + i.msg + '</div>\n' +
                '    <img class="doc-chat-avatar" src="http://api.btstu.cn/sjtx/api.php"  alt=""/>\n' +
                '  </div>';
        }
    }
    insertDiv.innerHTML = ss + '</div>';
}

function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");

    if (arr = document.cookie.match(reg))

        return unescape(arr[2]);
    else
        return null;
}

function setCookie(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString() + ";path=/";
}

s_theme=getCookie("_theme");
if(s_theme !== undefined) {
    document.body.setAttribute("data-md-color-scheme", s_theme)
    var name = document.querySelector("#__code_0 code span:nth-child(7)")
    name.textContent = s_theme
}

s_primary=getCookie("_primary_color");
if(s_primary !== null) {
    document.body.setAttribute("data-md-color-primary", s_primary);
    var name2 = document.querySelector("#__code_2 code span:nth-child(7)");
    if (s_primary !== null && name2 !== null) name2.textContent = s_primary.replace("-", " ");
}

s_accent=getCookie("_accent_color");
if(s_accent !== null) {
    document.body.setAttribute("data-md-color-accent", s_accent);
    var name3 = document.querySelector("#__code_3 code span:nth-child(7)");
    if (s_accent !== null && name3 !== null) name3.textContent = s_accent.replace("-", " ");
}

setTimeout(() => {
    let ls = document.querySelectorAll("chat-box");
    for(let i of ls) {
        let final = '<div class="doc-chat-container">';
        let dialogs = i.innerHTML.split("\n");
        for(let j of dialogs) {
            if(j === '') continue;
            if(j.substr(0, 2) === ') ') {
                final += '<div class="doc-chat-row">\n' +
                    '    <div class="doc-chat-box">' + j.substr(2).replaceAll("\\n", "<br>") + '</div>\n' +
                    '    <img class="doc-chat-avatar" src="http://api.btstu.cn/sjtx/api.php"  alt=""/>\n' +
                    '  </div>';
            } else if (j.substr(0, 2) === '( ') {
                final += '<div class="doc-chat-row doc-chat-row-robot">\n' +
                    '    <img class="doc-chat-avatar" src="https://docs-v1.zhamao.xin/logo.png" alt=""/>\n' +
                    '    <div class="doc-chat-box doc-chat-box-robot">' + j.substr(2).replaceAll("\\n", "<br>") + '</div>\n' +
                    '  </div>';
            } else if (j.substr(0, 2) === '^ ') {
                final += '<div class="doc-chat-row doc-chat-banner">' + j.substr(2) + '</div>';
            } else if (j.substr(0, 2) === '[ ') {
                final += '<div class="doc-chat-row doc-chat-row-robot">\n' +
                    '    <img class="doc-chat-avatar" src="https://docs-v1.zhamao.xin/logo.png" alt=""/>\n' +
                    '    <div class="doc-chat-box doc-chat-box-robot"><img src="' + j.substr(2) + '" alt=""/></div>\n' +
                    '  </div>';
            }
        }
        i.innerHTML = final;
    }
}, 500);
