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
                        <div className="n-segments-available">2/3</div>
                        <button className="preview-button next"
                        onClick={this.goToNextSegmentImage.bind(this)}> <i className="icon icon-chevron-right" /> </button>
                        <div className="text-n-segments-available">available screens for this segment</div>
                    </div>

                    ) : (null) }
                </div>



            <div className="preview-pp actions-image">
                <button className="preview-button previous"
                        onClick={this.previousImage.bind(this)}>
                    <i className="icon icon-chevron-left" />
                </button>

                <button className="preview-button next"
                        onClick={this.nextImage.bind(this)}>
                    <i className="icon icon-chevron-left" /> <i className="icon icon-chevron-left" />
                </button>

                <div className="info-icon">
                    <i className="icon icon-picture" />
                </div>

                <button>  <i className="icon icon-chevron-right" /> <i className="icon icon-chevron-right" /> </button>
                <button> <i className="icon icon-chevron-right" /> </button>
            </div>


            <div className="preview-pp change-window">
                {this.props.showFullScreenButton ? (
                    <button className="preview-button"
                            onClick={this.openWindow.bind(this)}><i className="icon icon-window" /> </button>
                ) : (null) }

            </div>

        </div>

    }
}


export default PreviewActions ;

