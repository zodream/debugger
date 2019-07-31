interface DebuggerHeader {
    name: string,
    message: string
}

class Debugger {
    /**
     * bar
     */
    public static bar(time: string, errors: number, info: any): JQuery {
        if ($.cookie && $.cookie('debugger-bar') == 1) {
            return;
        }
        let box = Debugger.createElement('bar');
        let html = '<div class="bar-title">';
        if (errors > 0) {
            html += '<span class="error-count">' + errors + '</span>';
        }
        html += '<i class="fa fa-chart-bar"></i>' + time +'<i class="fa fa-close"></i></div><div class="bar-info"><table class="bar-box">' + Debugger.createTable(info);
        box.html(html + '</table></div>');
        box.on('click', '.bar-title .fa-chart-bar', function() {
            $(this).closest('.debugger-bar').toggleClass('expanded');
        }).on('click', '.bar-title .fa-close', function() {
            $(this).closest('.debugger-bar').remove();
            if ($.cookie) {
                $.cookie('debugger-bar', 1);
            }
        });
        return box;
    }

    private static createTable(data: any): string {
        let html = '';
        $.each(data, (name, info) => {
            let tr = '',
                isArr = info && info instanceof Array;
            $.each(info, (i, item) => {
                if (isArr) {
                    tr += '<tr><td colspan="2">'+item+'</td></tr>';
                    return;
                }
                tr += '<tr><td>'+i+'</td><td>'+item+'</td></tr>';
            });
            if (tr == '') {
                return;
            }
            html += '<tr class="header-tr"><td colspan="2">'+name+'</td></tr>' + tr;
        });
        return html;
    }

    private static createElement(name: string): JQuery {
        let box = $('.debugger-' + name);
        if (box.length == 1) {
            return box;
        }
        box = $('<div class="debugger-' + name +'"></div>');
        $(document.body).append(box);
        return box;
    }

    /**
     * blueScreen
     */
    public static blueScreen(header: DebuggerHeader, data: Array<any>): JQuery {
        let box = Debugger.createElement('blue-screen');
        let html = '<div class="bs-header"><p>'+header.name+'</p><h1>'+header.message+'</h1></div>';
        data.forEach(ex => {
            html += '<div class="panel"><div class="panel-header"><p class="name">'+ex.name+': '+ex.message+'</p><p>'+ex.file+':'+ex.line+'</p></div><div class="panel-body">';
            if (ex.trace) {
                ex.trace.forEach(trace => {
                    html += '<div class="panel"><div class="panel-header"><p class="name">'+
                        (trace.class ? trace.class+trace.type : '')
                        +trace.function+'</p>';
                    if (trace.file) {
                        html += '<p>'+trace.file+':'+trace.line+'</p>';
                    }
                    html +='</div><div class="panel-body">'+trace.source+ Debugger.formatParam(trace.args)+'</div></div>';
                });
            }
            html += '</div></div>';
        });
        box.html(html)
            .on('click', '.panel .panel-header', function() {
            $(this).closest('.panel').toggleClass('expanded');
        }).find('.panel').removeClass('expanded');
        return box;
    }

    private static formatParam(data: any): string {
        if (!data || Object.keys(data).length < 1) {
            return '';
        }
        let html = '';
        $.each(data, (i, item) => {
            html += `<p><label>${i}</label>: <code>${item}</code></p>`;
        });
        return `<div class="func-val-box">${html}</div>`;
    }
}