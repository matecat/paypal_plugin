let Actions = require('./../actions/PreviewActions');
let Store = require('../store/PreviewsStore');
let Constants = require('../costansts');

class PreviewActions extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            index: 1
        };
        this.nextImage = this.nextImage.bind(this);
        this.previousImage = this.previousImage.bind(this);
        this.nextSegment = this.nextSegment.bind(this);
        this.previousSegment = this.previousSegment.bind(this);
        this.lastSegment = this.lastSegment.bind(this);
        this.firstSegment = this.firstSegment.bind(this);
        this.goToNextSegmentImage = this.goToNextSegmentImage.bind(this);
        this.goToPreviousSegmentImage = this.goToPreviousSegmentImage.bind(this);
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
        setTimeout(function () {
            Actions.selectSegment(self.props.currentSid, self.props.segmentPreviews.get(next).get('file_index'));
        }, 0);
        this.setState({
            index: next+1
        });
    }

    goToPreviousSegmentImage() {
        let self = this;
        let index = this.props.segmentPreviews.findIndex(function (item) {
            return item.get('file_index') === self.props.currentPreview;
        });
        let next;
        if (index > 0) {
            next = index - 1;
        } else {
            next = this.props.segmentPreviews.size - 1;
        }

        setTimeout(function () {
            Actions.selectSegment(self.props.currentSid, self.props.segmentPreviews.get(next).get('file_index'));
        }, 0);
        this.setState({
            index: next+1
        });
    }

    nextImage() {
        let self = this;
        if (this.props.segmentPreviews) {
            let currentPreviewJson = this.props.segmentPreviews.find(function (item) {
                return item.get('file_index') === self.props.currentPreview;
            });
            let next = currentPreviewJson.get('nextPreview');
            setTimeout(function () {
                Actions.selectSegment(self.props.previews.get(next).first(), next);
            }, 0);
        } else {
            setTimeout(function () {
                Actions.selectSegment(self.props.previews.get(self.props.previews.keySeq().first()).first(), self.props.previews.keySeq().first());
            }, 0);
        }


    }

    previousImage() {
        let self = this;
        if (this.props.segmentPreviews) {
            let currentPreviewJson = this.props.segmentPreviews.find(function (item) {
                return item.get('file_index') === self.props.currentPreview;
            });
            let previous = currentPreviewJson.get('previousPreview');
            setTimeout(function () {
                Actions.selectSegment(self.props.previews.get(previous).first(), previous);
            }, 0);
        } else {
            setTimeout(function () {
                Actions.selectSegment(self.props.previews.get(self.props.previews.keySeq().last()).first(), self.props.previews.keySeq().last());
            }, 0);
        }
    }

    nextSegment() {
        let self = this;
        let arrLength = this.props.previews.get(this.props.currentPreview).size;
        let index = this.props.previews.get(this.props.currentPreview).indexOf(parseInt(this.props.currentSid));
        let next = ((index + 1) < arrLength ) ? index + 1 : 0;
        setTimeout(function () {
            Actions.selectSegment(self.props.previews.get(self.props.currentPreview).get(next), self.props.currentPreview);
        }, 0);
    }

    previousSegment() {
        let self = this;
        let arrLength = this.props.previews.get(this.props.currentPreview).size;
        let index = this.props.previews.get(this.props.currentPreview).indexOf(parseInt(this.props.currentSid));
        let previous = ((index - 1) < 0 ) ? arrLength - 1 : index-1;
        setTimeout(function () {
            Actions.selectSegment(self.props.previews.get(self.props.currentPreview).get(previous), self.props.currentPreview);
        }, 0);
    }

    firstSegment() {
        let self = this;
        setTimeout(function () {
            Actions.selectSegment(self.props.previews.get(self.props.currentPreview).get(0), self.props.currentPreview);
        }, 0);
    }

    lastSegment() {
        let self = this;
        let arrLength = this.props.previews.get(this.props.currentPreview).size;
        setTimeout(function () {
            Actions.selectSegment(self.props.previews.get(self.props.currentPreview).get(arrLength - 1), self.props.currentPreview);
        }, 0);
    }

    openWindow() {
        Actions.openWindow();
    }

    closePreview() {
        Actions.closePreview();
    }

    openPreviewParent() {
        window.opener.UI.openPreview();
        window.close();
    }
    openPreviewSlider() {
        Actions.openSliderPreviews();
    }
    componentDidMount() {
        Store.addListener(Constants.NEXT_PREVIEW, this.nextImage);
        Store.addListener(Constants.PREV_PREVIEW, this.previousImage);
        Store.addListener(Constants.NEXT_SEGMENT, this.nextSegment);
        Store.addListener(Constants.PREV_SEGMENT, this.previousSegment);
        Store.addListener(Constants.LAST_SEGMENT, this.lastSegment);
        Store.addListener(Constants.FIRST_SEGMENT, this.firstSegment);
        Store.addListener(Constants.NEXT_SEGMENT_PREVIEW, this.goToNextSegmentImage);
        Store.addListener(Constants.PREV_SEGMENT_PREVIEW, this.goToPreviousSegmentImage);
    }

    componentWillUnmount() {
        Store.removeListener(Constants.NEXT_PREVIEW, this.nextImage);
        Store.removeListener(Constants.PREV_PREVIEW, this.previousImage);
        Store.removeListener(Constants.NEXT_SEGMENT, this.nextSegment);
        Store.removeListener(Constants.PREV_SEGMENT, this.previousSegment);
        Store.removeListener(Constants.LAST_SEGMENT, this.lastSegment);
        Store.removeListener(Constants.FIRST_SEGMENT, this.firstSegment);
        Store.removeListener(Constants.NEXT_SEGMENT_PREVIEW, this.goToNextSegmentImage);
        Store.removeListener(Constants.PREV_SEGMENT_PREVIEW, this.goToPreviousSegmentImage);
    }

    componentDidUpdate() {}

    render() {
        let keyShortcuts = (this.props.isMac) ? "mac" : "standard";
        if (this.props.currentPreview) {
            return <div className="preview-actions-container">


                <div className="preview-pp actions-image">

                    <button className="preview-button previous"
                            title={this.props.shortcuts.previousPreview.label + " (" + this.props.shortcuts.previousPreview.keystrokes[keyShortcuts] + ")"}
                            onClick={this.previousImage.bind(this)}>
                        <i className="icon icon-chevron-left" />
                        <i className="icon icon-chevron-left" />
                    </button>
                    {(!this.props.isLqa) ? (
                        <button className="preview-button previous"
                                title={this.props.shortcuts.firstSegment.label + " (" + this.props.shortcuts.firstSegment.keystrokes[keyShortcuts] + ")"}
                            onClick={this.firstSegment.bind(this)}>
                            <i className="icon icon-go-to-first" />
                        </button>
                    ) : (null)}

                    {(!this.props.isLqa) ? (
                    <button className="preview-button previous"
                            title={this.props.shortcuts.previousSegment.label + " (" + this.props.shortcuts.previousSegment.keystrokes[keyShortcuts] + ")"}
                            onClick={this.previousSegment.bind(this)}>
                        <i className="icon icon-chevron-left" />
                    </button>
                    ) : (null)}
                    <button className="info-icon-picture" onClick={this.openPreviewSlider.bind(this)}>
                        <i className="icon icon-picture" />
                    </button>
                    {(!this.props.isLqa) ? (
                    <button onClick={this.nextSegment.bind(this)}
                            title={this.props.shortcuts.nextSegment.label + " (" + this.props.shortcuts.nextSegment.keystrokes[keyShortcuts] + ")"}>
                        <i className="icon icon-chevron-right" />
                    </button>
                    ) : (null)}
                    {(!this.props.isLqa) ? (
                    <button className="preview-button previous"
                            title={this.props.shortcuts.lastSegment.label + " (" + this.props.shortcuts.lastSegment.keystrokes[keyShortcuts] + ")"}
                            onClick={this.lastSegment.bind(this)}>
                        <i className="icon icon-go-to-last" />
                    </button>
                    ) : (null)}

                    <button onClick={this.nextImage.bind(this)}
                            title={this.props.shortcuts.nextPreview.label + " (" + this.props.shortcuts.nextPreview.keystrokes[keyShortcuts] + ")"}>
                        <i className="icon icon-chevron-right" />
                        <i className="icon icon-chevron-right" />
                    </button>

                </div>


                <div className="preview-pp actions-segment">
                    { !this.props.isLqa && this.props.segmentPreviews.size > 1 ? (
                        <div>
                            <button className="preview-button previous"
                                    onClick={this.goToPreviousSegmentImage.bind(this)}
                                    title={this.props.shortcuts.previousSegmentPreview.label + " (" + this.props.shortcuts.previousSegmentPreview.keystrokes[keyShortcuts] + ")"}
                                >
                                <i className="icon icon-chevron-left" /> </button>
                            <div className="n-segments-available">{this.state.index}/{this.props.segmentPreviews.size}</div>
                            <button className="preview-button next"
                                    onClick={this.goToNextSegmentImage.bind(this)}
                                    title={this.props.shortcuts.nextSegmentPreview.label + " (" + this.props.shortcuts.nextSegmentPreview.keystrokes[keyShortcuts] + ")"}
                                >
                                <i className="icon icon-chevron-right" /> </button>
                            <div className="text-n-segments-available">available screens for this segment</div>
                        </div>

                    ) : (null) }
                </div>





                <div className="preview-pp change-window">
                    {(!this.props.isLqa) ? (
                        this.props.showFullScreenButton ? (
                        <div>
                            <button className="preview-button"
                                    onClick={this.openWindow.bind(this)} title="Undock into saparate window"><i className="icon icon-preview-new-window" /> </button>
                            <button className="preview-button"
                                    onClick={this.closePreview.bind(this)} title="Close screenshots preview"><i className="icon icon-cancel-circle" /> </button>
                        </div>
                        ) : (
                            <div>
                                <button className="preview-button"
                                        onClick={this.openPreviewParent.bind(this)} title="Dock to bottom"><i className="icon icon-preview-bottom-window" /> </button>
                            </div>)
                            )
                    : (
                        <div>
                            <button className="ui button approve-all-segments"><i className="icon-checkmark5 icon" />APPROVE ALL</button>
                        </div>
                        )}

                </div>

            </div>
        } else {
            return <div className="preview-actions-container">

                    <div className="preview-pp actions-image">
                        <button className="preview-button previous"
                                onClick={this.previousImage.bind(this)}
                                title={this.props.shortcuts.previousPreview.label + " (" + this.props.shortcuts.previousPreview.keystrokes[keyShortcuts] + ")"}
                        >
                            <i className="icon icon-chevron-left" />
                            <i className="icon icon-chevron-left" />
                        </button>

                        <button className="info-icon-picture">
                            <i className="icon icon-picture" />
                        </button>

                        <button onClick={this.nextImage.bind(this)}
                                title={this.props.shortcuts.nextPreview.label + " (" + this.props.shortcuts.nextPreview.keystrokes[keyShortcuts] + ")"}
                            >
                            <i className="icon icon-chevron-right" />
                            <i className="icon icon-chevron-right" />
                        </button>
                    </div>

                    <div className="preview-pp actions-segment"/>

                    <div className="preview-pp change-window">
                        {this.props.showFullScreenButton ? (
                            <div>
                                <button className="preview-button"
                                        onClick={this.closePreview.bind(this)}><i className="icon icon-cancel-circle" /> </button>
                            </div>
                        ) : (null) }
                    </div>

                </div>
        }

    }
}


export default PreviewActions ;

