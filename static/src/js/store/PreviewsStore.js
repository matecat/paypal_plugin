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
        if (_.isUndefined(segment)) return;
        let preview = segment.get('previews');
        if (preview && preview.size > 0 ) {
            return preview.first().get('file_index')
        }
        return undefined;
    },

    updateSegmentsPreview: function ( segments ) {
        segments.forEach(function ( segment ) {
            Store.segments = Store.segments.update(
                Store.segments.findIndex(function(item) {
                    return item.get('segment') === segment.id_segment;
                }), function(item) {
                    return item.merge(Immutable.fromJS(segment));
                }
            );
        });
    },

    updateSegment: function ( sid, data ) {
        Store.segments = Store.segments.update(
            Store.segments.findIndex(function(item) {
                return item.get('segment') === parseInt(sid);
            }), function(item) {
                item = item.set('translation', data.translation);
                item = item.set('version_number', data.version_number);
                item = item.set('status', data.status);
                return item;
            }
        );
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
        case Constants.UPDATE_SEGMENTS_INFO:
            Store.updateSegmentsPreview(action.segments);
            Store.emitChange(action.actionType, action.preview, Store.getPreviewsSegments(action.sid,  Store.currentPreview));
            break;
        case Constants.UPDATE_SEGMENT:
            Store.updateSegment(action.sid, action.data);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments(action.sid,  Store.currentPreview));
            break;
        default:
            Store.emitChange(action.actionType);
            break;

    }
});

module.exports = Store;