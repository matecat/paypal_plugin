let PreviewContainer = require('./components/PreviewContainer').default;
let PreviewActions = require('./actions/PreviewActions');
let Constants = require('./costansts');
let Store = require('./store/PreviewsStore');

    let PREVIEW = {

        // dataTest: JSON.parse('{"data":{"segments":[{"segment":673636,"previews":[{"path":"static/src/css/paypal/","file_index":"login.png","file_w":1126,"file_h":559,"w":362,"h":42,"samplePh":[],"fidelity":100,"x":370,"y":105}]},{"segment":673637,"previews":[{"path":"static/src/css/paypal/","file_index":"login.png","file_id":1,"file_w":1126,"file_h":559,"w":362,"h":42,"samplePh":[],"fidelity":100,"x":370,"y":160}]},{"segment":673638,"previews":[{"path":"static/src/css/paypal/","file_index":"login.png","file_id":1,"file_w":1126,"file_h":559,"w":362,"h":42,"samplePh":[],"fidelity":100,"x":370,"y":233},{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":85,"h":40,"samplePh":[],"fidelity":100,"x":1123,"y":3},{"path":"static/src/css/paypal/","file_index":"home1.png","file_id":1,"file_w":1272,"file_h":798,"w":362,"h":42,"samplePh":[],"fidelity":100,"x":370,"y":233}]},{"segment":673639,"previews":[{"path":"static/src/css/paypal/","file_index":"login.png","file_id":1,"file_w":1126,"file_h":559,"w":191,"h":43,"samplePh":[],"fidelity":100,"x":451,"y":285}]},{"segment":673640,"previews":[{"path":"static/src/css/paypal/","file_index":"login.png","file_id":1,"file_w":1126,"file_h":559,"w":140,"h":36,"samplePh":[],"fidelity":100,"x":484,"y":371}]},{"segment":673641,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":591,"h":100,"samplePh":[],"fidelity":100,"x":91,"y":372}]},{"segment":673642,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":342,"h":33,"samplePh":[],"fidelity":100,"x":791,"y":135}]},{"segment":673643,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":228,"h":33,"samplePh":[],"fidelity":100,"x":791,"y":169}]},{"segment":673644,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":134,"h":33,"samplePh":[],"fidelity":100,"x":834,"y":208}]},{"segment":673645,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":402,"h":77,"samplePh":[],"fidelity":100,"x":838,"y":249}]},{"segment":673646,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":402,"h":77,"samplePh":[],"fidelity":100,"x":838,"y":249}]},{"segment":673647,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":134,"h":33,"samplePh":[],"fidelity":100,"x":834,"y":338}]},{"segment":673648,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":402,"h":77,"samplePh":[],"fidelity":100,"x":838,"y":376}]},{"segment":673649,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":402,"h":77,"samplePh":[],"fidelity":100,"x":838,"y":376}]},{"segment":673650,"previews":[{"path":"static/src/css/paypal/","file_index":"register.png","file_id":2,"file_w":1340,"file_h":648,"w":399,"h":48,"samplePh":[],"fidelity":100,"x":809,"y":467}]}],"previews":[{"login.png":[673636,673637,673638,673639,673640]},{"register.png":[673638,673641,673641,673642,673643,673644,673645,673646,673647,673648,673649,673650]},{"home1.png":[673638]}]}}'),

        init: function () {
            this.isMac = (navigator.platform == 'MacIntel')? true : false;
            this.idJob = APP.getParameterByName("id");
            this.passJob = APP.getParameterByName("pass");
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
                    "standard": "ctrl+right",
                    "mac": "meta+right",
                }
            };
            PREVIEW.shortcuts.previousPreview = {
                "label" : "Next Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "ctrl+left",
                    "mac": "meta+left",
                }
            };
            PREVIEW.shortcuts.nextSegment = {
                "label" : "Next Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+right",
                    "mac": "alt+ctrl+right",
                }
            };
            PREVIEW.shortcuts.previousSegment = {
                "label": "Previous Preview Segment",
                "equivalent": "",
                "keystrokes": {
                    "standard": "shift+ctrl+left",
                    "mac": "alt+ctrl+left",
                }
            };
            PREVIEW.shortcuts.lastSegment = {
                "label" : "Last Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "shift+ctrl+pagedown",
                    "mac": "alt+ctrl+pagedown",
                }
            };
            PREVIEW.shortcuts.firstSegment = {
                "label" : "First Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "shift+ctrl+pageup",
                    "mac": "alt+ctrl+pageup",
                }
            };
            $("body").on('keydown.shortcuts', null, PREVIEW.shortcuts.nextPreview.keystrokes.standard, function(e) {
                PreviewActions.nextPreview();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.nextPreview.keystrokes.mac, function(e) {
                PreviewActions.nextPreview();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousPreview.keystrokes.standard, function(e) {
                PreviewActions.prevPreview();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousPreview.keystrokes.mac, function(e) {
                PreviewActions.prevPreview();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.nextSegment.keystrokes.standard, function(e) {
                PreviewActions.nextSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.nextSegment.keystrokes.mac, function(e) {
                PreviewActions.nextSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousSegment.keystrokes.standard, function(e) {
                PreviewActions.prevSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.previousSegment.keystrokes.mac, function(e) {
                PreviewActions.prevSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.firstSegment.keystrokes.standard, function(e) {
                PreviewActions.firstSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.firstSegment.keystrokes.mac, function(e) {
                PreviewActions.firstSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.lastSegment.keystrokes.standard, function(e) {
                PreviewActions.lastSegment();
            }).on('keydown.shortcuts', null, PREVIEW.shortcuts.lastSegment.keystrokes.mac, function(e) {
                PreviewActions.lastSegment();
            })
        },

    };
    $(document).ready(function() {
        PREVIEW.init();
    });

    module.exports = PREVIEW;



