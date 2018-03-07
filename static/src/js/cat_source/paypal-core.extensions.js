let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');


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
    var original_setTranslation_success = UI.setTranslation_success;
    var original_addIssuesToSegment = UI.addIssuesToSegment;
    var original_deleteSegmentIssues = UI.deleteSegmentIssues;

    $.extend(UI, {

        scrollSelector: "#outer",

        /**
         * Overwrite the start of matecat to che reference files
         */
        start: function () {
            originalStart.apply(this);
            this.checkReferenceFiles();
            this.checkIstructions();
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
                    if ( config.isLQA && !UI.body.hasClass('side-tools-opened') ) {
                        e.preventDefault();
                        e.stopPropagation();
                        UI.closeSegmentsContainer();
                    }
                };

                if ( e.which == esc ) handleEscPressed(e) ;
            }).on('click', '.project-instructions', function ( e ) {
                e.preventDefault();
                UI.openInstructionsModal();
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
         * Overwrite matecat function activateSegment
         * @param segment
         */
        activateSegment: function (segment) {
            originalActivateSegment.apply(this, [segment]);
        },
        /**
         * Overwrite matecat function setTranslation_success
         * @param d
         * @param option
         */
        setTranslation_success(d, option) {
            original_setTranslation_success.apply(this, [d, option]);
            PreviewActions.updateSegment(option.id_segment, d.translation);
        },
        /**
         * Overwrite matecat function animateScroll
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
        addIssuesToSegment: function ( fileId, segmentId, versions ) {
            original_addIssuesToSegment.apply(this, [fileId, segmentId, versions]);
            PreviewActions.addIssuesToSegment(segmentId, versions);
        },

        deleteSegmentIssues: function ( fileId, segmentId, issue_id ) {
            original_deleteSegmentIssues.apply(this, [fileId, segmentId, issue_id]);
            PreviewActions.removeIssuesToSegment(segmentId, issue_id);
        },
        /**
         * Overwrite matecat function setLastSegmentFromLocalStorage
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
         * Overwrite matecat function loadCustomization to show the tags always in extended mode
         */
        loadCustomization: function () {
            originalLoadCustimization.apply(this);
            UI.custom.extended_tagmode = true;
        },
        /**
         * Overwrite matecat function isMarkedAsCompleteClickable to know if si markable as complete
         */
        isMarkedAsCompleteClickable: function ( stats ) {
            if (config.isLQA) {
                /**
                 * Review step
                 *
                 * In this case the job is markable as complete when 'DRAFT' count is 0
                 * and 'TRANSLATED' is < 0 and 'APPROVED' + 'REJECTED' > 0.
                 */

                return config.job_completion_current_phase == 'lqa' &&
                    stats.APPROVED > 0 ;
            }
            else {
                return originalisMarkedAsCompleteClickable.apply(this, [stats])
            }
        },
        /**
         * Overwrite matecat function isReadonlySegment to know if segment is read only
         */
        isReadonlySegment: function( segment ) {
            let result = originalIsReadonlySegment.apply(this, [segment]);
            let isReviewReadOnly = config.isReview && config.job_completion_current_phase !== 'revise' && config.job_marked_complete;
            return result || isReviewReadOnly ;
        },
        /**
         * Overwrite matecat function messageForClickOnReadonly to change the message on click on read only segment
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