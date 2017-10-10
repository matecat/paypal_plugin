let PreviewContainer = require('../components/PreviewContainer').default;
let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');
let interact = require('interactjs');


(function() {
    var originalSetEvents = UI.setEvents;
    var originalCreateButtons = UI.createButtons;
    var originalSetLastSegmentFromLocalStorage = UI.setLastSegmentFromLocalStorage;

    $.extend(UI, {
        windowPreview: null,

        setEvents: function () {
            let self  = this;
            originalSetEvents.apply(this);

            this.createPreviewContainer();

            Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
            Store.addListener(Constants.OPEN_WINDOW, this.openWindow.bind(this));

            $(document).on('click', '.open-screenshot-button', this.openWindow.bind(this));

            interact('#plugin-mount-point')
                .resizable({
                    preserveAspectRatio: true,
                    edges: { left: false, right: false, bottom: false, top: true }
                })
                .on('resizemove', function (event) {
                    var target = event.target,
                        x = (parseFloat(target.getAttribute('data-x')) || 0),
                        y = (parseFloat(target.getAttribute('data-y')) || 0);

                    // update the element's style
                    // target.style.width  = event.rect.width + 'px';
                    target.style.height = event.rect.height + 'px';

                    var outerH = window.innerHeight - event.rect.height;
                    $('#outer').height(outerH);

                    // translate when resizing from top or left edges
                    // x += event.deltaRect.left;
                    y += event.deltaRect.top;

                    // target.style.webkitTransform = target.style.transform =
                        'translate(' + x + 'px,' + y + 'px)';

                    // target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                    // target.textContent = Math.round(event.rect.width) + '×' + Math.round(event.rect.height);
                });

        },

        createButtons: function() {
            originalCreateButtons.apply(this);
            var buttonsOb = $('#segment-' + this.currentSegmentId + '-buttons');
            var button = '<li><a class="open-screenshot-button">' +
                '<span class="icon icon-picture"></span></a></li>';
            buttonsOb.prepend(button);
        },

        openWindow: function () {
            if (this.windowPreview && !this.windowPreview.closed) {
                this.windowPreview.focus()
            } else {
                window.addEventListener("storage", this.selectSegmentFromPreview.bind(this), true);
                let url = '/plugins/paypal/preview?id='+ config.id_job + '&pass=' + config.password;
                this.windowPreview = window.open(url, "_blank", "toolbar=no,scrollbars=yes,resizable=no,top=500,left=500,width=1024,height=600");
            }
        },
        selectSegment: function (sid) {
            var el = $("section:not(.opened) #segment-" + sid + "-target").find(".editarea");
            if (el.length > 0 ) {
                UI.editAreaClick(el[0]);
            }
        },
        selectSegmentFromPreview: function (e) {
            if (e.key === UI.localStorageCurrentSegmentId) {
                this.gotoSegment(e.newValue);
            }
        },
        createPreviewContainer: function () {
            let storageKey = 'currentSegmentId-' +config.id_job + config.password;
            let currentId = localStorage.getItem(storageKey);
            let mountPoint = $("#plugin-mount-point")[0];
            ReactDOM.render(React.createElement(PreviewContainer, {
                sid: currentId,
                classContainer: "preview-core-container",
                showInfo: false,
                showFullScreenButton: true
            }), mountPoint);
            this.getPreviewData().done(function (response) {
                PreviewActions.renderPreview(currentId, response.data);
            });
        },

        getPreviewData: function () {
            return $.ajax({
                async: true,
                type: "get",
                url : "/plugins/paypal/preview/" + config.id_job + "/" + config.password
            });
        },

        setLastSegmentFromLocalStorage: function (segmentId) {
            setTimeout(function () {
                PreviewActions.updatePreview(segmentId);
            });
            originalSetLastSegmentFromLocalStorage.call(this, segmentId);
        },

    });

})() ;