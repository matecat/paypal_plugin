let PreviewContainer = require('../components/PreviewContainer').default;
let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');
var Split = require('split.js');


(function() {
    var originalStart = UI.start;
    var originalSetEvents = UI.setEvents;
    var originalSetLastSegmentFromLocalStorage = UI.setLastSegmentFromLocalStorage;
    var originalActivateSegment = UI.activateSegment;
    var originalAnimateScroll = UI.animateScroll;
    var originalSetShortcuts = UI.setShortcuts;
    var originalLoadCustimization = UI.loadCustomization;

    $.extend(UI, {
        windowPreview: null,

        scrollSelector: "#outer",

        start: function () {
            originalStart.apply(this);
            this.checkReferenceFiles();
        },

        setEvents: function () {
            let self  = this;

            originalSetEvents.apply(this);

            // To make tab Footer messages opened by default
            SegmentActions.registerTab('messages', true, true);



            Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
            Store.addListener(Constants.OPEN_WINDOW, this.openWindow.bind(this));
            Store.addListener(Constants.CLOSE_WINDOW, this.closePreview.bind(this));

            Split(['#outer', '#plugin-mount-point'], {
                sizes: [60, 40],
                direction: 'vertical'
            });


            $("body").on('keydown.shortcuts', null, UI.shortcuts.nextPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.previousPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.prevPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.nextSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.previousSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.prevSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.firstSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.firstSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.lastSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.lastSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.nextSegmentPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextSegmentPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.previousSegmentPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.previousSegmentPreview();
            });


            this.createPreviewContainer();

        },
        setShortcuts: function() {
            originalSetShortcuts.apply(this);

            UI.shortcuts.nextPreview = {
                "label" : "Next Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+right",
                    "mac": "alt+ctrl+right",
                }
            };
            UI.shortcuts.previousPreview = {
                "label" : "Previous Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+left",
                    "mac": "alt+ctrl+left",
                }
            };
            UI.shortcuts.nextSegment = {
                "label" : "Next Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+down",
                    "mac": "alt+ctrl+down",
                }
            };
            UI.shortcuts.previousSegment = {
                "label": "Previous Preview Segment",
                "equivalent": "",
                "keystrokes": {
                    "standard": "alt+ctrl+up",
                    "mac": "alt+ctrl+up",
                }
            };
            UI.shortcuts.lastSegment = {
                "label" : "Last Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+pagedown",
                    "mac": "alt+ctrl+pagedown",
                }
            };
            UI.shortcuts.firstSegment = {
                "label" : "First Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+pageup",
                    "mac": "alt+ctrl+pageup",
                }
            };
            UI.shortcuts.nextSegmentPreview = {
                "label" : "Next Segment Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+n",
                    "mac": "alt+ctrl+n",
                }
            };
            UI.shortcuts.previousSegmentPreview = {
                "label" : "Previous Segment Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+p",
                    "mac": "alt+ctrl+p",
                }
            };
        },
        activateSegment: function (segment) {
            originalActivateSegment.apply(this, [segment]);
        },
        animateScroll: function (segment, speed) {
            var scrollAnimation = $( UI.scrollSelector ).stop().delay( 300 );
            var pos ;
            var prev = segment.prev('section') ;

            // XXX: this condition is necessary **only** because in case of first segment of a file,
            // the previous element (<ul>) has display:none style. Such elements are ignored by the
            // the .offset() function.
            var commonOffset = $('.header-menu').height() +
                $('.searchbox:visible').height() ;

            if ( prev.length ) {
                pos = prev.offset().top  - prev.offsetParent('#outer').offset().top + commonOffset;
            } else {
                pos = 0;
            }

            scrollAnimation.animate({
                scrollTop: pos
            }, speed);

            return scrollAnimation.promise() ;
        },

        openWindow: function () {
            if (this.windowPreview && !this.windowPreview.closed) {
                this.windowPreview.focus()
            } else {
                window.addEventListener("storage", this.selectSegmentFromPreview.bind(this), true);
                let url = '/plugins/paypal/preview/template/' + config.id_job + '/' + config.password;
                this.windowPreview = window.open(url, "_blank", "toolbar=no,scrollbars=yes,resizable=no,top=500,left=500,width=1024,height=600");
            }
            this.closePreview();
        },

        setHideMatches: function () {
            var cookieName = (config.isReview)? 'hideMatchesReview' : 'hideMatches';
            $.cookie(cookieName + '-' + config.id_job, false, { expires: 30 });
            UI.body.removeClass('hideMatches');
            if(UI.currentSegment){
                UI.currentSegment.find('.footer').removeClass('showMatches');
            }

        },

        selectSegment: function (sid) {
            var el = $("section:not(.opened) #segment-" + sid + "-target").find(".editarea, .targetarea");
            if (el.length > 0 ) {
                UI.editAreaClick(el[0]);
            }
        },
        selectSegmentFromPreview: function (e) {
            if (e.key === UI.localStorageCurrentSegmentId) {
                this.selectSegment(e.newValue);
            }
        },
        createPreviewContainer: function () {
            let storageKey = 'currentSegmentId-' +config.id_job + config.password;
            let currentId = localStorage.getItem(storageKey);
            let mountPoint = $("#plugin-mount-point")[0];
            let self = this;
            ReactDOM.render(React.createElement(PreviewContainer, {
                sid: currentId,
                classContainer: "preview-core-container",
                showInfo: false,
                showFullScreenButton: true,
                isMac: UI.isMac,
                Shortcuts: UI.shortcuts
            }), mountPoint);
            this.getPreviewData().done(function (response) {
                if (!_.isNull(response.data.previews)) {
                    self.segmentsPreviews = response.data;
                    self.previewsData = response.data;
                    PreviewActions.renderPreview(currentId, response.data);
                    // Event captured by the footer Messages to show the preview
                    SegmentActions.renderPreview(currentId, response.data);
                }
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
            let self = this;
            setTimeout(function () {
                PreviewActions.updatePreview(segmentId);
                // Event captured by the footer Messages to show the preview
                SegmentActions.renderPreview(segmentId, self.previewsData);
            });
            originalSetLastSegmentFromLocalStorage.call(this, segmentId);
        },

        closePreview: function () {
            $('#plugin-mount-point').css('height', 0);
            $('#outer').css('height', '100%');
        },

        openPreview: function (sid,preview) {
            $('#plugin-mount-point').css('height', '40%');
            $('#outer').css('height', '60%');
            if(sid && preview){
                PreviewActions.selectSegment(sid,preview)
            }
            setTimeout(function () {
                UI.scrollSegment(UI.currentSegment);
            }, 100);
        },

        loadCustomization: function () {
            originalLoadCustimization.apply(this);
            UI.custom.extended_tagmode = true;
        },

        checkReferenceFiles: function () {
            var path = sprintf(
                '/plugins/paypal/reference-files/%s/%s/list',
                config.id_job, config.password
            );
            $.ajax({
                type: "GET",
                url : path
            }).done(function (response) {
                if (response.reference.files && response.reference.files.length > 0 ) {
                    var htmlButton = '<li>' +
                        '<a title="References" alt="References" class="download-references" href="#" >' +
                        '<span class="icon-download"></span>REFERENCES' +
                        '</a>' +
                        '</li>';
                    $("#previewDropdown").append(htmlButton);
                    $('.download-references').on('click', function () {
                        var path = sprintf(
                            '/plugins/paypal/reference-files/%s/%s',
                            config.id_job, config.password
                        );

                        var iFrameDownload = $( document.createElement( 'iframe' ) ).hide().prop( {
                            id: 'iframeDownload_' + new Date().getTime() + "_" + parseInt( Math.random( 0, 1 ) * 10000000 ),
                            src: path
                        } );
                        $( "body" ).append( iFrameDownload );
                    });
                }
            });
        }


    });

})() ;