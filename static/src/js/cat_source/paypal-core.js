let PreviewContainer = require('../components/PreviewContainer').default;
let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');

(function() {
    var originalSetEvents = UI.setEvents;
    var originalSetLastSegmentFromLocalStorage = UI.setLastSegmentFromLocalStorage;

    $.extend(UI, {
        windowPreview: null,

        setEvents: function () {
            let self  = this;
            originalSetEvents.apply(this);

            this.createPreviewContainer();

            Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
            Store.addListener(Constants.OPEN_WINDOW, this.openWindow.bind(this));

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
            this.gotoSegment(sid)
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
            originalSetLastSegmentFromLocalStorage.call(this, segmentId);
            setTimeout(function () {
                PreviewActions.updatePreview(segmentId);
            });
        },

    });

})() ;