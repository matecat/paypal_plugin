let Actions = require('./../actions/PreviewActions');
let Store = require('../store/PreviewsStore');
let Constants = require('../costansts');

class PreviewActions extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            index: 1
        }
        this.nextImage = this.nextImage.bind(this);
        this.previousImage = this.previousImage.bind(this);
        this.nextSegment = this.nextSegment.bind(this);
        this.previousSegment = this.previousSegment.bind(this);
        this.lastSegment = this.lastSegment.bind(this);
        this.firstSegment = this.firstSegment.bind(this);
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

        Actions.selectSegment(this.props.currentSid, this.props.segmentPreviews.get(next).get('file_index'));
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

    componentDidMount() {
        Store.addListener(Constants.NEXT_PREVIEW, this.nextImage);
        Store.addListener(Constants.PREV_PREVIEW, this.previousImage);
        Store.addListener(Constants.NEXT_SEGMENT, this.nextSegment);
        Store.addListener(Constants.PREV_SEGMENT, this.previousSegment);
        Store.addListener(Constants.LAST_SEGMENT, this.lastSegment);
        Store.addListener(Constants.FIRST_SEGMENT, this.firstSegment);
    }

    componentWillUnmount() {
        Store.removeListener(Constants.NEXT_PREVIEW, this.nextImage);
        Store.removeListener(Constants.PREV_PREVIEW, this.previousImage);
        Store.removeListener(Constants.NEXT_SEGMENT, this.nextSegment);
        Store.removeListener(Constants.PREV_SEGMENT, this.previousSegment);
        Store.removeListener(Constants.LAST_SEGMENT, this.lastSegment);
        Store.removeListener(Constants.FIRST_SEGMENT, this.firstSegment);
    }

    componentDidUpdate() {}

    render() {
        let keyShortcuts = (UI.isMac) ? "mac" : "standard";
        if (this.props.currentPreview) {
            return <div className="preview-actions-container">


                <div className="preview-pp actions-image">
                    <button className="preview-button previous"
                            title={UI.shortcuts.previousPreview.label + " (" + UI.shortcuts.previousPreview.keystrokes[keyShortcuts] + ")"}
                            onClick={this.previousImage.bind(this)}>
                        <i className="icon icon-chevron-left" />
                        <i className="icon icon-chevron-left" />
                    </button>

                    <button className="preview-button previous"
                            title={UI.shortcuts.firstSegment.label + " (" + UI.shortcuts.firstSegment.keystrokes[keyShortcuts] + ")"}
                        onClick={this.firstSegment.bind(this)}>
                        <i className="icon icon-go-to-first" />
                    </button>

                    <button className="preview-button previous"
                            title={UI.shortcuts.previousSegment.label + " (" + UI.shortcuts.previousSegment.keystrokes[keyShortcuts] + ")"}
                            onClick={this.previousSegment.bind(this)}>
                        <i className="icon icon-chevron-left" />
                    </button>

                    <div className="info-icon">
                        <i className="icon icon-picture" />
                    </div>

                    <button onClick={this.nextSegment.bind(this)}
                            title={UI.shortcuts.nextSegment.label + " (" + UI.shortcuts.nextSegment.keystrokes[keyShortcuts] + ")"}>
                        <i className="icon icon-chevron-right" />
                    </button>

                    <button className="preview-button previous"
                            title={UI.shortcuts.lastSegment.label + " (" + UI.shortcuts.lastSegment.keystrokes[keyShortcuts] + ")"}
                            onClick={this.lastSegment.bind(this)}>
                        <i className="icon icon-go-to-last" />
                    </button>

                    <button onClick={this.nextImage.bind(this)}
                            title={UI.shortcuts.nextPreview.label + " (" + UI.shortcuts.nextPreview.keystrokes[keyShortcuts] + ")"}>
                        <i className="icon icon-chevron-right" />
                        <i className="icon icon-chevron-right" />
                    </button>
                </div>


                <div className="preview-pp actions-segment">
                    { this.props.segmentPreviews.size > 1 ? (
                        <div>
                            <button className="preview-button previous"
                                    onClick={this.goToPreviousSegmentImage.bind(this)}> <i className="icon icon-chevron-left" /> </button>
                            <div className="n-segments-available">{this.state.index}/{this.props.segmentPreviews.size}</div>
                            <button className="preview-button next"
                                    onClick={this.goToNextSegmentImage.bind(this)}> <i className="icon icon-chevron-right" /> </button>
                            <div className="text-n-segments-available">available screens for this segment</div>
                        </div>

                    ) : (null) }
                </div>





                <div className="preview-pp change-window">
                    {this.props.showFullScreenButton ? (
                        <div>
                            <button className="preview-button"
                                    onClick={this.openWindow.bind(this)}><i className="icon icon-preview-new-window" /> </button>
                            <button className="preview-button"
                                    onClick={this.closePreview.bind(this)}><i className="icon icon-cancel-circle" /> </button>
                        </div>
                    ) : (
                        <div>
                            <button className="preview-button"
                                    onClick={this.openPreviewParent.bind(this)}><i className="icon icon-preview-bottom-window" /> </button>
                        </div>) }

                </div>

            </div>
        } else {
            return <div className="preview-actions-container">

                    <div className="preview-pp actions-image">
                        <button className="preview-button previous"
                                onClick={this.previousImage.bind(this)}>
                            <i className="icon icon-chevron-left" />
                            <i className="icon icon-chevron-left" />
                        </button>

                        <div className="info-icon">
                            <i className="icon icon-picture" />
                        </div>

                        <button onClick={this.nextImage.bind(this)}>
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

