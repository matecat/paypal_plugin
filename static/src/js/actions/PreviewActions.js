let AppDispatcher = require('../dispatcher/AppDispatcher');
let Constants = require('./../costansts');

let PreviewActions = {

    renderPreview: function (sid, data) {
        AppDispatcher.dispatch({
            actionType: Constants.RENDER_VIEW,
            sid: sid,
            data: data
        });
    },

    updatePreview: function (sid) {
        AppDispatcher.dispatch({
            actionType: Constants.UPDATE_VIEW,
            sid: sid
        });
    },

    selectSegment: function (sid, preview) {
        AppDispatcher.dispatch({
            actionType: Constants.SELECT_SEGMENT,
            sid: sid,
            preview: preview
        });
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
    }

};

module.exports = PreviewActions;
