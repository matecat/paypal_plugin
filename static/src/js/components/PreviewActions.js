let Actions = require('./../actions/PreviewActions');


class PreviewActions extends React.Component {

    constructor(props) {
        super(props);
    }

    getCurrentSegment() {
        let self = this;
        return this.props.segmentsInfo.find(function (segment) {
            return segment.get('segment') === parseInt(self.props.currentSid)
        });
    }

    goToNextSegmentImage() {
        let self = this;
        let index = this.props.segmentPreviews.findIndex(function (item) {
            return item.get('file_index') === self.props.currentPreview;
        });
        let next;
        if (this.props.segmentPreviews.get(index+1)) {
            next = index+1;
        } else {
            next = 0;
        }

        Actions.selectSegment(this.props.currentSid, this.props.segmentPreviews.get(next).get('file_index'));
    }

    goToPreviousSegmentImage() {
        let self = this;
        let index = this.props.segmentPreviews.findIndex(function (item) {
            return item.get('file_index') === self.props.currentPreview;
        });
        let next;
        if (this.props.segmentPreviews.get(index - 1)) {
            next = index - 1;
        } else {
            next = this.props.segmentPreviews.size - 1;
        }

        Actions.selectSegment(this.props.currentSid, this.props.segmentPreviews.get(next).get('file_index'));
    }

    nextImage() {
        let self = this;
        let index = this.props.previews.keySeq().findIndex(function (k, i) {
            return k === self.props.currentPreview;
        });
        let nextIndex = index+1;
        let previewName;
        let next;
        if (!this.props.previews.toArray()[nextIndex]) {
            nextIndex = 0;
        }
        next = this.props.previews.toArray()[nextIndex];
        this.props.previews.keySeq().findIndex(function (k, i) {
            if (i === nextIndex) {
                previewName = k;
            }
            return false;
        });
        Actions.selectSegment(next.first(), previewName);
    }

    previousImage() {
        let self = this;
        let index = this.props.previews.keySeq().findIndex(function (k, i) {
            return k === self.props.currentPreview;
        });
        let nextIndex = index-1;
        let previewName;
        let next;
        if (!this.props.previews.toArray()[nextIndex]) {
            nextIndex = this.props.previews.size-1;
        }
        next = this.props.previews.toArray()[nextIndex];
        this.props.previews.keySeq().findIndex(function (k, i) {
            if (i === nextIndex) {
                previewName = k;
            }
            return false;
        });
        Actions.selectSegment(next.first(), previewName);
    }

    openWindow() {
        Actions.openWindow();
    }

    componentDidMount() {
    }

    componentWillUnmount() {
    }

    componentDidUpdate() {}

    render() {

        return <div className="preview-actions-container">




                <div className="preview-pp actions-segment">
                    { this.props.segmentPreviews.size > 1 ? (
                    <div>
                        <button className="preview-button previous"
                             onClick={this.goToPreviousSegmentImage.bind(this)}> <i className="icon icon-chevron-left" /> </button>
                        <button className="preview-button next"
                        onClick={this.goToNextSegmentImage.bind(this)}> <i className="icon icon-chevron-right" /> </button>
                    </div>

                    ) : (null) }
                </div>



            <div className="preview-pp actions-image">
                <button className="preview-button previous"
                        onClick={this.previousImage.bind(this)}
                >Previous Preview</button>
                <button className="preview-button previous ui"
                        onClick={this.nextImage.bind(this)}
                >Next Preview</button>
                <button> </button>
                <button> </button>
            </div>


            <div className="preview-pp change-window">
                <button className="preview-button previous">
                    <i className="icon icon-chevron-left" />
                </button>
                <button className="preview-button next">
                    <i className="icon icon-chevron-right" />
                </button>
            </div>

            {this.props.showFullScreenButton ? (
                <button className="preview-button previous ui right floated blue button"
                        onClick={this.openWindow.bind(this)}>Open in a Window</button>
            ) : (null) }


        </div>

    }
}


export default PreviewActions ;

