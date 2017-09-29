(function() {
    var originalCreateButtons = UI.createButtons;
    var originalSetEvents = UI.setEvents;


    $.extend(UI, {
        windowPreview: null,

        setEvents: function () {
            var self  = this;
            originalSetEvents.apply(this);
            window.addEventListener("storage", this.selectSegmentFromPreview.bind(this), true);
            url = '/plugins/paypal/preview?id='+ config.id_job + '&pass=' + config.password;
            $(document).on('click', '.open-screenshot-button', function () {
                if (self.windowPreview && !self.windowPreview.closed) {
                    self.windowPreview.focus()
                } else {
                    self.windowPreview = window.open(url, "_blank", "toolbar=no,scrollbars=yes,resizable=no,top=500,left=500,width=1100,height=1000");
                }
            });

        },
        createButtons: function() {
            originalCreateButtons.apply(this);
            var buttonsOb = $('#segment-' + this.currentSegmentId + '-buttons');
            var button = '<li class="right"><a class="open-screenshot-button">' +
                '<span class="icon icon-picture"></span>' +
                'Open</a></li>';
            buttonsOb.prepend(button);
        },
        selectSegmentFromPreview: function (e) {
            if (e.key === UI.localStorageCurrentSegmentId) {
                this.gotoSegment(e.newValue)
            }
        }

    });

})() ;