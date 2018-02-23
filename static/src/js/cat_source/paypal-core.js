let PreviewContainer = require('../components/PreviewContainer').default;
let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');
let Split = require('split.js');
let Utils = require('./paypalUtils');


(function() {
    var originalStart = UI.start;
    var originalSetEvents = UI.setEvents;
    var originalSetLastSegmentFromLocalStorage = UI.setLastSegmentFromLocalStorage;
    var originalActivateSegment = UI.activateSegment;
    var originalAnimateScroll = UI.animateScroll;
    var originalSetShortcuts = UI.setShortcuts;
    var originalLoadCustimization = UI.loadCustomization;
    var originalisMarkedAsCompleteClickable = UI.isMarkedAsCompleteClickable;
    var originalIsReadonlySegment = UI.isReadonlySegment;
    var original_messageForClickOnReadonly = UI.messageForClickOnReadonly ;
    var original_isUnlockedSegment = UI.isUnlockedSegment ;

    $.extend(UI, {
        windowPreview: null,

        scrollSelector: "#outer",
        /**
         * Overwrite the start of matecat to che reference files
         */
        start: function () {
            originalStart.apply(this);
            this.checkReferenceFiles();
        },
        /**
         * Overwrite the matecat fn to add events and listeners
         */
        setEvents: function () {
            let self  = this;

            originalSetEvents.apply(this);

            // To make tab Footer messages opened by default
            SegmentActions.registerTab('messages', true, false);



            Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
            Store.addListener(Constants.OPEN_WINDOW, this.openWindow.bind(this));
            Store.addListener(Constants.CLOSE_WINDOW, this.closePreview.bind(this));


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
            }).on('keydown', function ( e ) {

                var esc = 27 ;

                var handleEscPressed = function(e) {
                    if ( config.isLQA ) {
                        e.preventDefault();
                        e.stopPropagation();
                        UI.closeSegmentsContainer();
                    }
                }

                if ( e.which == esc ) handleEscPressed(e) ;
            });


            this.createPreviewContainer();

        },
        /**
         * To set custom shortcuts
         */
        setShortcuts: function() {
            originalSetShortcuts.apply(this);

            UI.shortcuts.nextPreview = {
                "label" : "Next Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+right",
                    "mac": "option+ctrl+right",
                }
            };
            UI.shortcuts.previousPreview = {
                "label" : "Previous Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+left",
                    "mac": "option+ctrl+left",
                }
            };
            UI.shortcuts.nextSegment = {
                "label" : "Next Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+down",
                    "mac": "option+ctrl+down",
                }
            };
            UI.shortcuts.previousSegment = {
                "label": "Previous Preview Segment",
                "equivalent": "",
                "keystrokes": {
                    "standard": "alt+ctrl+up",
                    "mac": "option+ctrl+up",
                }
            };
            UI.shortcuts.lastSegment = {
                "label" : "Last Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+pagedown",
                    "mac": "option+ctrl+fn+down",
                }
            };
            UI.shortcuts.firstSegment = {
                "label" : "First Preview Segment",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+pageup",
                    "mac": "option+ctrl+fn+up",
                }
            };
            UI.shortcuts.nextSegmentPreview = {
                "label" : "Next Segment Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+n",
                    "mac": "option+ctrl+n",
                }
            };
            UI.shortcuts.previousSegmentPreview = {
                "label" : "Previous Segment Preview",
                "equivalent": "",
                "keystrokes" : {
                    "standard": "alt+ctrl+p",
                    "mac": "option+ctrl+p",
                }
            };
        },
        /**
         * Overwrite matecate function activateSegment
         * @param segment
         */
        activateSegment: function (segment) {
            originalActivateSegment.apply(this, [segment]);
        },
        /**
         * Overwrite matecate function animateScroll
         * @param segment
         * @param speed
         * @returns {*}
         */
        animateScroll: function (segment, speed) {
            var scrollAnimation = $( UI.scrollSelector ).stop().delay( 300 );
            var pos = 0;
            var prev = segment.prev('section') ;
            var segmentOpen = $('section.editor');

            if (!config.isLQA) {
                // XXX: this condition is necessary **only** because in case of first segment of a file,
                // the previous element (<ul>) has display:none style. Such elements are ignored by the
                // the .offset() function.
                var searchH = ($('.searchbox:visible').length) ? $('.searchbox:visible').height() : 0;
                var commonOffset = $('.header-menu').height() + searchH - 20 ;
                pos = segment.offset().top  - segment.offsetParent('#outer').offset().top + commonOffset;

                if ( segmentOpen.length && UI.getSegmentId(segment) !== UI.getSegmentId(segmentOpen)) {
                    pos = pos - segmentOpen.find('.footer').height();
                }


                scrollAnimation.animate({
                    scrollTop: pos
                }, speed);
            }

            return scrollAnimation.promise() ;
        },
        /**
         * To open the preview panel in a new window
         */
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
        /**
         *
         */
        setHideMatches: function () {
            var cookieName = (config.isReview)? 'hideMatchesReview' : 'hideMatches';
            Cookies.set(cookieName + '-' + config.id_job, false, { expires: 30 });
            UI.body.removeClass('hideMatches');
            if(UI.currentSegment){
                UI.currentSegment.find('.footer').removeClass('showMatches');
            }

        },
        /**
         * Function called after the click on a segment in the preview panel
         * @param sid
         */
        selectSegment: function (sid) {
            var el = $("section:not(.opened) #segment-" + sid + "-target").find(".editarea, .targetarea");
            if (el.length > 0 ) {
                UI.editAreaClick(el[0]);
            }
        },
        /**
         * In LQA, to show the segment
         */
        showSegment: function (  ) {
            if (config.isLQA) {
                if ( this.spliInstance ) {
                    this.spliInstance.destroy();
                    delete(this.spliInstance);
                }
                var outerHeight = $( 'section.opened' ).outerHeight() + 200;
                var h = Math.floor( (outerHeight / $( '.main-container' ).height()) * 100 );
                var h2 = 100 - h;
                this.spliInstance = Split( ['#outer',
                    '#plugin-mount-point'], {
                    sizes: [h,
                        h2],
                    direction: 'vertical'
                } );
            }
        },
        /**
         *
         */
        closeSegmentsContainer: function (  ) {
            if ( this.spliInstance ) {
                this.spliInstance.destroy();
                delete(this.spliInstance);
            }
            $('#plugin-mount-point').css('height', '100%');
            $('#outer').css('height', '0');
        },
        /**
         * When a segment is selected and the preview is in a different window
         * @param e
         */
        selectSegmentFromPreview: function (e) {
            if (e.key === UI.localStorageCurrentSegmentId) {
                this.selectSegment(e.newValue);
            }
        },
        /**
         * Inizialize the preview container and split the containers
         */
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
                Shortcuts: UI.shortcuts,
                isLqa: config.isLQA
            }), mountPoint);
            this.getPreviewData().done(function (response) {
                if (!_.isNull(response.data.previews)) {
                    self.segmentsPreviews = response.data;
                    self.previewsData = response.data;
                    PreviewActions.renderPreview(currentId, response.data);
                    // Event captured by the footer Messages to show the preview
                    SegmentActions.renderPreview(currentId, response.data);
                    if (config.isLQA) {
                        $('#plugin-mount-point').css('height', '100%');
                        $('#outer').css('height', '0');
                    } else {
                        self.spliInstance = Split(['#outer', '#plugin-mount-point'], {
                            sizes: [100, 0],
                            direction: 'vertical'
                        });
                    }
                }
            });
        },
        /**
         * to get the previews info
         * @returns {*}
         */
        getPreviewData: function () {
            return Utils.getPreviewData();
        },
        /**
         * Overwrite matecate function setLastSegmentFromLocalStorage
         * @param segmentId
         */
        setLastSegmentFromLocalStorage: function (segmentId) {
            let self = this;
            setTimeout(function () {
                PreviewActions.updatePreview(segmentId);
                // Event captured by the footer Messages to show the preview
                SegmentActions.renderPreview(segmentId, self.previewsData);
            });
            originalSetLastSegmentFromLocalStorage.call(this, segmentId);
        },
        /**
         * To Close the preview container
         */
        closePreview: function () {
            $('#plugin-mount-point').css('height', 0);
            $('#outer').css('height', '100%');
        },
        /**
         * To Open the preview container
         */
        openPreview: function (sid,preview) {
            $('#plugin-mount-point').css('height', '45%');
            $('#outer').css('height', '55%');
            if(sid && preview){
                PreviewActions.selectSegment(sid,preview)
            }
            setTimeout(function () {
                UI.scrollSegment(UI.currentSegment);
            }, 100);
        },
        /**
         * Overwrite matecate function loadCustomization to show the tags always in extended mode
         */
        loadCustomization: function () {
            originalLoadCustimization.apply(this);
            UI.custom.extended_tagmode = true;
        },
        /**
         * Overwrite matecate function isMarkedAsCompleteClickable to know if si markable as complete
         */
        isMarkedAsCompleteClickable: function ( stats ) {
            if (config.isReview) {
                /**
                 * Review step
                 *
                 * In this case the job is markable as complete when 'DRAFT' count is 0
                 * and 'TRANSLATED' is < 0 and 'APPROVED' + 'REJECTED' > 0.
                 */

                return config.job_completion_current_phase == 'revise' &&
                    stats.APPROVED > 0 ;
            }
            else {
                /**
                 * Translation step
                 *
                 * This condition covers the case in which the project is pretranslated.
                 * When a project is pretranslated, the 'translated' count can be 0 or
                 * less.
                 */
                return config.job_completion_current_phase == 'translate' &&
                    stats.TRANSLATED > 0 ;
            }
        },
        /**
         * Overwrite matecate function isReadonlySegment to know if segment is read only
         */
        isReadonlySegment: function( segment ) {
            let result = originalIsReadonlySegment.apply(this, [segment]);
            let isReviewReadOnly = config.isReview && config.job_completion_current_phase !== 'revise' && config.job_marked_complete;
            return result || isReviewReadOnly ;
        },
        /**
         * Overwrite matecate function messageForClickOnReadonly to change the message on click on read only segment
         * @param section
         * @returns {*}
         */
        messageForClickOnReadonly: function( section ) {
            let isReviewReadOnly = config.isReview && config.job_completion_current_phase !== 'revise' && config.job_marked_complete;

            if ( UI.translateAndReadonly()) {
                return 'This job is currently under review. Segments are in read-only mode.';
            } else if (isReviewReadOnly){
                return 'This job is marked as complete. Segments are in read-only mode.';
            } else {
                return original_messageForClickOnReadonly() ;
            }
        },
        /**
         * To retrieve information about the reference files
         */
        checkReferenceFiles: function () {
            Utils.checkReferenceFiles().done(function (response) {
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
        },
        /**
         * To check if a segment is locked
         * @param segment
         * @returns {boolean}
         */
        isUnlockedSegment: function ( segment ) {
            if (config.isReview) {
                return true;
            } else {
                original_isUnlockedSegment.apply(this, [segment]);
            }
        }


    });

})() ;