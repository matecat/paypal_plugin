let PreviewContainer = require('./components/PreviewContainer').default;
let PreviewActions = require('./actions/PreviewActions');
let Constants = require('./costansts');
let Store = require('./store/PreviewsStore');

let PREVIEW = {

    init: function () {
        this.isMac = (navigator.platform == 'MacIntel')? true : false;
        this.idJob = config.id_job;
        this.passJob = config.password;
        this.storageKey = 'currentSegmentId-' + this.idJob + this.passJob;
        let currentId = localStorage.getItem(this.storageKey);
        this.setShortcuts();
        this.mountPreviewContainer(currentId);
        window.addEventListener("storage", this.showSegmentPreview.bind(this), true);
        Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
    },

    getData: function () {
        return $.ajax({
            async: true,
            type: "get",
            url : "/plugins/paypal/preview/" + this.idJob + "/" + this.passJob
        });
    },

    showSegmentPreview: function (e) {
        if (e.key === this.storageKey) {
            this.updatePreviewContainer(e.newValue);
        }
    },
    mountPreviewContainer: function (currentId) {
        let mountPoint = $("#preview-container")[0];
        ReactDOM.render(React.createElement(PreviewContainer, {
            sid: currentId,
            isMac: PREVIEW.isMac,
            Shortcuts: PREVIEW.shortcuts
        }), mountPoint);
        this.getData().done(function (response) {
            PreviewActions.renderPreview(currentId, response.data);
        });
    },
    updatePreviewContainer: function (currentId) {
        PreviewActions.updatePreview(currentId);
    },
    selectSegment: function (sid) {
        localStorage.setItem(this.storageKey, sid);
    },
    setShortcuts: function() {
        PREVIEW.shortcuts = {};
        PREVIEW.shortcuts.nextPreview = {
            "label" : "Next Preview",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+right",
                "mac": "option+ctrl+right",
            }
        };
        PREVIEW.shortcuts.previousPreview = {
            "label" : "Previous Preview",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+left",
                "mac": "option+ctrl+left",
            }
        };
        PREVIEW.shortcuts.nextSegment = {
            "label" : "Next Preview Segment",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+down",
                "mac": "option+ctrl+down",
            }
        };
        PREVIEW.shortcuts.previousSegment = {
            "label": "Previous Preview Segment",
            "equivalent": "",
            "keystrokes": {
                "standard": "alt+ctrl+up",
                "mac": "option+ctrl+up",
            }
        };
        PREVIEW.shortcuts.lastSegment = {
            "label" : "Last Preview Segment",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+pagedown",
                "mac": "option+ctrl+fn+down",
            }
        };
        PREVIEW.shortcuts.firstSegment = {
            "label" : "First Preview Segment",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+pageup",
                "mac": "option+ctrl+fn+up",
            }
        };
        PREVIEW.shortcuts.nextSegmentPreview = {
            "label" : "Next Segment Preview",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+n",
                "mac": "option+ctrl+n",
            }
        };
        PREVIEW.shortcuts.previousSegmentPreview = {
            "label" : "Previous Segment Preview",
            "equivalent": "",
            "keystrokes" : {
                "standard": "alt+ctrl+p",
                "mac": "option+ctrl+p",
            }
        };


        $("body").on('keydown.shortcuts', null, PREVIEW.shortcuts.nextPreview.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.nextPreview();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousPreview.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.prevPreview();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.nextSegment.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.nextSegment();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousSegment.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.prevSegment();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.firstSegment.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.firstSegment();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.lastSegment.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.lastSegment();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.nextSegmentPreview.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.nextSegmentPreview();
        }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousSegmentPreview.keystrokes.standard, function(e) {
            e.preventDefault();
            PreviewActions.previousSegmentPreview();
        });

    },

};
$(document).ready(function() {
    PREVIEW.init();
});

module.exports = PREVIEW;



