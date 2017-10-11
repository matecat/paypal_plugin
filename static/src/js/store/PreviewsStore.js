let AppDispatcher = require('../dispatcher/AppDispatcher');
let EventEmitter = require('events').EventEmitter;
let Constants = require('../costansts');
let assign = require('object-assign');
let Immutable = require('immutable');

EventEmitter.prototype.setMaxListeners(0);

let Store = assign({}, EventEmitter.prototype, {
    segments : [],

    previews : [],

    storeData: function (data) {
        this.segments = Immutable.fromJS(data.segments);
        this.previews = Immutable.fromJS(data.previews);
    },

    getSegmentInfo: function (sid) {
        return this.segments.find(function (item) {
            return item.get('segment') === parseInt(sid);
        });
    },

    getPreviewsSegments: function (sid, previewName) {
        if (_.isUndefined(previewName) ) {
            return  Immutable.fromJS([]);
        }
        let preview = this.previews.get(previewName)
        return this.segments.filter(function (item) {
            return preview.indexOf(item.get('segment')) > -1;
        });

    },

    getPreviewName: function (segment) {
        let preview = segment.get('previews');
        if (preview.size > 0 ) {
            return preview.first().get('file_index')
        }
        return undefined;
    },

    emitChange: function(event, args) {
        this.emit.apply(this, arguments);
    }

});


// Register callback to handle all updates
AppDispatcher.register(function(action) {
    let segment;
    switch(action.actionType) {
        case Constants.RENDER_VIEW:
            Store.storeData(action.data);
            segment = Store.getSegmentInfo(action.sid);
            Store.currentSegmentId = action.sid;
            Store.currentPreview = Store.getPreviewName(segment);
            Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments(action.sid, Store.currentPreview), Store.previews);
            break;
        case Constants.UPDATE_VIEW:
            if (Store.currentSegmentId === parseInt(action.sid) ){
                return;
            }
            segment = Store.getSegmentInfo(action.sid);
            Store.currentSegmentId = action.sid;
            Store.currentPreview = Store.getPreviewName(segment);
            Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments(action.sid,  Store.currentPreview), Store.previews);
            break;
        case Constants.SELECT_SEGMENT:
            Store.currentSegmentId = action.sid;
            if (Store.currentPreview === action.preview) {
                Store.emitChange(action.actionType, action.sid);
            } else {
                Store.currentPreview = action.preview;
                Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments(action.sid,  Store.currentPreview), Store.previews);
            }
            break;
        case Constants.OPEN_WINDOW:
            Store.emitChange(action.actionType,);
            break;
        case Constants.CLOSE_WINDOW:
            Store.emitChange(action.actionType,);
            break;
    }
});

module.exports = Store;