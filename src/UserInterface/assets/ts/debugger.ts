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
    public static blueScreen(header: DebuggerHeader): JQuery {
        let box = Debugger.createElement('blue-screen');
        let html = '<div class="bs-header"><p>'+header.name+'</p><h1><span>'+header.message+'</span><a href="" target="_blank" rel="noreferrer noopener">search►</a></h1></div>';
        box.html(html);
        return box;
    }
}