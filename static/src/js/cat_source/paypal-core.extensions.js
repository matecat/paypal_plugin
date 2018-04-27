let PreviewActions = require('../actions/PreviewActions');
let Constants = require('../costansts');
let Store = require('../store/PreviewsStore');
let showdown = require( "showdown" );

(function(SF) {

    var originalStart = UI.start;
    var originalSetEvents = UI.setEvents;
    var originalSetLastSegmentFromLocalStorage = UI.setLastSegmentFromLocalStorage;
    var originalActivateSegment = UI.activateSegment;
    // var originalAnimateScroll = UI.animateScroll;
    var originalSetShortcuts = UI.setShortcuts;
    var originalLoadCustimization = UI.loadCustomization;
    var originalIsReadonlySegment = UI.isReadonlySegment;
    var original_messageForClickOnReadonly = UI.messageForClickOnReadonly ;
    var original_isUnlockedSegment = UI.isUnlockedSegment ;
    var original_setTranslation_success = UI.setTranslation_success;
    var original_addIssuesToSegment = UI.addIssuesToSegment;
    var original_deleteSegmentIssues = UI.deleteSegmentIssues;
    var originalGotoNextSegment = UI.gotoNextSegment;
    var originalisMarkedAsCompleteClickable = UI.isMarkedAsCompleteClickable;
    var originalsetProgress = UI.setProgress;
    $.extend(UI, {

        /**
         * Overwrite the start of matecat to che reference files
         */
        start: function () {
            originalStart.apply(this);
            this.checkReferenceFiles();
            this.checkInstructions();
            var cookieName = (config.isReview)? 'hideMatchesReview' : 'hideMatches';
            Cookies.set(cookieName + '-' + config.id_job, false, { expires: 30 });

            // if (config.isReview) {
            //     $('body').addClass('revise-page');
            // } else {
            //     $('body').addClass('translate-page');
            // }
        },
        /**
         * Overwrite the matecat fn to add events and listeners
         */
        setEvents: function () {
            let self  = this;

            originalSetEvents.apply(this);

            // To make tab Footer messages opened by default
            if (config.isReview) {
                SegmentActions.registerTab('messages', true, true);
            } else {
                SegmentActions.registerTab('messages', true, false);
            }



            Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
            Store.addListener(Constants.OPEN_WINDOW, this.openWindow.bind(this));
            Store.addListener(Constants.CLOSE_WINDOW, this.closePreview.bind(this));


            $("body").on('keydown.shortcuts', null, UI.shortcuts.paypal.events.nextPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.previousPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.prevPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.nextSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.previousSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.prevSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.firstSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.firstSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.lastSegment.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.lastSegment();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.nextSegmentPreview.keystrokes.standard, function(e) {
                e.preventDefault();
                PreviewActions.nextSegmentPreview();
            }).on('keydown.shortcuts', null, UI.shortcuts.paypal.events.previousSegmentPreview.keystrokes.standard, function(e) {
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
            UI.shortcuts.paypal = {
                label: "Preview",
                events: {
                    'nextPreview' : {
                        "label": "Next Preview",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+right",
                            "mac": "option+ctrl+right",
                        }
                    },
                    'previousPreview' : {
                        "label": "Previous Preview",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+left",
                            "mac": "option+ctrl+left",
                        }
                    },
                    'nextSegment' : {
                        "label": "Next Preview Segment",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+down",
                            "mac": "option+ctrl+down",
                        }
                    },
                    'previousSegment' : {
                        "label": "Previous Preview Segment",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+up",
                            "mac": "option+ctrl+up",
                        }
                    },
                    'lastSegment' : {
                        "label": "Last Preview Segment",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+pagedown",
                            "mac": "option+ctrl+fn+down",
                        }
                    },
                    'firstSegment' : {
                        "label": "First Preview Segment",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+pageup",
                            "mac": "option+ctrl+fn+up",
                        }
                    },
                    'nextSegmentPreview' : {
                        "label": "Next Segment Preview",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+n",
                            "mac": "option+ctrl+n",
                        }
                    },
                    'previousSegmentPreview': {
                        "label": "Previous Segment Preview",
                        "equivalent": "",
                        "keystrokes": {
                            "standard": "alt+ctrl+p",
                            "mac": "option+ctrl+p",
                        }
                    }
                }
            }
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
            var article = segment.closest('article');

            if (!config.isLQA) {
                pos = segment.offset().top - segment.offsetParent('#outer').offset().top;

                if (article.prevAll('article').length > 0) {
                    _.forEach(article.prevAll('article'), function ( item ) {
                        pos = pos + $(item).outerHeight() + 140;
                    });
                }
                if ( segmentOpen.length && UI.getSegmentId(segment) !== UI.getSegmentId(segmentOpen)) {
                    pos = pos - segmentOpen.find('.footer').height() - 100;
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
            } else if (config.isReview) {
                return config.job_completion_current_phase == 'revise' &&
                    stats.DRAFT <= 0 && stats.TRANSLATED <=0 &&
                    ( stats.APPROVED + stats.REJECTED ) > 0 ;
            }  else {
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
         * Overwrite matecat function translateAndReadonly to know if a job is in ReadOnly mode
         * @returns {*}
         */
        translateAndReadonly: function (  ) {
            return !config.isReview && ( config.job_completion_current_phase === 'revise' ||
                    (config.job_completion_current_phase === 'translate' && config.job_marked_complete)
                );
        },
        /**
         * Overwrite matecat function clickMarkAsCompleteForTranslate to kshow message after 'mark as complete' clicked in TR
         * @returns {*}
         */
        clickMarkAsCompleteForTranslate: function (  ) {
            APP.confirm({
                callback: 'markAsCompleteSubmit',
                msg: 'By marking the job as complete, ' +
                'this page will be made available in read-only mode. ' +
                'Are you sure you want to mark this job as complete? '
            });
        },
        /**
         * Overwrite matecat function clickMarkAsCompleteForTranslate to kshow message after 'mark as complete' clicked in LR
         * @returns {*}
         */
        clickMarkAsCompleteForReview: function (  ) {
            APP.confirm({
                callback: 'markAsCompleteSubmit',
                msg: 'By marking the job as complete, ' +
                'this page will be made available in read-only mode. ' +
                'Are you sure you want to mark this job as complete? '
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
                return original_isUnlockedSegment.apply(this, [segment]);
            }
        },

        gotoNextSegment: function ( sid ) {
            if (!config.isLQA || ( typeof SF !== "undefined" && SF.filtering()) ) {
                originalGotoNextSegment.apply(this);
            }
            return false;
        },

        setProgress: function ( stats ) {
            originalsetProgress.apply(this, [ stats ]);
            if (config.isReview) {
                let total = stats.TOTAL;
                let approved = stats.APPROVED;
                let todo = Math.round( stats.TOTAL - stats.APPROVED);
                $('#stat-todo strong').html(todo);
            }
        },

        showFixWarningsModal: function (  ) {
            APP.confirm({
                name: 'markJobAsComplete', // <-- this is the name of the function that gets invoked?
                okTxt: 'Fix errors',
                callback: 'goToFirstError',
                msg: 'Unresolved issues may prevent downloading your translation. <br>Please fix the issues. <a style="color: #4183C4; font-weight: 700; text-decoration:' +
                ' underline;" href="https://www.matecat.com/support/advanced-features/understanding-fixing-tag-errors-tag-issues-matecat/" target="_blank">How to fix tags in MateCat </a> '
            });
        }

    });
    function overrideMatchesSource( SegmentTabMatches ) {
        let original_getMatchInfo = SegmentTabMatches.prototype.getMatchInfo;
        SegmentTabMatches.prototype.getMatchInfo = function ( match ) {
            let tmProperties = match.tm_properties;
            if (tmProperties && !_.isUndefined(tmProperties) ) {
                let userEmail = tmProperties.find(function ( item ) {
                    return item.type === "x-user";
                });
                let projectType = tmProperties.find(function ( item ) {
                    return item.type === "x-project_type";
                });
                let note = tmProperties.find(function ( item ) {
                    return item.type === "x-note";
                });
                let sourceHtml = <li className="graydesc">
                    Source:
                    <span className="bold">
                                        {match.cb}
                                    </span>
                </li>;
                let userMailHtml = <li className="graydesc">
                                        User ID: <span className="bold"> Anonymous </span>
                                    </li>;
                let projectTypeHtml, noteHtml = "";
                if (!_.isUndefined(userEmail)) {
                    userMailHtml = <li className="graydesc">
                                        User ID:
                                        <span className="bold"> {userEmail.value}</span>
                                    </li>
                }
                if (!_.isUndefined(projectType)) {
                    projectTypeHtml = <li className="graydesc">
                                            Project Type: <span className="bold"> {projectType.value}</span>
                                        </li>;
                }
                if (!_.isUndefined(note) && note.value) {
                    let converter = new showdown.Converter();
                    let text = converter.makeHtml( note.value );
                    let noteText = '<div class="tm-match-note-tooltip-content">'  + text + '</div>';
                    noteHtml = <li className="graydesc note-tm-match">
                                            <span className="bold tm-match-note-tooltip" data-html={noteText} data-variation="tiny"
                                                  ref={(tooltip) => this.noteTooltip = tooltip}>
                                                    Notes
                                                <i className="icon-info icon"/>
                                            </span>
                                        </li>;
                }

                return <ul className="graysmall-details">
                        <li className={'percent ' + match.percentClass}>
                            {match.percentText}
                        </li>
                        <li>
                            {match.suggestion_info}
                        </li>
                        {sourceHtml}
                        {userMailHtml}
                        {projectTypeHtml}
                        {noteHtml}
                    </ul>;

            }
            return original_getMatchInfo.apply(this, [match]);

        }
        let original_componentDidMount = SegmentTabMatches.prototype.componentDidMount;
        SegmentTabMatches.prototype.componentDidMount = function (  ) {
            original_componentDidMount.apply(this, arguments);
            setTimeout(function (  ) {
                $('.tm-match-note-tooltip').popup({hoverable: true});
            }, 1000);
        };
        let original_componentDidUpdate = SegmentTabMatches.prototype.componentDidUpdate;
        SegmentTabMatches.prototype.componentDidUpdate = function (  ) {
            original_componentDidUpdate.apply(this, arguments);
            setTimeout(function (  ) {
                $('.tm-match-note-tooltip').popup({hoverable: true});
            }, 1000);
        }
    }
    overrideMatchesSource(SegmentTabMatches);

})(SegmentFilter) ;