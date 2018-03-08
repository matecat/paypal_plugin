let PreviewContainer = require('../components/PreviewContainer').default;
let PreviewActions = require('../actions/PreviewActions');
let Split = require('split.js');
let Utils = require('./paypalUtils');


(function() {

    $.extend(UI, {
        windowPreview: null,

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
                $('#outer').addClass('show-segment');
                PreviewActions.showSegmentContainer();
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
            $('#outer').removeClass('show-segment');
            PreviewActions.closeSegmentContainer();
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
                UI.scrollSegment(UI.currentSegment, sid);
            }, 100);
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
        checkIstructions: function ( ) {
            let self = this;
            Utils.getJobInstructions().done(function (response) {
                if (response.data && !response.errors ) {
                    self.instructions = response.data;
                    self.openInstructionsModal();
                    //add link to the footer
                    var html = '<div class="project-instructions"><span><a>Job Instructions</a></span></div>';
                    $('footer .wrapper').append(html);

                }
            });
        },
        openInstructionsModal: function (  ) {
            var props = {
                text: this.instructions,
                successText: "Ok",
                successCallback: function() {
                    APP.ModalWindow.onCloseModal();
                }
            };
            APP.ModalWindow.showModalComponent(ConfirmMessageModal, props, "Job Instructions");
        }
    });

})() ;