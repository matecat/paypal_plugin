let AppDispatcher = require('../dispatcher/AppDispatcher');
let EventEmitter = require('events').EventEmitter;
let Constants = require('../costansts');
let assign = require('object-assign');
let Immutable = require('immutable');

EventEmitter.prototype.setMaxListeners(0);

let Store = assign({}, EventEmitter.prototype, {
    segments : Immutable.fromJS([]),

    previews : Immutable.fromJS([]),

    previewsStatus : Immutable.fromJS({}),

    storeData: function (data) {
        this.segments = Immutable.fromJS(data.segments);
        this.previews = Immutable.fromJS(data.previews);
    },

    getSegmentInfo: function (sid) {
        return this.segments.find(function (item) {
            return item.get('segment') === parseInt(sid);
        });
    },

    getPreviewsSegments: function (previewName) {
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
        if (Store.segments.size ) {
            segments.forEach(function ( segment ) {
                Store.segments = Store.segments.update(
                    Store.segments.findIndex(function(item) {
                        return item.get('segment') === segment.id_segment;
                    }), function(item) {
                        return item.merge(Immutable.fromJS(segment));
                    }
                );
            });
        }
    },

    updateSegment: function ( sid, data ) {
        if (Store.segments.size ) {
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
        }
    },

    approveSegments: function ( segments ) {
        if (Store.segments.size ) {
            segments.forEach(function ( segment ) {
                Store.segments = Store.segments.update(
                    Store.segments.findIndex(function(item) {
                        return item.get('segment') === segment;
                    }), function(item) {
                        return item.set('status', 'APPROVED');
                    }
                );
            });
        }
    },

    addIssuesToSegment: function ( sid, issues ) {
        if (Store.segments.size) {
            Store.segments = Store.segments.update(
                Store.segments.findIndex(function(item) {
                    return item.get('segment') === parseInt(sid);
                }), function(item) {
                    item = item.set('issues', Immutable.fromJS(issues));
                    return item;
                }
            );
        }
    },

    removeIssuesSegment: function ( sid, issueId ) {
        if (Store.segments.size ) {
            Store.segments = Store.segments.update(
                Store.segments.findIndex(function(item) {
                    return item.get('segment') === parseInt(sid);
                }), function(item) {
                    let issues = item.get('issues');
                    issues = issues.delete(issues.findIndex(function ( item ) {
                        return item.id === parseInt(issueId);
                    }));
                    item = item.set('issues', Immutable.fromJS(issues));
                    return item;
                }
            );
        }
    },
    /**
     *
     * @param {String} preview : name preview to set
     * @param {Boolean} set : if it is set to true it creates the element even if it is not present in previewsStatus Store
     */

    setCache: function ( preview,set ) {
        let status = true;

        if(set || Store.previewsStatus.get(preview)){
            Store.getPreviewsSegments(preview).forEach(e =>{
                if(e.toJS().status !== 'APPROVED'){
                    status = false;
                }
            });
            Store.previewsStatus = Store.previewsStatus.set(preview,Immutable.fromJS({
                approved: status}));
        }
    },
    setCacheFromSegment: function ( sid ) {
        let previewsArray = Store.previews.filter(function (item ) {
            return item.indexOf(parseInt(sid)) >-1;
        }).reduce((a,item,index)=>{
            a.push(index);
            return a;
        },[]);

        previewsArray.forEach(preview =>{
            this.setCache(preview,false);
        })
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
            Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview), Store.previews);
            break;
        case Constants.UPDATE_VIEW:
            if (Store.currentSegmentId === parseInt(action.sid) ){
                return;
            }
            segment = Store.getSegmentInfo(action.sid);
            Store.currentSegmentId = action.sid;
            Store.currentPreview = Store.getPreviewName(segment);
            Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview), Store.previews);
            break;
        case Constants.SELECT_SEGMENT:
            Store.currentSegmentId = action.sid;
            if (Store.currentPreview === action.preview) {
                Store.emitChange(action.actionType, action.sid);
            } else {
                Store.currentPreview = action.preview;
                Store.emitChange(action.actionType, action.sid, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview), Store.previews);
            }
            break;
        case Constants.UPDATE_SEGMENTS_INFO:
            Store.updateSegmentsPreview(action.segments);
            Store.emitChange(action.actionType, action.preview, Store.getPreviewsSegments( Store.currentPreview),Store.previewsStatus);
            break;
        case Constants.UPDATE_PREVIEW_STATUS:
            Store.setCache( action.preview, action.set);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments(Store.currentPreview),Store.previewsStatus);
            break;
        case Constants.UPDATE_SEGMENT:
            Store.updateSegment(action.sid, action.data);
            Store.setCacheFromSegment(action.sid);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview),Store.previewsStatus);
            break;
        case Constants.ADD_ISSUES:
            Store.addIssuesToSegment(action.sid, action.issues);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview),Store.previewsStatus);
            break;
        case Constants.REMOVE_ISSUE:
            Store.removeIssuesSegment(action.sid, action.issue);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview),Store.previewsStatus);
            break;
        case Constants.APPROVE_SEGMENTS:
            Store.approveSegments(action.segments);
            Store.emitChange(Constants.UPDATE_SEGMENTS_INFO, Store.currentPreview, Store.getPreviewsSegments( Store.currentPreview),Store.previewsStatus);
            break;
        default:
            Store.emitChange(action.actionType);
            break;

    }
});

module.exports = Store;