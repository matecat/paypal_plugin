let AppDispatcher = require('../dispatcher/AppDispatcher');
let Constants = require('./../costansts');
let Utils = require('../cat_source/paypalUtils');
let Store = require('../store/PreviewsStore');
let PreviewActions = {

    renderPreview: function (sid, data) {
        AppDispatcher.dispatch({
            actionType: Constants.RENDER_VIEW,
            sid: sid,
            data: data
        });
        let max = Store.previews.size;
        console.log(Store.previews);
        console.log(Store.previews.toJS());
        if(Store.previews.size > 4) max = 3;

        let previewsArray = Store.previews.reduce((a,item,index)=>{
            a.push(index);
            return a;
        },[]);

        let start = Math.max(previewsArray.indexOf(Store.currentPreview)-3,0);
        let end = Math.min(previewsArray.indexOf(Store.currentPreview)+3,previewsArray.length-1);

        for(start; start <= end; start++){
            this.updatePreviewSegments(previewsArray[start]);
        }

    },

    updatePreview: function (sid) {
        AppDispatcher.dispatch({
            actionType: Constants.UPDATE_VIEW,
            sid: sid
        });
    },

    selectSegment: function (sid, preview) {
        let currentPreview = Store.currentPreview;
        AppDispatcher.dispatch({
            actionType: Constants.SELECT_SEGMENT,
            sid: sid,
            preview: preview
        });
        if (currentPreview !== preview) {
            this.updatePreviewSegments(preview)
        }
    },

    updatePreviewSegments: function ( preview ) {
        let self = this;
        let segments = Store.getPreviewsSegments( preview);
        let segmentsArray = segments.reduce(function ( newList, item ) {
            newList.push(item.get('segment'));
            return newList;
        }, []);
        // Use cache
        console.log('require preview',preview,!!Store.previewsStatus.get(preview));
        if(!Store.previewsStatus.get(preview)){
            Utils.getSegmentsPreviewInfo(segmentsArray).done(function ( response ) {
                if (response.data) {
                    AppDispatcher.dispatch({
                        actionType: Constants.UPDATE_SEGMENTS_INFO,
                        preview: preview,
                        segments: response.data
                    });
                }
                self.updatePreviewStatus(preview,true);
            });
        }
    },

    updatePreviewStatus: function (preview, set) {
        AppDispatcher.dispatch({
            actionType: Constants.UPDATE_PREVIEW_STATUS,
            preview: preview,
            set: set
        });
    },

    updateSegment: function ( sid, data ) {
        AppDispatcher.dispatch({
            actionType: Constants.UPDATE_SEGMENT,
            sid: sid,
            data: data
        });
    },

    openSegment: function (sid) {
        UI.showSegment();
    },

    openWindow: function () {
        AppDispatcher.dispatch({
            actionType: Constants.OPEN_WINDOW,
        });
    },

    closePreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.CLOSE_WINDOW,
        });
    },

    nextPreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.NEXT_PREVIEW,
        });
    },

    prevPreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.PREV_PREVIEW,
        });
    },

    nextSegment: function () {
        AppDispatcher.dispatch({
            actionType: Constants.NEXT_SEGMENT,
        });
    },

    prevSegment: function () {
        AppDispatcher.dispatch({
            actionType: Constants.PREV_SEGMENT
        });
    },

    lastSegment: function () {
        AppDispatcher.dispatch({
            actionType: Constants.LAST_SEGMENT,
        });
    },

    firstSegment: function () {
        AppDispatcher.dispatch({
            actionType: Constants.FIRST_SEGMENT
        });
    },
    nextSegmentPreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.NEXT_SEGMENT_PREVIEW
        });
    },
    previousSegmentPreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.PREV_SEGMENT_PREVIEW
        });
    },
    openSliderPreviews: function (  ) {
        AppDispatcher.dispatch({
            actionType: Constants.OPEN_SLIDER
        });
    },
    addIssuesToSegment: function ( segmentId, versions ) {
        let issues = _.reduce(versions, function ( result, value ) {
            if (value.issues.length > 0) {
                result = _.concat(result, value.issues);
            }
            return result;
        }, []);
        if (issues.length > 0) {
            AppDispatcher.dispatch({
                actionType: Constants.ADD_ISSUES,
                sid: segmentId,
                issues: issues
            });
        }
    },

    removeIssuesToSegment: function ( segmentId, issue_id ) {
        AppDispatcher.dispatch({
            actionType: Constants.REMOVE_ISSUE,
            sid: segmentId,
            issue: issue_id
        });
    }
};

module.exports = PreviewActions;
