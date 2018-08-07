interface DebuggerHeader {
    name: string,
    message: string
}

class Debugger {
    /**
     * bar
     */
    public static bar(time: string, properties: any): JQuery {
        let box = Debugger.createElement('bar');
        let html = '<div class="bar-title"><i class="fa fa-bar-chart"></i>' + time +'<i class="fa fa-close"></i></div><div class="bar-info"><table class="bar-box">';
        $.each(properties, (i, item) => {
            html += '<tr><td>'+i+'</td><td>'+item+'</td></tr>';
        });
        box.html(html + '</table></div>');
        box.on('click', '.bar-title .fa-bar-chart', function() {
            $(this).closest('.debugger-bar').toggleClass('expanded');
        }).on('click', '.bar-title .fa-close', function() {
            $(this).closest('.debugger-bar').remove();
        });
        return box;
    }

    private static createElement(name: string): JQuery {
        let box = $('<div class="debugger-' + name +'"></div>');
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
                        +trace.function+'('+trace.args.join(',')+')</p>';
                    if (trace.file) {
                        html += '<p>'+trace.file+':'+trace.line+'</p>';
                    }
                    html +='</div><div class="panel-body">'+trace.source+'</div></div>';
                });
            }
            html += '</div></div>';
        });
        box.html(html);
        box.on('click', '.panel .panel-header', function() {
            $(this).closest('.panel').toggleClass('expanded');
        });
        return box;
    }
}