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

    nextSegmentPreview: function () {
        AppDispatcher.dispatch({
            actionType: Constants.NEXT_SEGMENT_IMAGE,
        });
    }

};

module.exports = PreviewActions;
